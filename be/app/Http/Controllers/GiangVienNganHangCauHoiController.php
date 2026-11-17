<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveNganHangCauHoiRequest;
use App\Models\NganHangCauHoi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GiangVienNganHangCauHoiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'chu_de' => ['nullable', 'string', 'max:120'],
            'loai_cau_hoi' => ['nullable', 'in:trac_nghiem,tu_luan'],
            'do_kho' => ['nullable', 'in:de,trung_binh,kho'],
            'trang_thai' => ['nullable', 'in:active,inactive'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = NganHangCauHoi::query()
            ->where('nguoi_tao_id', $request->user()->id)
            ->with('dapAns');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('noi_dung', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }
        foreach (['chu_de', 'loai_cau_hoi', 'do_kho', 'trang_thai'] as $filter) {
            $value = $request->query($filter);
            if (is_string($value) && $value !== '') {
                $query->where($filter, $value);
            }
        }

        $paginator = $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );

        return response()->json([
            'status' => true,
            'data' => $paginator->getCollection()->map(fn ($question) => $this->serialize($question))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'topics' => NganHangCauHoi::where('nguoi_tao_id', $request->user()->id)
                    ->whereNotNull('chu_de')->distinct()->orderBy('chu_de')->pluck('chu_de'),
            ],
        ]);
    }

    public function store(SaveNganHangCauHoiRequest $request): JsonResponse
    {
        $question = DB::transaction(fn () => $this->createQuestion($request->validated(), (int) $request->user()->id));
        return response()->json(['status' => true, 'data' => $this->serialize($question->load('dapAns'))], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->serialize($this->ownedQuestion($request, $id)->load('dapAns')),
        ]);
    }

    public function update(SaveNganHangCauHoiRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $question = DB::transaction(function () use ($request, $id, $data) {
            $question = $this->ownedQuestion($request, $id, true);
            $data['trang_thai'] = $question->trang_thai;
            $question->fill($this->questionFields($data));
            $question->version++;
            $question->save();
            $question->dapAns()->delete();
            $this->saveAnswers($question, $data);
            return $question->fresh()->load('dapAns');
        });

        return response()->json(['status' => true, 'data' => $this->serialize($question)]);
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        $copy = DB::transaction(function () use ($request, $id) {
            $source = $this->ownedQuestion($request, $id)->load('dapAns');
            $copy = NganHangCauHoi::create([
                'nguoi_tao_id' => $request->user()->id,
                'noi_dung' => $source->noi_dung,
                'loai_cau_hoi' => $source->loai_cau_hoi,
                'chu_de' => $source->chu_de,
                'do_kho' => $source->do_kho,
                'diem_tu_luan' => $source->diem_tu_luan,
                'dap_an_mau' => $source->dap_an_mau,
                'trang_thai' => 'active',
                'version' => 1,
            ]);
            foreach ($source->dapAns as $answer) {
                $copy->dapAns()->create([
                    'noi_dung' => $answer->noi_dung,
                    'la_dap_an_dung' => $answer->la_dap_an_dung,
                    'thu_tu' => $answer->thu_tu,
                ]);
            }
            return $copy->load('dapAns');
        });
        return response()->json(['status' => true, 'data' => $this->serialize($copy)], 201);
    }

    public function deactivate(Request $request, int $id): JsonResponse
    {
        $question = $this->ownedQuestion($request, $id);
        if ($question->trang_thai !== 'inactive') {
            $question->update(['trang_thai' => 'inactive', 'version' => $question->version + 1]);
        }
        return response()->json(['status' => true, 'message' => 'Đã vô hiệu hóa câu hỏi.']);
    }

    /** Save imported preview rows into the bank without changing assignment import endpoints. */
    public function importSave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'questions' => ['required', 'array', 'min:1', 'max:200'],
            'questions.*.noi_dung' => ['required', 'string', 'max:10000'],
            'questions.*.loai_cau_hoi' => ['required', 'in:trac_nghiem,tu_luan'],
            'questions.*.diem' => ['nullable', 'numeric', 'gt:0', 'lte:10'],
            'questions.*.dap_an' => ['nullable', 'array', 'max:6'],
            'questions.*.dap_an.*' => ['required', 'string', 'max:5000'],
            'questions.*.dap_an_dung_index' => ['nullable', 'integer', 'min:0', 'max:5'],
            'questions.*.dap_an_mau' => ['nullable', 'string', 'max:20000'],
            'chu_de' => ['nullable', 'string', 'max:120'],
            'do_kho' => ['required', 'in:de,trung_binh,kho'],
        ]);

        foreach ($validated['questions'] as $index => $item) {
            $answers = $item['dap_an'] ?? [];
            if ($item['loai_cau_hoi'] === 'trac_nghiem' && (count($answers) < 2 || count($answers) > 6 || !array_key_exists((int) ($item['dap_an_dung_index'] ?? -1), $answers))) {
                throw ValidationException::withMessages(["questions.$index.dap_an" => 'Câu trắc nghiệm cần 2–6 phương án và một đáp án đúng.']);
            }
            if ($item['loai_cau_hoi'] === 'tu_luan' && (empty($item['dap_an_mau']) || empty($item['diem']) || !SaveNganHangCauHoiRequest::hasTwoDecimals($item['diem']))) {
                throw ValidationException::withMessages(["questions.$index.dap_an_mau" => 'Câu tự luận cần đáp án mẫu và điểm tối đa hợp lệ.']);
            }
        }

        $created = DB::transaction(function () use ($validated, $request) {
            $items = [];
            foreach ($validated['questions'] as $item) {
                $item['chu_de'] = $validated['chu_de'] ?? null;
                $item['do_kho'] = $validated['do_kho'];
                $items[] = $this->createQuestion($item, (int) $request->user()->id);
            }
            return $items;
        });

        return response()->json(['status' => true, 'data' => ['count' => count($created)]], 201);
    }

    private function ownedQuestion(Request $request, int $id, bool $lock = false): NganHangCauHoi
    {
        $query = NganHangCauHoi::where('nguoi_tao_id', $request->user()->id)->whereKey($id);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->firstOrFail();
    }

    private function createQuestion(array $data, int $ownerId): NganHangCauHoi
    {
        $question = NganHangCauHoi::create(['nguoi_tao_id' => $ownerId, ...$this->questionFields($data)]);
        $this->saveAnswers($question, $data);
        return $question;
    }

    private function questionFields(array $data): array
    {
        $type = $data['loai_cau_hoi'];
        return [
            'noi_dung' => trim($data['noi_dung']),
            'loai_cau_hoi' => $type,
            'chu_de' => isset($data['chu_de']) && trim($data['chu_de']) !== '' ? trim($data['chu_de']) : null,
            'do_kho' => $data['do_kho'],
            'diem_tu_luan' => $type === 'tu_luan' ? $data['diem_tu_luan'] : null,
            'dap_an_mau' => $type === 'tu_luan' ? trim($data['dap_an_mau']) : null,
            'trang_thai' => $data['trang_thai'] ?? 'active',
        ];
    }

    private function saveAnswers(NganHangCauHoi $question, array $data): void
    {
        if ($question->loai_cau_hoi !== 'trac_nghiem') {
            return;
        }
        foreach ($data['dap_an'] as $index => $content) {
            $question->dapAns()->create([
                'noi_dung' => trim($content),
                'la_dap_an_dung' => $index === (int) $data['dap_an_dung_index'],
                'thu_tu' => $index + 1,
            ]);
        }
    }

    private function serialize(NganHangCauHoi $question): array
    {
        return [
            'id' => $question->id,
            'noi_dung' => $question->noi_dung,
            'loai_cau_hoi' => $question->loai_cau_hoi,
            'chu_de' => $question->chu_de,
            'do_kho' => $question->do_kho,
            'diem_tu_luan' => $question->diem_tu_luan,
            'dap_an_mau' => $question->dap_an_mau,
            'trang_thai' => $question->trang_thai,
            'version' => $question->version,
            'updated_at' => $question->updated_at,
            'dap_an' => $question->relationLoaded('dapAns') ? $question->dapAns->map(fn ($answer) => [
                'id' => $answer->id,
                'noi_dung' => $answer->noi_dung,
                'la_dap_an_dung' => (bool) $answer->la_dap_an_dung,
                'thu_tu' => $answer->thu_tu,
            ])->values() : [],
        ];
    }
}
