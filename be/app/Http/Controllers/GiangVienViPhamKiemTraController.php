<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiangVienViPhamKiemTraController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH VI PHẠM KIỂM TRA
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        // 1. Kiểm tra đăng nhập
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        // 2. Kiểm tra bộ lọc
        $filters = $request->validate([
            'baiTapId' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        // 3. Lấy danh sách vi phạm
        $query = DB::table('vi_pham_kiem_tra as vp')
            ->join(
                'bai_tap as bt',
                'vp.bai_tap_id',
                '=',
                'bt.id'
            )
            ->join(
                'sinh_vien as sv',
                'vp.sinh_vien_id',
                '=',
                'sv.id'
            )
            ->join(
                'nguoi_dung as nd',
                'sv.nguoi_dung_id',
                '=',
                'nd.id'
            )
            ->join(
                'lop_hoc as lh',
                'bt.lop_hoc_id',
                '=',
                'lh.id'
            )

            // Chỉ xem vi phạm của các lớp
            // Giảng viên đang phụ trách.
            ->where(
                'lh.giang_vien_id',
                $nguoiDung->id
            );

        // 4. Lọc theo bài kiểm tra
        if (!empty($filters['baiTapId'])) {
            $query->where(
                'vp.bai_tap_id',
                $filters['baiTapId']
            );
        }

        // 5. Lấy dữ liệu
        $data = $query
            ->select([
                'vp.id',
                'vp.bai_tap_id',
                'vp.sinh_vien_id',
                'vp.lan_thu',
                'vp.loai_vi_pham',
                'vp.created_at',

                'bt.tieu_de as ten_bai_kiem_tra',

                'nd.ho_ten as ten_sinh_vien',

                'sv.ma_sinh_vien',

                'lh.ma_lop',
                'lh.ten_lop',
            ])
            ->orderByDesc('vp.created_at')
            ->paginate(20);

        // 6. Bổ sung trạng thái cảnh báo
        $data->getCollection()->transform(
            function ($item) {
                $item->bi_khoa =
                    (int) $item->lan_thu >= 3;

                $item->trang_thai =
                    $item->bi_khoa
                        ? 'da_khoa'
                        : 'canh_bao';

                return $item;
            }
        );

        // 7. Response cho Frontend
        return response()->json([
            'status' => true,

            'message' =>
                'Lấy danh sách vi phạm kiểm tra thành công.',

            'data' => $data,
        ]);
    }
}
