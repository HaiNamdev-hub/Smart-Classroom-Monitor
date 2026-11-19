<?php

namespace App\Http\Controllers;

use App\Events\ExamProtectionAlertEvent;
use App\Support\ExamMode;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SinhVienBaoVeBaiKiemTraController extends Controller
{
    private const EVENT_DESCRIPTIONS = [
        'copy_blocked' => 'Thao tác sao chép nội dung câu hỏi/đáp án đã bị trình duyệt chặn.',
        'cut_blocked' => 'Thao tác cắt nội dung câu hỏi/đáp án đã bị trình duyệt chặn.',
        'select_all_blocked' => 'Thao tác chọn toàn bộ nội dung câu hỏi/đáp án đã bị trình duyệt chặn.',
        'context_menu_blocked' => 'Menu chuột phải trong vùng câu hỏi/đáp án đã bị chặn.',
        'drag_blocked' => 'Thao tác kéo nội dung câu hỏi/đáp án đã bị chặn.',
        'screenshot_shortcut_key' => 'Trình duyệt nhận được phím/tổ hợp phím thường dùng để chụp màn hình; hệ thống không xác nhận ảnh đã được chụp.',
        'SCREENSHOT_SHORTCUT_DETECTED' => 'Trình duyệt nhận được tín hiệu phím có thể liên quan đến chụp màn hình; hệ thống không xác nhận ảnh đã được chụp.',
        'focus_lost' => 'Cửa sổ bài thi mất focus khi tab vẫn hiển thị; đây chỉ là tín hiệu cần xem xét, không xác nhận gian lận.',
        'fullscreen_exit' => 'Sinh viên đã thoát chế độ toàn màn hình trong lúc làm bài.',
    ];

    public function store(Request $request, int $baiTapId): JsonResponse
    {
        $data = $request->validate([
            'eventId' => ['required', 'uuid'],
            'eventType' => ['required', 'string', Rule::in(array_keys(self::EVENT_DESCRIPTIONS))],
            'occurredAt' => ['nullable', 'date'],
            'signal' => [
                'required_if:eventType,SCREENSHOT_SHORTCUT_DETECTED',
                'nullable',
                'string',
                Rule::in(['PRINT_SCREEN', 'META_SHIFT_S', 'META_SHIFT_3', 'META_SHIFT_4', 'META_SHIFT_5']),
            ],
            'source' => [
                'required_if:eventType,SCREENSHOT_SHORTCUT_DETECTED',
                'nullable',
                'string',
                Rule::in(['keydown', 'keyup']),
            ],
        ]);

        $userId = (int) $request->user()->id;
        $student = DB::table('sinh_vien')->where('nguoi_dung_id', $userId)->first();
        abort_unless($student, 403, 'Tài khoản không phải Sinh viên.');

        $result = DB::transaction(function () use ($baiTapId, $userId, $student, $data) {
            $assignment = DB::table('bai_tap as bt')
                ->join('lop_hoc as lh', 'lh.id', '=', 'bt.lop_hoc_id')
                ->join('thanh_vien_lop as tv', function ($join) use ($userId) {
                    $join->on('tv.lop_hoc_id', '=', 'bt.lop_hoc_id')
                        ->where('tv.sinh_vien_id', '=', $userId)
                        ->where('tv.trang_thai', '=', 'dang_hoc');
                })
                ->where('bt.id', $baiTapId)
                ->lockForUpdate()
                ->select('bt.*', 'lh.ten_lop')
                ->first();

            abort_unless($assignment, 403, 'Sinh viên không thuộc lớp của bài kiểm tra.');

            abort_unless(
                DB::table('bai_kiem_tra_luot_lam')
                    ->where('bai_tap_id', $baiTapId)
                    ->where('sinh_vien_id', $student->id)
                    ->exists(),
                409,
                'Bài kiểm tra chưa được bắt đầu.'
            );

            $now = Carbon::now('Asia/Ho_Chi_Minh');
            $start = Carbon::parse($assignment->thoi_gian_bat_dau, 'Asia/Ho_Chi_Minh');
            $deadline = Carbon::parse($assignment->han_nop, 'Asia/Ho_Chi_Minh');
            $examEnd = Carbon::parse($assignment->thoi_gian_ket_thuc, 'Asia/Ho_Chi_Minh');
            if ($examEnd->lt($deadline)) $deadline = $examEnd;

            abort_if($now->lt($start) || !$deadline || $now->gt($deadline), 403, 'Bài kiểm tra không trong thời gian làm bài.');
            abort_if(DB::table('bai_nop')->where('bai_tap_id', $baiTapId)->where('sinh_vien_id', $userId)->exists(), 403, 'Bài kiểm tra đã được nộp.');

            $tabCount = DB::table('vi_pham_kiem_tra')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $student->id)
                ->count();
            $cameraQuery = DB::table('canh_bao_camera')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $student->id);
            $phoneCount = (clone $cameraQuery)->where('loai_canh_bao', 'phat_hien_dien_thoai')->count();
            $otherCameraCount = (clone $cameraQuery)->where('loai_canh_bao', '<>', 'phat_hien_dien_thoai')->count();
            $cameraRequired = ExamMode::requiresCamera($baiTapId);
            abort_if($tabCount >= 3 || ($cameraRequired && ($phoneCount >= 3 || $otherCameraCount >= 5)), 403, 'Bài kiểm tra đã bị khóa.');

            $existing = DB::table('su_kien_bao_ve_bai_kiem_tra')
                ->where('event_id', $data['eventId'])
                ->first();
            if ($existing) {
                abort_unless(
                    (int) $existing->bai_tap_id === $baiTapId &&
                    (int) $existing->sinh_vien_id === (int) $student->id,
                    409,
                    'Mã sự kiện đã được sử dụng.'
                );

                return ['duplicate' => true, 'session' => null, 'student' => null];
            }

            $session = DB::table('phien_giam_sat')
                ->where('bai_tap_id', $baiTapId)
                ->where('lop_hoc_id', $assignment->lop_hoc_id)
                ->where('trang_thai', 'dang_dien_ra')
                ->orderByDesc('id')
                ->first();

            if ($data['eventType'] === 'SCREENSHOT_SHORTCUT_DETECTED') {
                abort_unless($session, 409, 'Không có phiên giám sát đang diễn ra cho bài kiểm tra này.');

                $recentScreenshot = DB::table('su_kien_bao_ve_bai_kiem_tra')
                    ->where('bai_tap_id', $baiTapId)
                    ->where('sinh_vien_id', $student->id)
                    ->where('loai_su_kien', 'SCREENSHOT_SHORTCUT_DETECTED')
                    ->where('thoi_gian_su_kien', '>=', now('UTC')->subSeconds(2))
                    ->exists();

                if ($recentScreenshot) {
                    return ['duplicate' => false, 'rateLimited' => true, 'session' => null, 'student' => null];
                }
            }

            $clientOccurredAt = isset($data['occurredAt'])
                ? Carbon::parse($data['occurredAt'])->utc()
                : null;
            $recordedAt = now('UTC');
            $signalInfo = $data['eventType'] === 'SCREENSHOT_SHORTCUT_DETECTED'
                ? ['signal' => $data['signal'], 'source' => $data['source']]
                : null;

            DB::table('su_kien_bao_ve_bai_kiem_tra')->insert([
                'event_id' => $data['eventId'],
                'bai_tap_id' => $baiTapId,
                'sinh_vien_id' => $student->id,
                'phien_id' => $session?->id,
                'loai_su_kien' => $data['eventType'],
                'thong_tin_tin_hieu' => $signalInfo ? json_encode($signalInfo, JSON_UNESCAPED_UNICODE) : null,
                'thoi_gian_su_kien' => $recordedAt,
                'thoi_gian_trinh_duyet' => $clientOccurredAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'duplicate' => false,
                'session' => $session,
                'student' => DB::table('sinh_vien as sv')
                    ->join('nguoi_dung as nd', 'nd.id', '=', 'sv.nguoi_dung_id')
                    ->where('sv.id', $student->id)
                    ->select('nd.ho_ten', 'sv.ma_sinh_vien')
                    ->first(),
                'assignment' => $assignment,
                'recordedAt' => $recordedAt,
                'signalInfo' => $signalInfo,
            ];
        }, 3);

        if (!$result['duplicate'] && !($result['rateLimited'] ?? false) && $result['session'] && $result['student']) {
            try {
                $signalInfo = $result['signalInfo'] ?? null;
                $signalName = $signalInfo['signal'] ?? null;
                $signalLabel = $signalName === 'PRINT_SCREEN'
                    ? 'phím Print Screen'
                    : ($signalName ? 'tổ hợp ' . str_replace('_', '+', $signalName) : null);
                $description = $data['eventType'] === 'SCREENSHOT_SHORTCUT_DETECTED'
                    ? sprintf(
                        'Sinh viên %s (%s) đã nhấn %s trong lúc làm bài. Hệ thống không thể xác nhận ảnh chụp thực tế.',
                        $result['student']->ho_ten,
                        $result['student']->ma_sinh_vien ?: 'chưa có MSSV',
                        $signalLabel ?: 'phím tắt nghi vấn'
                    )
                    : self::EVENT_DESCRIPTIONS[$data['eventType']];

                broadcast(new ExamProtectionAlertEvent(
                    eventId: $data['eventId'],
                    phienId: (int) $result['session']->id,
                    baiTapId: $baiTapId,
                    sinhVienId: (int) $student->id,
                    loaiSuKien: $data['eventType'],
                    noiDung: $description,
                    thoiGian: $result['recordedAt']->toIso8601String(),
                    hoTen: $result['student']->ho_ten,
                    maSinhVien: $result['student']->ma_sinh_vien,
                    tenLop: $result['assignment']->ten_lop,
                    tenBaiKiemTra: $result['assignment']->tieu_de,
                    signalInfo: $signalInfo,
                ));
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::warning(
                    'Unable to broadcast exam protection event.',
                    ['event_id' => $data['eventId'], 'exception' => $exception->getMessage()]
                );
            }
        }

        $rateLimited = $result['rateLimited'] ?? false;

        return response()->json([
            'status' => true,
            'data' => [
                'eventId' => $data['eventId'],
                'accepted' => !$rateLimited,
                'duplicate' => $result['duplicate'],
                'rateLimited' => $rateLimited,
                'recordedAs' => 'needs_review',
                'countsAsViolation' => false,
            ],
        ], ($result['duplicate'] || $rateLimited) ? 200 : 201);
    }
}
