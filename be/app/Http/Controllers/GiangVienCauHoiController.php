<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GiangVienCauHoiController extends Controller
{
    /**
     * Chỉ người tạo bài tập được quản lý câu hỏi.
     */
    private function checkAssignment(
        Request $request,
        int $baiTapId
    ): void {
        $user = $request->user();

        abort_unless($user, 401, 'Chưa đăng nhập.');

        $exists = DB::table('bai_tap')
            ->where('id', $baiTapId)
            ->where('nguoi_tao_id', $user->id)
            ->exists();

        abort_unless(
            $exists,
            403,
            'Bạn không có quyền quản lý bài kiểm tra này.'
        );
    }

    /**
     * Không thay đổi câu hỏi khi đã có bài nộp.
     */
    private function checkCanEdit(int $baiTapId): void
    {
        $hasSubmission = DB::table('bai_nop')
            ->where('bai_tap_id', $baiTapId)
            ->exists();

        if ($hasSubmission) {
            abort(
                409,
                'Bài kiểm tra đã có sinh viên nộp. '
                . 'Không thể sửa hoặc xóa câu hỏi.'
            );
        }
    }

    /**
     * Lấy câu hỏi và đáp án để giảng viên quản lý.
     */
    public function index(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $this->checkAssignment($request, $baiTapId);

        $questions = DB::table('cau_hoi')
            ->where('bai_tap_id', $baiTapId)
            ->orderBy('thu_tu')
            ->orderBy('id')
            ->get()
            ->map(function ($question) {
                $options = DB::table('dap_an')
                    ->where('cau_hoi_id', $question->id)
                    ->orderBy('thu_tu')
                    ->orderBy('id')
                    ->get([
                        'id',
                        'noi_dung',
                        'la_dap_an_dung',
                        'thu_tu',
                    ]);

                return [
                    'id' => $question->id,
                    'bai_tap_id' => $question->bai_tap_id,
                    'noi_dung' => $question->noi_dung,
                    'loai_cau_hoi' => $question->loai_cau_hoi,
                    'diem' => $question->diem,
                    'thu_tu' => $question->thu_tu,
                    'dap_an_mau' => $question->dap_an_mau,
                    'dap_an' => $options,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $questions->values(),
        ]);
    }

    /**
     * Kiểm tra nội dung nhập từ giao diện.
     */
    private function validateQuestion(Request $request): array
    {
        $data = Validator::make($request->all(), [
            'noi_dung' => ['required', 'string', 'max:10000'],
            'loai_cau_hoi' => [
                'required',
                'in:trac_nghiem,tu_luan',
            ],
            'diem' => [
                'required',
                'numeric',
                'gt:0',
                'lte:100',
            ],
            'dap_an_mau' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'dap_an' => ['nullable', 'array'],
            'dap_an.*' => [
                'required',
                'string',
                'max:5000',
            ],
            'dap_an_dung_index' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ])->validate();

        if ($data['loai_cau_hoi'] === 'trac_nghiem') {
            $options = $data['dap_an'] ?? [];

            if (count($options) < 2 || count($options) > 6) {
                throw ValidationException::withMessages([
                    'dap_an' =>
                        'Câu trắc nghiệm phải có từ 2 đến 6 đáp án.',
                ]);
            }

            $correct = $data['dap_an_dung_index'] ?? null;

            if (
                $correct === null ||
                !array_key_exists((int) $correct, $options)
            ) {
                throw ValidationException::withMessages([
                    'dap_an_dung_index' =>
                        'Vui lòng chọn một đáp án đúng.',
                ]);
            }

            $data['dap_an_mau'] = null;
        } else {
            if (empty(trim($data['dap_an_mau'] ?? ''))) {
                throw ValidationException::withMessages([
                    'dap_an_mau' =>
                        'Vui lòng nhập đáp án mẫu cho câu tự luận.',
                ]);
            }

            $data['dap_an'] = [];
        }

        return $data;
    }

    /**
     * Tạo câu hỏi mới.
     */
    public function store(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $this->checkAssignment($request, $baiTapId);

        $data = $this->validateQuestion($request);

        $questionId = DB::transaction(
            function () use ($baiTapId, $data) {
                // Khóa bài tập trong lúc cập nhật câu hỏi.
                DB::table('bai_tap')
                    ->where('id', $baiTapId)
                    ->lockForUpdate()
                    ->first();

                $this->checkCanEdit($baiTapId);

                $nextOrder = (int) DB::table('cau_hoi')
                    ->where('bai_tap_id', $baiTapId)
                    ->max('thu_tu') + 1;

                $id = DB::table('cau_hoi')->insertGetId([
                    'bai_tap_id' => $baiTapId,
                    'noi_dung' => $data['noi_dung'],
                    'loai_cau_hoi' => $data['loai_cau_hoi'],
                    'diem' => $data['diem'],
                    'thu_tu' => $nextOrder,
                    'dap_an_mau' => $data['dap_an_mau'],
                ]);

                $this->saveOptions($id, $data);

                return $id;
            }
        );

        return response()->json([
            'status' => true,
            'message' => 'Đã tạo câu hỏi thành công.',
            'data' => [
                'id' => $questionId,
            ],
        ], 201);
    }

    /**
     * Cập nhật câu hỏi.
     */
    public function update(
        Request $request,
        int $baiTapId,
        int $cauHoiId
    ): JsonResponse {
        $this->checkAssignment($request, $baiTapId);

        $data = $this->validateQuestion($request);

        DB::transaction(
            function () use ($baiTapId, $cauHoiId, $data) {
                DB::table('bai_tap')
                    ->where('id', $baiTapId)
                    ->lockForUpdate()
                    ->first();

                $this->checkCanEdit($baiTapId);

                $question = DB::table('cau_hoi')
                    ->where('id', $cauHoiId)
                    ->where('bai_tap_id', $baiTapId)
                    ->first();

                abort_unless(
                    $question,
                    404,
                    'Không tìm thấy câu hỏi.'
                );

                DB::table('cau_hoi')
                    ->where('id', $cauHoiId)
                    ->update([
                        'noi_dung' => $data['noi_dung'],
                        'loai_cau_hoi' => $data['loai_cau_hoi'],
                        'diem' => $data['diem'],
                        'dap_an_mau' => $data['dap_an_mau'],
                    ]);

                // Chỉ xóa đáp án khi chưa có bài nộp.
                DB::table('dap_an')
                    ->where('cau_hoi_id', $cauHoiId)
                    ->delete();

                $this->saveOptions($cauHoiId, $data);
            }
        );

        return response()->json([
            'status' => true,
            'message' => 'Đã cập nhật câu hỏi.',
        ]);
    }

    /**
     * Xóa câu hỏi.
     */
    public function destroy(
        Request $request,
        int $baiTapId,
        int $cauHoiId
    ): JsonResponse {
        $this->checkAssignment($request, $baiTapId);

        DB::transaction(
            function () use ($baiTapId, $cauHoiId) {
                DB::table('bai_tap')
                    ->where('id', $baiTapId)
                    ->lockForUpdate()
                    ->first();

                $this->checkCanEdit($baiTapId);

                $question = DB::table('cau_hoi')
                    ->where('id', $cauHoiId)
                    ->where('bai_tap_id', $baiTapId)
                    ->first();

                abort_unless(
                    $question,
                    404,
                    'Không tìm thấy câu hỏi.'
                );

                DB::table('dap_an')
                    ->where('cau_hoi_id', $cauHoiId)
                    ->delete();

                DB::table('cau_hoi')
                    ->where('id', $cauHoiId)
                    ->delete();
            }
        );

        return response()->json([
            'status' => true,
            'message' => 'Đã xóa câu hỏi.',
        ]);
    }

    /**
     * Lưu các lựa chọn trắc nghiệm.
     */
    private function saveOptions(
        int $cauHoiId,
        array $data
    ): void {
        if ($data['loai_cau_hoi'] !== 'trac_nghiem') {
            return;
        }

        foreach ($data['dap_an'] as $index => $content) {
            DB::table('dap_an')->insert([
                'cau_hoi_id' => $cauHoiId,
                'noi_dung' => trim($content),
                'la_dap_an_dung' =>
                    (int) $index ===
                    (int) $data['dap_an_dung_index'],
                'thu_tu' => (int) $index + 1,
            ]);
        }
    }
}
