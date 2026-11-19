<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiangVienCanhBaoCameraController extends Controller
{
    private const MAX_PHONE = 3;
    private const MAX_CAMERA = 5;
    private const MAX_TAB = 3;

    /**
     * GET /api/giang-vien/canh-bao-camera
     *
     * Lấy danh sách cảnh báo camera của các lớp
     * mà Giảng viên đang phụ trách.
     */
    public function index(Request $request): JsonResponse
    {
        // ==========================================
        // 1. XÁC ĐỊNH GIẢNG VIÊN ĐĂNG NHẬP
        // ==========================================

        $giangVienId = $request->user()->id;

        // ==========================================
        // 2. LẤY DANH SÁCH CẢNH BÁO CAMERA
        // ==========================================

        $alerts = DB::table('canh_bao_camera as cb')
            ->join(
                'bai_tap as bt',
                'cb.bai_tap_id',
                '=',
                'bt.id'
            )
            ->join(
                'lop_hoc as lh',
                'bt.lop_hoc_id',
                '=',
                'lh.id'
            )
            ->join(
                'sinh_vien as sv',
                'cb.sinh_vien_id',
                '=',
                'sv.id'
            )
            ->join(
                'nguoi_dung as nd',
                'sv.nguoi_dung_id',
                '=',
                'nd.id'
            )
            ->where(
                'lh.giang_vien_id',
                $giangVienId
            )
            ->select(
                'cb.id',
                'cb.bai_tap_id',
                'cb.sinh_vien_id',
                'cb.loai_canh_bao',
                'cb.event_id',
                'cb.evidence_status',
                'cb.created_at',
                'bt.tieu_de',
                'lh.ten_lop',
                'sv.ma_sinh_vien',
                'nd.ho_ten'
            )
            ->orderByDesc('cb.id')
            ->limit(300)
            ->get();

        // Chưa có cảnh báo thì trả về danh sách rỗng.
        if ($alerts->isEmpty()) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }

        // ==========================================
        // 3. LẤY CÁC BÀI THI VÀ SINH VIÊN CẦN ĐẾM
        // ==========================================

        $baiTapIds = $alerts
            ->pluck('bai_tap_id')
            ->unique()
            ->values()
            ->all();

        $sinhVienIds = $alerts
            ->pluck('sinh_vien_id')
            ->unique()
            ->values()
            ->all();

        // ==========================================
        // 4. ĐẾM CẢNH BÁO CAMERA THEO LOẠI
        // ==========================================

        $cameraStats = DB::table('canh_bao_camera')
            ->whereIn('bai_tap_id', $baiTapIds)
            ->whereIn('sinh_vien_id', $sinhVienIds)
            ->select(
                'bai_tap_id',
                'sinh_vien_id'
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN loai_canh_bao =
                    'phat_hien_dien_thoai'
                    THEN 1 ELSE 0
                END) AS so_lan_dien_thoai"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN loai_canh_bao <>
                    'phat_hien_dien_thoai'
                    THEN 1 ELSE 0
                END) AS so_lan_camera"
            )
            ->groupBy(
                'bai_tap_id',
                'sinh_vien_id'
            )
            ->get()
            ->keyBy(function ($item) {
                return $item->bai_tap_id
                    . '_'
                    . $item->sinh_vien_id;
            });

        // ==========================================
        // 5. ĐẾM VI PHẠM RỜI TAB
        // ==========================================

        $tabStats = DB::table('vi_pham_kiem_tra')
            ->whereIn('bai_tap_id', $baiTapIds)
            ->whereIn('sinh_vien_id', $sinhVienIds)
            ->select(
                'bai_tap_id',
                'sinh_vien_id'
            )
            ->selectRaw(
                'COUNT(*) AS so_lan_vi_pham'
            )
            ->groupBy(
                'bai_tap_id',
                'sinh_vien_id'
            )
            ->get()
            ->keyBy(function ($item) {
                return $item->bai_tap_id
                    . '_'
                    . $item->sinh_vien_id;
            });

        // ==========================================
        // 6. CHUYỂN ĐỔI DỮ LIỆU CHO FRONTEND
        // ==========================================

        $evidenceIds = DB::table('anh_man_hinh')
            ->whereIn('event_id', $alerts->pluck('event_id')->filter()->all())
            ->pluck('id', 'event_id');

        $result = $alerts->map(function ($item) use (
            $cameraStats,
            $tabStats,
            $evidenceIds
        ) {
            $key = $item->bai_tap_id
                . '_'
                . $item->sinh_vien_id;

            $camera = $cameraStats->get($key);
            $tab = $tabStats->get($key);

            $phoneCount = (int) (
                $camera->so_lan_dien_thoai ?? 0
            );

            $cameraCount = (int) (
                $camera->so_lan_camera ?? 0
            );

            $tabCount = (int) (
                $tab->so_lan_vi_pham ?? 0
            );

            // ======================================
            // 7. XÁC ĐỊNH TRẠNG THÁI KHÓA BÀI
            // ======================================

            $phoneLocked =
                $phoneCount >= self::MAX_PHONE;

            $cameraLocked =
                $cameraCount >= self::MAX_CAMERA;

            $tabLocked =
                $tabCount >= self::MAX_TAB;

            $biKhoa = (
                $phoneLocked ||
                $cameraLocked ||
                $tabLocked
            );

            $lyDoKhoa = null;

            if ($phoneLocked) {
                $lyDoKhoa = 'dien_thoai';
            } elseif ($cameraLocked) {
                $lyDoKhoa = 'camera';
            } elseif ($tabLocked) {
                $lyDoKhoa = 'roi_tab';
            }

            // ======================================
            // 8. CHUYỂN TÊN LOẠI CẢNH BÁO
            // ======================================

            $tenLoaiCanhBao = match (
                $item->loai_canh_bao
            ) {
                'khong_co_khuon_mat' =>
                    'Không phát hiện khuôn mặt',

                'nhieu_khuon_mat' =>
                    'Phát hiện nhiều khuôn mặt',

                'camera_ngat' =>
                    'Camera bị ngắt kết nối',

                'phat_hien_dien_thoai' =>
                    'Phát hiện điện thoại',

                default =>
                    'Cảnh báo camera',
            };

            // ======================================
            // 9. TRẢ VỀ THÔNG TIN TỪNG CẢNH BÁO
            // ======================================

            return [
                // Thông tin cảnh báo
                'id' => $item->id,

                'baiTapId' =>
                    $item->bai_tap_id,

                'sinhVienId' =>
                    $item->sinh_vien_id,

                'loaiCanhBao' =>
                    $item->loai_canh_bao,

                'eventId' => $item->event_id,
                'evidenceStatus' => $item->evidence_status ?? 'none',
                'imageAvailable' => $evidenceIds->has($item->event_id),
                'evidenceUrl' => $evidenceIds->has($item->event_id)
                    ? '/api/giang-vien/anh-man-hinh/' . $evidenceIds->get($item->event_id) . '/file'
                    : null,

                'tenLoaiCanhBao' =>
                    $tenLoaiCanhBao,

                'thoiGian' =>
                    $item->created_at,

                // Thông tin Sinh viên
                'tenSinhVien' =>
                    $item->ho_ten,

                'mssv' =>
                    $item->ma_sinh_vien,

                // Thông tin lớp và bài kiểm tra
                'tenBaiKiemTra' =>
                    $item->tieu_de,

                'tenLop' =>
                    $item->ten_lop,

                // Số lần vi phạm
                'soLanDienThoai' =>
                    $phoneCount,

                'soLanCamera' =>
                    $cameraCount,

                'soLanViPham' =>
                    $tabCount,

                // Số lượt còn lại
                'soLanDienThoaiConLai' => max(
                    0,
                    self::MAX_PHONE - $phoneCount
                ),

                'soLanCameraConLai' => max(
                    0,
                    self::MAX_CAMERA - $cameraCount
                ),

                'soLanViPhamConLai' => max(
                    0,
                    self::MAX_TAB - $tabCount
                ),

                // Trạng thái khóa bài
                'biKhoa' => $biKhoa,

                'lyDoKhoa' => $lyDoKhoa,

                // Trạng thái xem xét từ Frontend.
                // Chưa có trạng thái xử lý lưu trong DB.
                'canXemXet' => true,
            ];
        });

        // ==========================================
        // 10. TRẢ JSON
        // ==========================================

        return response()->json([
            'status' => true,
            'data' => $result->values(),
        ]);
    }
}
