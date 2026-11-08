<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA SINH VIÊN THUỘC LỚP
    |--------------------------------------------------------------------------
    */

    private function assignment(
        int $baiTapId,
        int $studentId
    ): object {
        $assignment = DB::table('bai_tap as bt')
            ->where('bt.id', $baiTapId)
            ->whereExists(function ($query) use ($studentId) {
                $query->selectRaw('1')
                    ->from('thanh_vien_lop as tv')
                    ->whereColumn(
                        'tv.lop_hoc_id',
                        'bt.lop_hoc_id'
                    )
                    ->where(
                        'tv.sinh_vien_id',
                        $studentId
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
            ->where('sinh_vien_id', $studentId)
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

        $reason = $phoneCount >= 3
            ? 'dien_thoai'
            : ($otherCameraCount >= 5
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

    private function questions(int $baiTapId): array
    {
        $questions = DB::table('cau_hoi')
            ->where('bai_tap_id', $baiTapId)
            ->orderBy('thu_tu')
            ->orderBy('id')
            ->get();

        $answerRows = DB::table('dap_an')
            ->whereIn(
                'cau_hoi_id',
                $questions->pluck('id')
            )
            ->orderBy('thu_tu')
            ->orderBy('id')
            ->get([
                'id',
                'cau_hoi_id',
                'noi_dung',
                'thu_tu',
            ])
            ->groupBy('cau_hoi_id');

        return $questions->map(function ($question) use (
            $answerRows
        ) {
            return [
                'id' => $question->id,
                'noi_dung' => $question->noi_dung,
                'loai_cau_hoi' => $question->loai_cau_hoi,
                'diem' => $question->diem,

                // Không trả đáp án đúng cho Sinh viên.
                'dap_an' => (
                    $answerRows[$question->id] ?? collect()
                )->map(function ($answer) {
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

        $questions = $this->questions($baiTapId);

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

                'phienGiamSat' => $activeSession ? [
                    'id' => $activeSession->id,
                    'tenPhien' => $activeSession->ten_phien,
                    'trangThai' => $activeSession->trang_thai,
                ] : null,
                'sinhVienId' => $studentId,
            ],
        ]);
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
                'required',
                'array',
            ],
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

        $now = $this->nowVN();

        DB::table('bai_lam_nhap')->updateOrInsert(
            [
                'bai_tap_id' => $baiTapId,
                'sinh_vien_id' => $studentId,
            ],
            [
                'cau_tra_loi' => json_encode(
                    $answers,
                    JSON_UNESCAPED_UNICODE
                ),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Đã lưu bài nháp.',
        ]);
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
            'answers' => ['required', 'array'],
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

        $this->assertExamNotLocked(
            $baiTapId,
            $studentId
        );

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
            DB::table('bai_tap')
                ->where('id', $baiTapId)
                ->lockForUpdate()
                ->first();

            // Kiểm tra lại trạng thái sau khi lấy khóa
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

            $totalPoints = (float) $questions->sum('diem');
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

            // Điểm trắc nghiệm được quy đổi
            // theo tổng điểm toàn bài (thang 10).
            $multipleChoiceScore = $totalPoints > 0
                ? round(
                    10 * $earnedPoints / $totalPoints,
                    2
                )
                : 0.0;

            /*
        |--------------------------------------------------------------------------
        | B. LƯU BÀI NỘP
        |--------------------------------------------------------------------------
        */

            // Chú ý: dùng duong_dan theo schema
            // của SinhVienKiemTraController gốc.
            $baiNopId = DB::table('bai_nop')->insertGetId([
                'bai_tap_id' => $baiTapId,
                'sinh_vien_id' => $studentId,

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
        $this->assignment($baiTapId, $studentId);

        return DB::transaction(function () use ($baiTapId, $studentId, $data) {
            // Dùng chung hàng khóa với API cảnh báo camera và API nộp bài.
            DB::table('bai_tap')
                ->where('id', $baiTapId)
                ->lockForUpdate()
                ->first();

            $state = $this->violationStateData($baiTapId, $studentId);
            $daNop = $this->alreadySubmitted($baiTapId, $studentId);

            $query = DB::table('vi_pham_kiem_tra')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $studentId);

            $existing = (clone $query)
                ->where('event_id', $data['eventId'])
                ->exists();

            if (!$daNop && !$state['biKhoa'] && !$existing) {
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
                        broadcast(new \App\Events\CanhBaoExamEvent(
                            phienId: $phien->id,
                            sinhVienId: $studentId,
                            loaiCanhBao: 'roi_tab',
                            noiDung: "Sinh viên đã rời tab lần {$newCount}",
                            thoiGian: now()->toISOString(),
                            soLanCamera: $state['soLanCamera'] ?? 0,
                            soLanDienThoai: $state['soLanDienThoai'] ?? 0,
                            soLanViPham: $state['soLanViPham'] ?? $newCount,
                            biKhoa: $state['biKhoa'] ?? false,
                            lyDoKhoa: $state['lyDoKhoa'] ?? null
                        ))->toOthers();
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
                'data' => array_merge($state, ['daNop' => $daNop]),
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

        return response()->json([
            'status' => true,
            'data' => $state,
        ]);
    }
}
