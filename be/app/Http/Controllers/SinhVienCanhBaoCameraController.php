<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                $sinhVienId
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
            $existed = (clone $cameraQuery)
                ->where(
                    'event_id',
                    $data['eventId']
                )
                ->exists();

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
                    $daNop
                );
            }

            // Nếu eventId đã tồn tại,
            // trả lại bộ đếm hiện tại.
            if ($existed) {
                return $this->responseState(
                    $otherCameraCount,
                    $phoneCount,
                    $tabCount,
                    $daNop
                );
            }

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

            DB::table('canh_bao_camera')->insert([
                'bai_tap_id' => $baiTapId,
                'sinh_vien_id' => $sinhVienId,

                'event_id' => $data['eventId'],

                'loai_canh_bao' =>
                    $data['loaiCanhBao'],

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

                    broadcast(new \App\Events\CanhBaoExamEvent(
                        phienId: $phien->id,
                        sinhVienId: $sinhVienId,
                        loaiCanhBao: $data['loaiCanhBao'],
                        noiDung: $tenLoai[$data['loaiCanhBao']] ?? 'Cảnh báo camera',
                        thoiGian: now()->toISOString(),
                        soLanCamera: $otherCameraCount,
                        soLanDienThoai: $phoneCount,
                        soLanViPham: $tabCount,
                        biKhoa: $isLocked,
                        lyDoKhoa: $lockReason
                    ))->toOthers();
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
                false
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
        bool $daNop
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
            ],
        ]);
    }
}
