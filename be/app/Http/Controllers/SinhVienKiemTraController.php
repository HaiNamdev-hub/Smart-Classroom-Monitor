<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use App\Support\ExamScoreAllocator;
use App\Support\ExamMode;

class SinhVienKiemTraController extends Controller
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    /*
    |--------------------------------------------------------------------------
    | THỜI GIAN
    |--------------------------------------------------------------------------
    */

    private function nowVN(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    // Database DATETIME đang lưu giờ Việt Nam,
    // không phải chuỗi UTC.
    private function dateVN($value): Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        return Carbon::createFromFormat(
            '!Y-m-d H:i:s',
            (string) $value,
            self::TIMEZONE
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LẤY ID SINH VIÊN
    |--------------------------------------------------------------------------
    */

    private function studentId(Request $request): int
    {
        $user = $request->user();

        abort_unless(
            $user,
            401,
            'Bạn chưa đăng nhập.'
        );

        $id = DB::table('sinh_vien')
            ->where('nguoi_dung_id', $user->id)
            ->value('id');

        abort_unless(
            $id,
            403,
            'Tài khoản không phải Sinh viên.'
        );

        return (int) $id;
    }

    private function studentUserId(int $studentId): int
    {
        $userId = DB::table('sinh_vien')
            ->where('id', $studentId)
            ->value('nguoi_dung_id');

        abort_unless($userId, 403, 'Tài khoản không phải Sinh viên.');

        return (int) $userId;
    }

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA SINH VIÊN THUỘC LỚP
    |--------------------------------------------------------------------------
    */

    private function assignment(
        int $baiTapId,
        int $studentId
    ): object {
        $userId = $this->studentUserId($studentId);

        $assignmentQuery = DB::table('bai_tap as bt')
            ->where('bt.id', $baiTapId);

        // Legacy bank-generated drafts remain inaccessible to students when
        // their metadata exists. Normal exams do not depend on Phase 3 tables.
        if (Schema::hasTable('ngan_hang_de_tao')) {
            $assignmentQuery->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('ngan_hang_de_tao as ndt')
                    ->whereColumn('ndt.bai_tap_id', 'bt.id')
                    ->where('ndt.trang_thai', 'nhap');
            });
        }

        $assignment = $assignmentQuery->whereExists(function ($query) use ($userId) {
                $query->selectRaw('1')
                    ->from('thanh_vien_lop as tv')
                    ->whereColumn(
                        'tv.lop_hoc_id',
                        'bt.lop_hoc_id'
                    )
                    ->where(
                        'tv.sinh_vien_id',
                        $userId
                    )
                    ->where(
                        'tv.trang_thai',
                        'dang_hoc'
                    );
            })
            ->select('bt.*')
            ->first();

        abort_unless(
            $assignment,
            404,
            'Không tìm thấy bài kiểm tra thuộc lớp của bạn.'
        );

        return $assignment;
    }

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA GIỜ BẮT ĐẦU
    |--------------------------------------------------------------------------
    */

    private function ensureStarted(object $assignment): void
    {
        abort_if(
            empty($assignment->thoi_gian_bat_dau),
            422,
            'Bài kiểm tra chưa được đặt lịch.'
        );

        $start = $this->dateVN(
            $assignment->thoi_gian_bat_dau
        );

        if ($this->nowVN()->lessThan($start)) {
            abort(
                403,
                'Bài kiểm tra chưa bắt đầu. Giờ mở bài: '
                    . $start->format('d/m/Y H:i')
                    . ' (giờ Việt Nam).'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HẠN KẾT THÚC
    |--------------------------------------------------------------------------
    */

    private function deadline(object $assignment): Carbon
    {
        $dates = [];

        if (!empty($assignment->han_nop)) {
            $dates[] = $this->dateVN(
                $assignment->han_nop
            );
        }

        if (!empty($assignment->thoi_gian_ket_thuc)) {
            $dates[] = $this->dateVN(
                $assignment->thoi_gian_ket_thuc
            );
        }

        abort_if(
            empty($dates),
            422,
            'Bài kiểm tra chưa có thời gian kết thúc.'
        );

        $deadline = $dates[0];

        foreach ($dates as $date) {
            if ($date->lessThan($deadline)) {
                $deadline = $date;
            }
        }

        return $deadline;
    }

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA ĐÃ NỘP BÀI
    |--------------------------------------------------------------------------
    */

    private function alreadySubmitted(
        int $baiTapId,
        int $studentId
    ): bool {
        return DB::table('bai_nop')
            ->where('bai_tap_id', $baiTapId)
            ->where('sinh_vien_id', $this->studentUserId($studentId))
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | ĐẾM SỐ LẦN VI PHẠM
    |--------------------------------------------------------------------------
    */

    private function violationCount(
        int $baiTapId,
        int $studentId
    ): int {
        return DB::table('vi_pham_kiem_tra')
            ->where('bai_tap_id', $baiTapId)
            ->where('sinh_vien_id', $studentId)
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | TRẠNG THÁI GIÁM SÁT: ĐIỆN THOẠI 3, CAMERA KHÁC 5, RỜI TAB 3
    |--------------------------------------------------------------------------
    */

    private function violationStateData(int $baiTapId, int $sinhVienId): array
    {
        $tabCount = DB::table('vi_pham_kiem_tra')
            ->where('bai_tap_id', $baiTapId)
            ->where('sinh_vien_id', $sinhVienId)
            ->count();

        $cameraQuery = DB::table('canh_bao_camera')
            ->where('bai_tap_id', $baiTapId)
            ->where('sinh_vien_id', $sinhVienId);

        $phoneCount = (clone $cameraQuery)
            ->where('loai_canh_bao', 'phat_hien_dien_thoai')
            ->count();

        $otherCameraCount = (clone $cameraQuery)
            ->where('loai_canh_bao', '!=', 'phat_hien_dien_thoai')
            ->count();

        $cameraRequired = ExamMode::requiresCamera($baiTapId);
        $reason = ($cameraRequired && $phoneCount >= 3)
            ? 'dien_thoai'
            : ($cameraRequired && $otherCameraCount >= 5
                ? 'camera'
                : ($tabCount >= 3 ? 'roi_tab' : null));

        return [
            'soLanViPham' => $tabCount,
            'soLanCamera' => $otherCameraCount,
            'soLanDienThoai' => $phoneCount,
            'soLanConLai' => max(0, 3 - $tabCount),
            'soLanCameraConLai' => max(0, 5 - $otherCameraCount),
            'soLanDienThoaiConLai' => max(0, 3 - $phoneCount),
            'biKhoa' => $reason !== null,
            'lyDoKhoa' => $reason,
        ];
    }

    private function assertExamNotLocked(int $baiTapId, int $sinhVienId): void
    {
        $state = $this->violationStateData($baiTapId, $sinhVienId);

        if ($state['lyDoKhoa'] === 'dien_thoai') {
            abort(403, 'Bài kiểm tra đã bị khóa do phát hiện điện thoại 3 lần.');
        }

        if ($state['lyDoKhoa'] === 'camera') {
            abort(403, 'Bài kiểm tra đã bị khóa do 5 cảnh báo camera khác.');
        }

        if ($state['lyDoKhoa'] === 'roi_tab') {
            abort(403, 'Bài kiểm tra đã bị khóa do rời tab 3 lần.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LẤY CÂU HỎI
    |--------------------------------------------------------------------------
    */

    private function questions(int $baiTapId, ?int $attemptId = null): array
    {
        $questionOrder = $attemptId
            ? DB::table('bai_kiem_tra_luot_lam_cau_hoi')
                ->where('luot_lam_id', $attemptId)
                ->orderBy('thu_tu_hien_thi')->pluck('cau_hoi_id')->map(fn ($id) => (int) $id)->all()
            : [];
        $questionsQuery = DB::table('cau_hoi')->where('bai_tap_id', $baiTapId);
        if ($questionOrder) {
            $questions = $questionsQuery->whereIn('id', $questionOrder)->get()
                ->keyBy('id');
            $questions = collect($questionOrder)->map(fn ($id) => $questions->get($id))->filter()->values();
        } else {
            // Historical attempts without a persisted order retain the original exam order.
            $questions = $questionsQuery->orderBy('thu_tu')->orderBy('id')->get();
        }

        $answerRows = DB::table('dap_an')
            ->whereIn(
                'cau_hoi_id',
                $questions->pluck('id')
            )
            ->get([
                'id',
                'cau_hoi_id',
                'noi_dung',
                'thu_tu',
            ])
            ->groupBy('cau_hoi_id');

        $answerOrder = $attemptId
            ? DB::table('bai_kiem_tra_luot_lam_dap_an')
                ->where('luot_lam_id', $attemptId)
                ->orderBy('cau_hoi_id')->orderBy('thu_tu_hien_thi')
                ->get(['cau_hoi_id', 'dap_an_id'])
                ->groupBy('cau_hoi_id')
            : collect();

        return $questions->map(function ($question) use (
            $answerRows,
            $answerOrder
        ) {
            $answers = $answerRows[$question->id] ?? collect();
            $savedOrder = $answerOrder->get($question->id, collect())->pluck('dap_an_id')->map(fn ($id) => (int) $id)->all();
            if ($savedOrder) {
                $answers = $answers->keyBy('id');
                $answers = collect($savedOrder)->map(fn ($id) => $answers->get($id))->filter()->values();
            } else {
                $answers = $answers->sortBy([['thu_tu', 'asc'], ['id', 'asc']])->values();
            }
            return [
                'id' => $question->id,
                'noi_dung' => $question->noi_dung,
                'loai_cau_hoi' => $question->loai_cau_hoi,
                'diem' => $question->diem,

                // Không trả đáp án đúng cho Sinh viên.
                'dap_an' => $answers->map(function ($answer) {
                    return [
                        'id' => $answer->id,
                        'noi_dung' => $answer->noi_dung,
                    ];
                })->values()->all(),
            ];
        })->all();
    }

    /*
    |--------------------------------------------------------------------------
    | API GET: LẤY ĐỀ KIỂM TRA
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $studentId = $this->studentId($request);

        $assignment = $this->assignment(
            $baiTapId,
            $studentId
        );

        $this->ensureStarted($assignment);

        $this->assertExamNotLocked(
            $baiTapId,
            $studentId
        );

        if (
            $this->alreadySubmitted(
                $baiTapId,
                $studentId
            )
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn đã nộp bài kiểm tra.',
            ], 409);
        }

        $deadline = $this->deadline($assignment);

        if (
            $this->nowVN()->greaterThanOrEqualTo(
                $deadline
            )
        ) {
            return response()->json([
                'status' => false,
                'message' =>
                'Bài kiểm tra đã hết thời gian làm bài.',
            ], 409);
        }

        $attempt = DB::table('bai_kiem_tra_luot_lam')
            ->where('bai_tap_id', $baiTapId)
            ->where('sinh_vien_id', $studentId)
            ->first(['id', 'bat_dau_luc']);
        $questions = $this->questions($baiTapId, $attempt ? (int) $attempt->id : null);

        if (empty($questions)) {
            return response()->json([
                'status' => false,
                'message' =>
                'Bài kiểm tra chưa có câu hỏi.',
            ], 422);
        }

        $draft = DB::table('bai_lam_nhap')
            ->where('bai_tap_id', $baiTapId)
            ->where('sinh_vien_id', $studentId)
            ->first();

        $activeSession = DB::table('phien_giam_sat')
            ->where('trang_thai', 'dang_dien_ra')
            ->where(function ($q) use ($baiTapId, $assignment) {
                $q->where('bai_tap_id', $baiTapId)
                  ->orWhere('lop_hoc_id', $assignment->lop_hoc_id);
            })
            ->orderByDesc('id')
            ->first(['id', 'ten_phien', 'trang_thai']);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $assignment->id,
                'tieuDe' => $assignment->tieu_de,
                'moTa' => $assignment->mo_ta,
                'hinhThucKiemTra' => ExamMode::forAssignment($baiTapId),
                'yeuCauCamera' => ExamMode::requiresCamera($baiTapId),

                'deadline' =>
                $deadline->toIso8601String(),

                'serverTime' =>
                $this->nowVN()->toIso8601String(),

                'choPhepNopTre' =>
                (bool) $assignment->cho_phep_nop_tre,

                'questions' => $questions,

                'answers' => $draft
                    ? json_decode(
                        $draft->cau_tra_loi ?: '{}',
                        true
                    )
                    : new \stdClass(),
                'draftUpdatedAt' => $draft?->updated_at
                    ? $this->dateVN($draft->updated_at)->toIso8601String()
                    : null,
                'draftRevision' => $draft
                    ? hash('sha256', (string) $draft->cau_tra_loi)
                    : null,
                'attemptStartedAt' => $attempt
                    ? $this->dateVN($attempt->bat_dau_luc)->toIso8601String()
                    : null,

                'phienGiamSat' => $activeSession ? [
                    'id' => $activeSession->id,
                    'tenPhien' => $activeSession->ten_phien,
                    'trangThai' => $activeSession->trang_thai,
                ] : null,
                'sinhVienId' => $studentId,
                'trangThaiLamBai' => $attempt ? 'IN_PROGRESS' : 'PRE_EXAM',
            ],
        ]);
    }

    /** Mark the moment the student has entered fullscreen and camera preparation is complete. */
    public function startExam(Request $request, int $baiTapId): JsonResponse
    {
        $validationRules = [
            'fullscreen' => ['required', 'accepted'],
        ];
        if (ExamMode::requiresCamera($baiTapId)) {
            $validationRules['cameraReady'] = ['required', 'accepted'];
        }
        $request->validate($validationRules);
        $studentId = $this->studentId($request);

        $cameraReady = $request->boolean('cameraReady');

        return DB::transaction(function () use ($baiTapId, $studentId, $cameraReady) {
            // Keep this short, rare lock for the absent-attempt creation race.
            DB::table('bai_tap')->where('id', $baiTapId)->lockForUpdate()->first();
            $assignment = $this->assignment($baiTapId, $studentId);
            abort_unless(!ExamMode::requiresCamera($baiTapId) || $cameraReady, 422, 'Hình thức thi yêu cầu camera hoạt động trước khi bắt đầu.');
            $this->ensureStarted($assignment);
            $this->assertExamNotLocked($baiTapId, $studentId);

            abort_if(
                $this->alreadySubmitted($baiTapId, $studentId),
                409,
                'Bài kiểm tra đã được nộp.'
            );
            abort_if(
                $this->nowVN()->greaterThanOrEqualTo($this->deadline($assignment)),
                409,
                'Bài kiểm tra đã hết thời gian làm bài.'
            );
            abort_unless(
                DB::table('cau_hoi')->where('bai_tap_id', $baiTapId)->exists(),
                422,
                'Bài kiểm tra chưa có câu hỏi.'
            );
            ExamScoreAllocator::assertStoredTotalIsTen($baiTapId);

            $attempt = DB::table('bai_kiem_tra_luot_lam')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $studentId)
                ->first();

            if (!$attempt) {
                $startedAt = $this->nowVN();
                $attemptId = DB::table('bai_kiem_tra_luot_lam')->insertGetId([
                    'bai_tap_id' => $baiTapId,
                    'sinh_vien_id' => $studentId,
                    'bat_dau_luc' => $startedAt,
                    'created_at' => $startedAt,
                    'updated_at' => $startedAt,
                ]);
                $shuffleConfig = Schema::hasTable('bai_tap_cau_hinh_tron')
                    ? DB::table('bai_tap_cau_hinh_tron')
                        ->where('bai_tap_id', $baiTapId)
                        ->first(['tron_cau_hoi', 'tron_dap_an'])
                    : null;
                app(\App\Support\ExamAttemptOrder::class)->persist(
                    (int) $attemptId,
                    $baiTapId,
                    (bool) ($shuffleConfig->tron_cau_hoi ?? false),
                    (bool) ($shuffleConfig->tron_dap_an ?? false)
                );
                $duplicate = false;
            } else {
                $attemptId = (int) $attempt->id;
                $startedAt = $this->dateVN($attempt->bat_dau_luc);
                $duplicate = true;
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'trangThaiLamBai' => 'IN_PROGRESS',
                    'hinhThucKiemTra' => ExamMode::forAssignment($baiTapId),
                    'yeuCauCamera' => ExamMode::requiresCamera($baiTapId),
                    'batDauLuc' => $startedAt->toIso8601String(),
                    'duplicate' => $duplicate,
                    // The client adopts this server-persisted order without reloading
                    // the exam or restarting camera/monitoring state.
                    'questions' => $this->questions($baiTapId, $attemptId),
                ],
            ], $duplicate ? 200 : 201);
        }, 3);
    }

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA ĐÁP ÁN HỢP LỆ
    |--------------------------------------------------------------------------
    */

    private function validatedAnswers(
        int $baiTapId,
        $input
    ): array {
        if (!is_array($input)) {
            throw ValidationException::withMessages([
                'answers' => 'Dữ liệu đáp án không hợp lệ.',
            ]);
        }

        $questions = DB::table('cau_hoi')
            ->where('bai_tap_id', $baiTapId)
            ->get([
                'id',
                'loai_cau_hoi',
            ])
            ->keyBy('id');

        $result = [];

        foreach ($input as $questionId => $value) {
            if (
                !ctype_digit((string) $questionId) ||
                !$questions->has((int) $questionId)
            ) {
                throw ValidationException::withMessages([
                    'answers' =>
                    'Câu hỏi không thuộc bài kiểm tra.',
                ]);
            }

            $question = $questions->get(
                (int) $questionId
            );

            if (
                $question->loai_cau_hoi === 'trac_nghiem'
            ) {
                if ($value === null || $value === '') {
                    continue;
                }

                if (
                    !ctype_digit((string) $value) ||
                    (int) $value <= 0
                ) {
                    throw ValidationException::withMessages([
                        'answers' =>
                        'Mã đáp án không hợp lệ.',
                    ]);
                }

                $answerId = (int) $value;

                $valid = DB::table('dap_an')
                    ->where('id', $answerId)
                    ->where('cau_hoi_id', $question->id)
                    ->exists();

                if (!$valid) {
                    throw ValidationException::withMessages([
                        'answers' =>
                        'Đáp án không thuộc câu hỏi.',
                    ]);
                }

                $result[(string) $questionId] = $answerId;
            } elseif (
                $question->loai_cau_hoi === 'tu_luan'
            ) {
                if (
                    !is_string($value) ||
                    mb_strlen($value) > 20000
                ) {
                    throw ValidationException::withMessages([
                        'answers' =>
                        'Câu tự luận tối đa 20.000 ký tự.',
                    ]);
                }

                $result[(string) $questionId] = $value;
            } else {
                throw ValidationException::withMessages([
                    'answers' =>
                    'Loại câu hỏi không được hỗ trợ.',
                ]);
            }
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | API PUT: LƯU NHÁP
    |--------------------------------------------------------------------------
    */

    public function saveDraft(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $input = $request->validate([
            'answers' => [
                'present',
                'array',
            ],
            'baseDraftRevision' => ['present', 'nullable', 'string', 'size:64'],
        ]);

        $studentId = $this->studentId($request);

        $assignment = $this->assignment(
            $baiTapId,
            $studentId
        );

        $this->ensureStarted($assignment);

        $this->assertExamNotLocked(
            $baiTapId,
            $studentId
        );

        if (
            $this->alreadySubmitted(
                $baiTapId,
                $studentId
            )
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn đã nộp bài kiểm tra.',
            ], 409);
        }

        if (
            $this->nowVN()->greaterThanOrEqualTo(
                $this->deadline($assignment)
            )
        ) {
            return response()->json([
                'status' => false,
                'message' =>
                'Đã hết thời gian lưu bài nháp.',
            ], 409);
        }

        $answers = $this->validatedAnswers(
            $baiTapId,
            $input['answers']
        );

        return DB::transaction(function () use ($baiTapId, $studentId, $answers, $input) {
            // Serialize draft writes with submit and other draft writers.
            $attempt = DB::table('bai_kiem_tra_luot_lam')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $studentId)
                ->lockForUpdate()
                ->first();
            abort_unless($attempt, 409, 'Chưa có lượt làm bài đang hoạt động.');

            if ($this->alreadySubmitted($baiTapId, $studentId)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bạn đã nộp bài kiểm tra.',
                ], 409);
            }

            $query = DB::table('bai_lam_nhap')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $studentId);
            $existing = (clone $query)->lockForUpdate()->first();
            $actualRevision = $existing
                ? hash('sha256', (string) $existing->cau_tra_loi)
                : null;

            if ($input['baseDraftRevision'] !== $actualRevision) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bản nháp trên máy chủ đã thay đổi ở nơi khác. Hãy tải lại đề để đối chiếu trước khi lưu tiếp.',
                    'data' => ['draftRevision' => $actualRevision],
                ], 409);
            }

            $now = $this->nowVN();
            $storedAnswers = json_encode($answers, JSON_UNESCAPED_UNICODE);
            $values = [
                'cau_tra_loi' => $storedAnswers,
                'updated_at' => $now,
            ];

            if ($existing) {
                $query->update($values);
            } else {
                $query->insert($values + [
                    'bai_tap_id' => $baiTapId,
                    'sinh_vien_id' => $studentId,
                    'created_at' => $now,
                ]);
            }

            // Hash the exact value read back from the database. This is an
            // optimistic concurrency token and requires no schema migration.
            $persistedAnswers = (string) $query->value('cau_tra_loi');
            $persistedUpdatedAt = $query->value('updated_at');

            return response()->json([
                'status' => true,
                'message' => 'Đã lưu bài nháp.',
                'data' => [
                    'answers' => $answers,
                    'draftUpdatedAt' => $persistedUpdatedAt
                        ? $this->dateVN($persistedUpdatedAt)->toIso8601String()
                        : $now->toIso8601String(),
                    'draftRevision' => hash('sha256', $persistedAnswers),
                ],
            ]);
        }, 3);
    }

    /*
    |--------------------------------------------------------------------------
    | API POST: NỘP BÀI KIỂM TRA
    |--------------------------------------------------------------------------
    */


    public function submit(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        // 1. Kiểm tra dữ liệu gửi lên
        $input = $request->validate([
            // Empty answers are valid when a student submits without answering.
            'answers' => ['present', 'array'],
            'tuDongNop' => ['sometimes', 'boolean'],
        ]);

        // 2. Xác định Sinh viên và bài kiểm tra
        $studentId = $this->studentId($request);

        $assignment = $this->assignment(
            $baiTapId,
            $studentId
        );

        // 3. Kiểm tra giờ và trạng thái khóa
        $this->ensureStarted($assignment);

        // Sử dụng giờ Việt Nam giống lúc tạo lịch
        $timezone = 'Asia/Ho_Chi_Minh';

        $now = Carbon::now($timezone);
        $deadline = $this->deadline($assignment);

        $isLate = $now->greaterThan($deadline);

        // Dung sai 20 giây cho thao tác tự nộp
        $grace = !empty($input['tuDongNop'])
            && $now->lessThanOrEqualTo(
                $deadline->copy()->addSeconds(20)
            );

        if (
            $isLate &&
            !$assignment->cho_phep_nop_tre &&
            !$grace
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Bài kiểm tra đã hết hạn nộp.',
            ], 409);
        }

        // 4. Kiểm tra câu trả lời hợp lệ
        $answers = $this->validatedAnswers(
            $baiTapId,
            $input['answers']
        );

        $result = DB::transaction(function () use (
            $baiTapId,
            $studentId,
            $answers,
            $timezone,
            $isLate,
            $grace
        ) {
            // Khóa bài tập để hạn chế hai request nộp đồng thời
            $attempt = DB::table('bai_kiem_tra_luot_lam')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $studentId)
                ->lockForUpdate()
                ->first();
            abort_unless($attempt, 409, 'Chưa có lượt làm bài đang hoạt động.');

            // Kiểm tra lại trạng thái sau khi lấy khóa
            if (
                $this->alreadySubmitted(
                    $baiTapId,
                    $studentId
                )
            ) {
                throw ValidationException::withMessages([
                    'answers' => 'Bạn đã nộp bài kiểm tra này.',
                ]);
            }

            $submittedAt = Carbon::now($timezone);

            // Lấy câu hỏi và đáp án đúng trước khi ghi dữ liệu
            $questions = DB::table('cau_hoi')
                ->where('bai_tap_id', $baiTapId)
                ->get();

            if ($questions->isEmpty()) {
                throw ValidationException::withMessages([
                    'answers' => 'Bài kiểm tra chưa có câu hỏi.',
                ]);
            }

            $examTotalCents = $questions->sum(
                fn ($question) => ExamScoreAllocator::cents($question->diem)
            );
            abort_if(
                $examTotalCents !== 1000,
                422,
                'Đề thi chưa có tổng điểm hợp lệ bằng 10. Vui lòng liên hệ Giảng viên.'
            );

            // Không cho ghi câu trả lời vào câu hỏi ngoài đề
            $questionIds = $questions->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            $correctAnswers = DB::table('dap_an')
                ->whereIn('cau_hoi_id', $questionIds)
                ->where('la_dap_an_dung', true)
                ->get()
                ->groupBy('cau_hoi_id');

            /*
        |--------------------------------------------------------------------------
        | A. TÍNH ĐIỂM TRẮC NGHIỆM
        |--------------------------------------------------------------------------
        */

            $earnedPoints = 0.0;

            $hasEssay = false;
            $multipleChoiceCount = 0;
            $correctCount = 0;

            foreach ($questions as $question) {
                if ($question->loai_cau_hoi === 'tu_luan') {
                    $hasEssay = true;
                    continue;
                }

                if ($question->loai_cau_hoi !== 'trac_nghiem') {
                    throw ValidationException::withMessages([
                        'answers' =>
                        'Đề kiểm tra có loại câu hỏi không hỗ trợ.',
                    ]);
                }

                $multipleChoiceCount++;

                $selectedAnswerId = $answers[(string) $question->id] ?? null;

                $correctIds = (
                    $correctAnswers[$question->id] ?? collect()
                )->pluck('id')->all();

                if (
                    count($correctIds) === 1 &&
                    $selectedAnswerId !== null &&
                    (int) $selectedAnswerId ===
                    (int) $correctIds[0]
                ) {
                    $earnedPoints += (float) $question->diem;
                    $correctCount++;
                }
            }

            // Điểm trắc nghiệm đã được phân bổ trực tiếp trên thang 10.
            $multipleChoiceScore = round($earnedPoints, 2);

            /*
        |--------------------------------------------------------------------------
        | B. LƯU BÀI NỘP
        |--------------------------------------------------------------------------
        */

            // Chú ý: dùng duong_dan theo schema
            // của SinhVienKiemTraController gốc.
            $baiNopId = DB::table('bai_nop')->insertGetId([
                'bai_tap_id' => $baiTapId,
                'sinh_vien_id' => $this->studentUserId($studentId),

                'ten_file' => 'bai-lam-truc-tuyen',
                'duong_dan' => '',
                'loai_file' => 'online_exam',

                'thoi_gian_nop' => $submittedAt,

                'trang_thai' =>
                $isLate && !$grace
                    ? 'tre_han'
                    : 'dung_han',

                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ]);

            /*
        |--------------------------------------------------------------------------
        | C. LƯU TỪNG CÂU TRẢ LỜI
        |--------------------------------------------------------------------------
        */

            foreach ($answers as $questionId => $value) {
                if (
                    $value === null ||
                    (
                        is_string($value) &&
                        trim($value) === ''
                    )
                ) {
                    continue;
                }

                DB::table('cau_tra_loi')->insert([
                    'bai_nop_id' => $baiNopId,
                    'cau_hoi_id' => (int) $questionId,

                    'dap_an_id' =>
                    is_int($value) ? $value : null,

                    'noi_dung_tra_loi' =>
                    is_string($value) ? $value : '',

                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | D. TỰ ĐỘNG LƯU ĐIỂM NẾU KHÔNG CÓ TỰ LUẬN
        |--------------------------------------------------------------------------
        */

            $autoGraded = false;
            $finalScore = null;

            if (!$hasEssay) {
                $teacherId = DB::table('bai_tap as bt')
                    ->join(
                        'lop_hoc as lh',
                        'bt.lop_hoc_id',
                        '=',
                        'lh.id'
                    )
                    ->where('bt.id', $baiTapId)
                    ->value('lh.giang_vien_id');

                if (!$teacherId) {
                    throw new \RuntimeException(
                        'Không tìm thấy Giảng viên phụ trách.'
                    );
                }

                $finalScore = $multipleChoiceScore;

                DB::table('diem')->insert([
                    'bai_nop_id' => $baiNopId,
                    'nguoi_cham_id' => $teacherId,

                    'diem' => $finalScore,

                    'nhan_xet' =>
                    'Hệ thống tự chấm '
                        . $correctCount . '/'
                        . $multipleChoiceCount
                        . ' câu trắc nghiệm đúng.',

                    'ket_qua' =>
                    $finalScore >= 5
                        ? 'dat'
                        : 'khong_dat',

                    'thoi_gian_cham' => $submittedAt,
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]);

                $autoGraded = true;
            }

            /*
        |--------------------------------------------------------------------------
        | E. XÓA BÀI NHÁP SAU KHI NỘP
        |--------------------------------------------------------------------------
        */

            DB::table('bai_lam_nhap')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $studentId)
                ->delete();

            return [
                'baiNopId' => $baiNopId,
                'autoGraded' => $autoGraded,
                'hasEssay' => $hasEssay,
                'finalScore' => $finalScore,
                'multipleChoiceScore' => $multipleChoiceScore,
                'correctCount' => $correctCount,
                'multipleChoiceCount' => $multipleChoiceCount,
            ];
        }, 3);

        return response()->json([
            'status' => true,

            'message' => $result['autoGraded']
                ? 'Nộp bài và tự chấm trắc nghiệm thành công.'
                : 'Nộp bài thành công. '
                . 'Phần tự luận đang chờ Giảng viên chấm.',

            'data' => [
                'id' => $result['baiNopId'],
                'baiTapId' => $baiTapId,
                'daNop' => true,

                'daChamTuDong' => $result['autoGraded'],
                'canChamTuLuan' => $result['hasEssay'],

                'diem' => $result['finalScore'],

                'diemTracNghiemTamTinh' =>
                $result['multipleChoiceScore'],

                'soCauTracNghiemDung' =>
                $result['correctCount'],

                'tongCauTracNghiem' =>
                $result['multipleChoiceCount'],
            ],
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | API POST: GHI NHẬN RỜI TAB
    |--------------------------------------------------------------------------
    */

    public function reportTabLeave(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $data = $request->validate([
            'eventId' => ['required', 'uuid'],
        ]);

        $studentId = $this->studentId($request);
        $assignment = $this->assignment($baiTapId, $studentId);

        return DB::transaction(function () use ($baiTapId, $studentId, $data, $assignment) {
            // Dùng chung hàng khóa với API cảnh báo camera và API nộp bài.
            $attempt = DB::table('bai_kiem_tra_luot_lam')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $studentId)
                ->lockForUpdate()
                ->first();

            $state = $this->violationStateData($baiTapId, $studentId);
            $daNop = $this->alreadySubmitted($baiTapId, $studentId);
            $daBatDau = $attempt !== null;

            $query = DB::table('vi_pham_kiem_tra')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $studentId);

            $existing = (clone $query)
                ->where('event_id', $data['eventId'])
                ->exists();

            if ($daBatDau && !$daNop && !$state['biKhoa'] && !$existing) {
                $newCount = $state['soLanViPham'] + 1;
                DB::table('vi_pham_kiem_tra')->insert([
                    'bai_tap_id' => $baiTapId,
                    'sinh_vien_id' => $studentId,
                    'event_id' => $data['eventId'],
                    'lan_thu' => $newCount,
                    'loai_vi_pham' => 'roi_tab',
                    'created_at' => $this->nowVN(),
                    'updated_at' => $this->nowVN(),
                ]);
                $state = $this->violationStateData($baiTapId, $studentId);

                try {
                    $phien = DB::table('phien_giam_sat')
                        ->where('bai_tap_id', $baiTapId)
                        ->where('trang_thai', 'dang_dien_ra')
                        ->first();

                    if ($phien) {
                        $studentInfo = DB::table('sinh_vien as sv')
                            ->join('nguoi_dung as nd', 'nd.id', '=', 'sv.nguoi_dung_id')
                            ->where('sv.id', $studentId)
                            ->select('nd.ho_ten', 'sv.ma_sinh_vien')
                            ->first();
                        $className = DB::table('lop_hoc')->where('id', $assignment->lop_hoc_id)->value('ten_lop');
                        $alertEvent = new \App\Events\CanhBaoExamEvent(
                            phienId: $phien->id,
                            sinhVienId: $studentId,
                            loaiCanhBao: 'roi_tab',
                            noiDung: "Sinh viên đã rời tab lần {$newCount}",
                            thoiGian: now()->toISOString(),
                            soLanCamera: $state['soLanCamera'] ?? 0,
                            soLanDienThoai: $state['soLanDienThoai'] ?? 0,
                            soLanViPham: $state['soLanViPham'] ?? $newCount,
                            biKhoa: $state['biKhoa'] ?? false,
                            lyDoKhoa: $state['lyDoKhoa'] ?? null,
                            eventId: $data['eventId'],
                            hoTen: $studentInfo?->ho_ten,
                            maSinhVien: $studentInfo?->ma_sinh_vien,
                            tenLop: $className,
                            tenBaiKiemTra: $assignment->tieu_de,
                            baiTapId: $baiTapId
                        );
                        DB::afterCommit(function () use ($alertEvent) {
                            try {
                                broadcast($alertEvent)->toOthers();
                            } catch (\Throwable $exception) {
                                \Illuminate\Support\Facades\Log::warning('Khong the broadcast canh bao roi tab sau commit.', [
                                    'event_id' => $alertEvent->eventId,
                                    'exception' => $exception->getMessage(),
                                ]);
                            }
                        });
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Không thể broadcast cảnh báo rời tab: ' . $e->getMessage());
                }
            }

            $message = match ($state['lyDoKhoa']) {
                'dien_thoai' => 'Bài kiểm tra đã bị khóa do phát hiện điện thoại 3 lần.',
                'camera' => 'Bài kiểm tra đã bị khóa do 5 cảnh báo camera khác.',
                'roi_tab' => 'Bài kiểm tra đã bị khóa do rời tab 3 lần.',
                default => "Bạn đã rời tab {$state['soLanViPham']}/3 lần.",
            };

            // Giữ các trường ở cấp gốc cho Frontend cũ,
            // đồng thời thêm data cho các màn hình mới.
            return response()->json(array_merge([
                'status' => true,
                'message' => $message,
                'daNop' => $daNop,
                'daBatDau' => $daBatDau,
                'trangThaiLamBai' => $daNop ? 'SUBMITTED' : ($daBatDau ? 'IN_PROGRESS' : 'PRE_EXAM'),
                'data' => array_merge($state, [
                    'daNop' => $daNop,
                    'daBatDau' => $daBatDau,
                    'trangThaiLamBai' => $daNop ? 'SUBMITTED' : ($daBatDau ? 'IN_PROGRESS' : 'PRE_EXAM'),
                ]),
            ], $state));
        }, 3);
    }

    /*
    |--------------------------------------------------------------------------
    | API GET: TRẠNG THÁI VI PHẠM
    |--------------------------------------------------------------------------
    */


    public function examViolationState(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $sinhVienId = $this->studentId($request);
        $assignment = $this->assignment($baiTapId, $sinhVienId);

        $state = $this->violationStateData($baiTapId, $sinhVienId);
        $state['daNop'] = $this->alreadySubmitted($baiTapId, $sinhVienId);

        $activeSession = DB::table('phien_giam_sat')
            ->where('trang_thai', 'dang_dien_ra')
            ->where(function ($q) use ($baiTapId, $assignment) {
                $q->where('bai_tap_id', $baiTapId)
                  ->orWhere('lop_hoc_id', $assignment->lop_hoc_id);
            })
            ->orderByDesc('id')
            ->first(['id', 'ten_phien', 'trang_thai']);

        $state['phienGiamSat'] = $activeSession ? [
            'id' => $activeSession->id,
            'tenPhien' => $activeSession->ten_phien,
            'trangThai' => $activeSession->trang_thai,
        ] : null;
        $state['sinhVienId'] = $sinhVienId;
        $state['hinhThucKiemTra'] = ExamMode::forAssignment($baiTapId);
        $state['yeuCauCamera'] = ExamMode::requiresCamera($baiTapId);
        $state['trangThaiLamBai'] = $state['daNop']
            ? 'SUBMITTED'
            : (DB::table('bai_kiem_tra_luot_lam')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $sinhVienId)
                ->exists()
                    ? 'IN_PROGRESS'
                    : 'PRE_EXAM');

        return response()->json([
            'status' => true,
            'data' => $state,
        ]);
    }
}
