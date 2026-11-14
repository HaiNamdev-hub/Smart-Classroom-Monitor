<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SinhVienCanhBaoCameraController extends Controller
{
    private const MAX_PHONE_WARNINGS = 3;
    private const MAX_CAMERA_WARNINGS = 5;
    private const MAX_TAB_WARNINGS = 3;

    /**
     * Sinh viên gửi cảnh báo từ camera.
     *
     * POST /api/sinh-vien/bai-kiem-tra/{baiTapId}/canh-bao-camera
     */
    public function store(
        Request $request,
        int $baiTapId
    ): JsonResponse {

        // ==========================================
        // 1. KIỂM TRA DỮ LIỆU ĐẦU VÀO
        // ==========================================

        $data = $request->validate([
            'eventId' => [
                'required',
                'uuid',
            ],

            'loaiCanhBao' => [
                'required',
                Rule::in([
                    'khong_co_khuon_mat',
                    'nhieu_khuon_mat',
                    'camera_ngat',
                    'phat_hien_dien_thoai',
                ]),
            ],
        ]);

        // ==========================================
        // 2. XÁC ĐỊNH SINH VIÊN ĐĂNG NHẬP
        // ==========================================

        $sinhVienId = DB::table('sinh_vien')
            ->where(
                'nguoi_dung_id',
                $request->user()->id
            )
            ->value('id');

        abort_unless(
            $sinhVienId,
            403,
            'Tài khoản không phải sinh viên.'
        );

        $sinhVienId = (int) $sinhVienId;

        // ==========================================
        // 3. KIỂM TRA QUYỀN THAM GIA BÀI KIỂM TRA
        // ==========================================

        $baiTap = DB::table('bai_tap as bt')
            ->join(
                'thanh_vien_lop as tv',
                'tv.lop_hoc_id',
                '=',
                'bt.lop_hoc_id'
            )
            ->where('bt.id', $baiTapId)
            ->where(
                'tv.sinh_vien_id',
                (int) $request->user()->id
            )
            ->where(
                'tv.trang_thai',
                'dang_hoc'
            )
            ->select('bt.*')
            ->first();

        abort_unless(
            $baiTap,
            403,
            'Bạn không thuộc lớp của bài kiểm tra.'
        );

        // ==========================================
        // 4. XỬ LÝ CẢNH BÁO TRONG TRANSACTION
        // ==========================================

        return DB::transaction(function () use (
            $data,
            $baiTap,
            $baiTapId,
            $sinhVienId
        ) {

            // Khóa hàng bài tập để tuần tự hóa
            // các yêu cầu cảnh báo đồng thời.
            DB::table('bai_tap')
                ->where('id', $baiTapId)
                ->lockForUpdate()
                ->first();

            // ======================================
            // 5. ĐẾM CÁC LOẠI VI PHẠM
            // ======================================

            $cameraQuery = DB::table(
                'canh_bao_camera'
            )
                ->where(
                    'bai_tap_id',
                    $baiTapId
                )
                ->where(
                    'sinh_vien_id',
                    $sinhVienId
                );

            // Chỉ đếm cảnh báo phát hiện điện thoại.
            $phoneCount = (clone $cameraQuery)
                ->where(
                    'loai_canh_bao',
                    'phat_hien_dien_thoai'
                )
                ->count();

            // Các cảnh báo camera khác,
            // KHÔNG tính cảnh báo điện thoại.
            $otherCameraCount = (clone $cameraQuery)
                ->where(
                    'loai_canh_bao',
                    '!=',
                    'phat_hien_dien_thoai'
                )
                ->count();

            // Đếm số lần rời tab.
            $tabCount = DB::table(
                'vi_pham_kiem_tra'
            )
                ->where(
                    'bai_tap_id',
                    $baiTapId
                )
                ->where(
                    'sinh_vien_id',
                    $sinhVienId
                )
                ->count();

            // ======================================
            // 6. KIỂM TRA BÀI ĐÃ NỘP CHƯA
            // ======================================

            $daNop = DB::table('bai_nop')
                ->where(
                    'bai_tap_id',
                    $baiTapId
                )
                ->where(
                    'sinh_vien_id',
                    $sinhVienId
                )
                ->exists();

            // ======================================
            // 7. KIỂM TRA EVENT ID ĐÃ TỒN TẠI
            // ======================================

            // Mỗi eventId chỉ được ghi nhận một lần.
            $existingAlert = (clone $cameraQuery)
                ->where(
                    'event_id',
                    $data['eventId']
                )
                ->first();

            if ($existingAlert) {
                abort_if(
                    $existingAlert->loai_canh_bao !== $data['loaiCanhBao'],
                    409,
                    'eventId đã được dùng cho một loại cảnh báo khác.'
                );
                return $this->responseState(
                    $otherCameraCount,
                    $phoneCount,
                    $tabCount,
                    $daNop,
                    true,
                    (int) $existingAlert->id,
                    $data['eventId'],
                    $existingAlert->evidence_status ?? 'none'
                );
            }

            // ======================================
            // 8. NẾU ĐÃ KHÓA HOẶC ĐÃ NỘP
            //    THÌ KHÔNG GHI THÊM
            // ======================================

            if (
                $daNop ||
                $phoneCount >= self::MAX_PHONE_WARNINGS ||
                $otherCameraCount >= self::MAX_CAMERA_WARNINGS ||
                $tabCount >= self::MAX_TAB_WARNINGS
            ) {
                return $this->responseState(
                    $otherCameraCount,
                    $phoneCount,
                    $tabCount,
                    $daNop,
                    false,
                    null,
                    $data['eventId'],
                    'none'
                );
            }

            // Nếu eventId đã tồn tại,
            // trả lại bộ đếm hiện tại.
            // ======================================
            // 9. KIỂM TRA THỜI GIAN THI
            // ======================================

            $timezone = 'Asia/Ho_Chi_Minh';
            $now = Carbon::now($timezone);

            $startValue =
                $baiTap->thoi_gian_bat_dau ?? null;

            $endValue =
                $baiTap->thoi_gian_ket_thuc ?? null;

            $dueValue =
                $baiTap->han_nop ?? null;

            abort_if(
                empty($startValue),
                409,
                'Bài kiểm tra chưa có thời gian bắt đầu.'
            );

            abort_if(
                empty($endValue) && empty($dueValue),
                409,
                'Bài kiểm tra chưa có thời gian kết thúc.'
            );

            $start = Carbon::parse(
                $startValue,
                $timezone
            );

            // Chọn thời hạn sớm nhất
            // nếu có cả kết thúc và hạn nộp.
            $deadlines = [];

            if (!empty($endValue)) {
                $deadlines[] = Carbon::parse(
                    $endValue,
                    $timezone
                );
            }

            if (!empty($dueValue)) {
                $deadlines[] = Carbon::parse(
                    $dueValue,
                    $timezone
                );
            }

            $deadline = $deadlines[0];

            foreach ($deadlines as $value) {
                if ($value->lt($deadline)) {
                    $deadline = $value;
                }
            }

            abort_if(
                $now->lt($start),
                409,
                'Bài kiểm tra chưa bắt đầu.'
            );

            abort_if(
                $now->gt($deadline),
                409,
                'Bài kiểm tra đã hết thời gian.'
            );

            // ======================================
            // 10. LƯU CẢNH BÁO VÀO MYSQL
            // ======================================

            $alertId = DB::table('canh_bao_camera')->insertGetId([
                'bai_tap_id' => $baiTapId,
                'sinh_vien_id' => $sinhVienId,

                'event_id' => $data['eventId'],

                'loai_canh_bao' =>
                    $data['loaiCanhBao'],

                'evidence_status' => $data['loaiCanhBao'] === 'camera_ngat'
                    ? 'not_applicable'
                    : 'pending',

                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ======================================
            // 11. TĂNG ĐÚNG BỘ ĐẾM VI PHẠM
            // ======================================

            if (
                $data['loaiCanhBao'] ===
                'phat_hien_dien_thoai'
            ) {

                // Phát hiện điện thoại:
                // tăng bộ đếm riêng /3.
                $phoneCount++;

            } else {

                // Các vi phạm camera khác:
                // tăng bộ đếm /5.
                $otherCameraCount++;
            }

            // ======================================
            // 11b. BROADCAST CẢNH BÁO CHO GIẢNG VIÊN
            // ======================================
            try {
                $phien = DB::table('phien_giam_sat')
                    ->where('bai_tap_id', $baiTapId)
                    ->where('trang_thai', 'dang_dien_ra')
                    ->first();

                if ($phien) {
                    $studentInfo = DB::table('sinh_vien as sv')
                        ->join('nguoi_dung as nd', 'nd.id', '=', 'sv.nguoi_dung_id')
                        ->where('sv.id', $sinhVienId)
                        ->select('nd.ho_ten', 'sv.ma_sinh_vien')
                        ->first();
                    $className = DB::table('lop_hoc')->where('id', $baiTap->lop_hoc_id)->value('ten_lop');
                    $tenLoai = [
                        'khong_co_khuon_mat' => 'Không phát hiện khuôn mặt',
                        'nhieu_khuon_mat' => 'Phát hiện nhiều khuôn mặt',
                        'camera_ngat' => 'Camera bị ngắt',
                        'phat_hien_dien_thoai' => 'Phát hiện điện thoại',
                    ];

                    $isLocked = ($phoneCount >= self::MAX_PHONE_WARNINGS)
                        || ($otherCameraCount >= self::MAX_CAMERA_WARNINGS)
                        || ($tabCount >= self::MAX_TAB_WARNINGS);

                    $lockReason = $phoneCount >= self::MAX_PHONE_WARNINGS
                        ? 'dien_thoai'
                        : ($otherCameraCount >= self::MAX_CAMERA_WARNINGS
                            ? 'camera'
                            : ($tabCount >= self::MAX_TAB_WARNINGS ? 'roi_tab' : null));

                    $alertEvent = new \App\Events\CanhBaoExamEvent(
                        phienId: $phien->id,
                        sinhVienId: $sinhVienId,
                        loaiCanhBao: $data['loaiCanhBao'],
                        noiDung: $tenLoai[$data['loaiCanhBao']] ?? 'Cảnh báo camera',
                        thoiGian: now()->toISOString(),
                        soLanCamera: $otherCameraCount,
                        soLanDienThoai: $phoneCount,
                        soLanViPham: $tabCount,
                        biKhoa: $isLocked,
                        lyDoKhoa: $lockReason,
                        eventId: $data['eventId'],
                        hoTen: $studentInfo?->ho_ten,
                        maSinhVien: $studentInfo?->ma_sinh_vien,
                        tenLop: $className,
                        tenBaiKiemTra: $baiTap->tieu_de,
                        baiTapId: $baiTapId
                    );
                    DB::afterCommit(function () use ($alertEvent) {
                        try {
                            broadcast($alertEvent)->toOthers();
                        } catch (\Throwable $exception) {
                            \Illuminate\Support\Facades\Log::warning('Khong the broadcast canh bao camera sau commit.', [
                                'event_id' => $alertEvent->eventId,
                                'exception' => $exception->getMessage(),
                            ]);
                        }
                    });
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Không thể broadcast cảnh báo camera: ' . $e->getMessage());
            }

            // ======================================
            // 12. TRẢ VỀ TRẠNG THÁI KHÓA
            // ======================================

            return $this->responseState(
                $otherCameraCount,
                $phoneCount,
                $tabCount,
                false,
                true,
                (int) $alertId,
                $data['eventId'],
                $data['loaiCanhBao'] === 'camera_ngat' ? 'not_applicable' : 'pending'
            );
        });
    }

    /**
     * Trả về số lần cảnh báo và trạng thái khóa.
     */
    private function responseState(
        int $otherCameraCount,
        int $phoneCount,
        int $tabCount,
        bool $daNop,
        bool $eventAccepted = false,
        ?int $alertId = null,
        ?string $eventId = null,
        string $evidenceStatus = 'none'
    ): JsonResponse {

        // ======================================
        // XÁC ĐỊNH ĐÃ KHÓA BÀI CHƯA
        // ======================================

        $phoneLocked =
            $phoneCount >= self::MAX_PHONE_WARNINGS;

        $cameraLocked =
            $otherCameraCount >= self::MAX_CAMERA_WARNINGS;

        $tabLocked =
            $tabCount >= self::MAX_TAB_WARNINGS;

        $biKhoa =
            $phoneLocked ||
            $cameraLocked ||
            $tabLocked;

        // ======================================
        // XÁC ĐỊNH LÝ DO KHÓA
        // ======================================

        $lyDoKhoa = null;

        if ($phoneLocked) {

            $lyDoKhoa = 'dien_thoai';

        } elseif ($cameraLocked) {

            $lyDoKhoa = 'camera';

        } elseif ($tabLocked) {

            $lyDoKhoa = 'roi_tab';
        }

        // ======================================
        // TRẢ VỀ JSON CHO VUE
        // ======================================

        return response()->json([
            'status' => true,

            'data' => [

                // Camera: tối đa 5 lần
                'soLanCamera' =>
                    $otherCameraCount,

                // Điện thoại: tối đa 3 lần
                'soLanDienThoai' =>
                    $phoneCount,

                // Rời tab: tối đa 3 lần
                'soLanViPham' =>
                    $tabCount,

                // Số lượt còn lại
                'soLanCameraConLai' => max(
                    0,
                    self::MAX_CAMERA_WARNINGS -
                    $otherCameraCount
                ),

                'soLanDienThoaiConLai' => max(
                    0,
                    self::MAX_PHONE_WARNINGS -
                    $phoneCount
                ),

                'soLanConLai' => max(
                    0,
                    self::MAX_TAB_WARNINGS -
                    $tabCount
                ),

                // Trạng thái bài kiểm tra
                'biKhoa' => $biKhoa,
                'daNop' => $daNop,

                // camera / dien_thoai / roi_tab
                'lyDoKhoa' => $lyDoKhoa,
                'eventAccepted' => $eventAccepted,
                'alertId' => $alertId,
                'eventId' => $eventId,
                'evidenceStatus' => $evidenceStatus,
            ],
        ]);
    }

    public function storeEvidence(Request $request, int $baiTapId, string $eventId): JsonResponse
    {
        $request->validate([
            'eventId' => ['required', 'uuid', 'in:' . $eventId],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,webp', 'max:512'],
        ]);

        $userId = (int) $request->user()->id;
        $student = DB::table('sinh_vien')->where('nguoi_dung_id', $userId)->first();
        abort_unless($student, 403, 'Tài khoản không phải sinh viên.');

        $membership = DB::table('bai_tap as bt')
            ->join('thanh_vien_lop as tv', 'tv.lop_hoc_id', '=', 'bt.lop_hoc_id')
            ->where('bt.id', $baiTapId)
            // Membership rows use the account ID; the alert table uses the profile ID.
            ->where('tv.sinh_vien_id', $userId)
            ->where('tv.trang_thai', 'dang_hoc')
            ->exists();
        abort_unless($membership, 403, 'Bạn không có quyền gửi ảnh cho bài kiểm tra này.');

        $alert = DB::table('canh_bao_camera')
            ->where('bai_tap_id', $baiTapId)
            ->where('sinh_vien_id', $student->id)
            ->where('event_id', $eventId)
            ->first();
        abort_unless($alert, 404, 'Không tìm thấy cảnh báo đã được Backend chấp nhận.');
        abort_if($alert->loai_canh_bao === 'camera_ngat', 422, 'Camera đã ngắt; không thể dùng ảnh cũ làm bằng chứng.');

        $existing = DB::table('anh_man_hinh')->where('event_id', $eventId)->first();
        if ($existing && Storage::disk($existing->storage_disk)->exists($existing->duong_dan)) {
            return response()->json(['status' => true, 'data' => ['eventId' => $eventId, 'imageId' => $existing->id, 'evidenceStatus' => 'available']]);
        }

        $file = $request->file('image');
        $extension = $file->getMimeType() === 'image/webp' ? 'webp' : 'jpg';
        $path = "exam-evidence/{$baiTapId}/{$student->id}/{$eventId}.{$extension}";
        $stored = Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
        abort_unless($stored, 500, 'Không thể lưu ảnh bằng chứng.');

        try {
            $imageId = DB::transaction(function () use ($alert, $baiTapId, $student, $userId, $eventId, $path, $file) {
                $existing = DB::table('anh_man_hinh')->where('event_id', $eventId)->first();
                if ($existing) {
                    DB::table('anh_man_hinh')->where('id', $existing->id)->update([
                        'duong_dan' => $path,
                        'storage_disk' => 'local',
                        'mime_type' => $file->getMimeType(),
                        'kich_thuoc_bytes' => $file->getSize(),
                        'thoi_gian_chup' => $existing->thoi_gian_chup,
                        'expires_at' => $existing->expires_at ?: now()->addDays(90),
                    ]);
                    DB::table('canh_bao_camera')->where('id', $alert->id)->update(['evidence_status' => 'available', 'updated_at' => now()]);
                    return (int) $existing->id;
                }

                $sessionId = DB::table('phien_giam_sat')
                    ->where('bai_tap_id', $baiTapId)
                    ->where('trang_thai', 'dang_dien_ra')
                    ->orderByDesc('id')
                    ->value('id');
                $id = DB::table('anh_man_hinh')->insertGetId([
                    'phien_id' => $sessionId,
                    'sinh_vien_id' => $userId,
                    'bai_tap_id' => $baiTapId,
                    'event_id' => $eventId,
                    'nguon' => 'webcam_ai',
                    'loai_canh_bao' => $alert->loai_canh_bao,
                    'duong_dan' => $path,
                    'storage_disk' => 'local',
                    'mime_type' => $file->getMimeType(),
                    'kich_thuoc_bytes' => $file->getSize(),
                    'thoi_gian_chup' => $alert->created_at,
                    'expires_at' => now()->addDays(90),
                    'created_at' => now(),
                ]);
                DB::table('canh_bao_camera')->where('id', $alert->id)->update(['evidence_status' => 'available', 'updated_at' => now()]);
                return $id;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json(['status' => true, 'data' => ['eventId' => $eventId, 'imageId' => $imageId, 'evidenceStatus' => 'available']], 201);
    }

    public function markEvidenceFailed(Request $request, int $baiTapId, string $eventId): JsonResponse
    {
        $request->validate(['eventId' => ['required', 'uuid', 'in:' . $eventId]]);
        $studentId = DB::table('sinh_vien')->where('nguoi_dung_id', $request->user()->id)->value('id');
        abort_unless($studentId, 403, 'Tài khoản không phải sinh viên.');
        $alert = DB::table('canh_bao_camera')->where('bai_tap_id', $baiTapId)
            ->where('sinh_vien_id', $studentId)->where('event_id', $eventId)->first();
        abort_unless($alert, 404, 'Không tìm thấy cảnh báo đã được chấp nhận.');
        if ($alert->evidence_status === 'pending') {
            DB::table('canh_bao_camera')->where('id', $alert->id)->update([
                'evidence_status' => 'failed', 'updated_at' => now(),
            ]);
        }
        return response()->json(['status' => true, 'data' => ['eventId' => $eventId, 'evidenceStatus' => $alert->evidence_status === 'pending' ? 'failed' : $alert->evidence_status]]);
    }
}
