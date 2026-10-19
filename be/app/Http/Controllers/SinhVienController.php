<?php

namespace App\Http\Controllers;

use App\Models\BaiNop;
use App\Models\BaiTap;
use App\Models\Diem;
use App\Models\ThanhVienLop;
use Illuminate\Http\Request;

class SinhVienController extends Controller
{
    /**
     * Trang chủ sinh viên
     */
    public function getTrangChu(Request $request)
    {
        try {
            $sinhVien = $request->user();

            if (!$sinhVien) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bạn chưa đăng nhập.',
                ], 401);
            }

            /*
            |--------------------------------------------------------------------------
            | Lấy các lớp sinh viên đang học
            |--------------------------------------------------------------------------
            */

            $lopHocIds = ThanhVienLop::query()
                ->where('sinh_vien_id', $sinhVien->id)
                ->where('trang_thai', 'dang_hoc')
                ->pluck('lop_hoc_id');

            /*
            |--------------------------------------------------------------------------
            | Thống kê
            |--------------------------------------------------------------------------
            */

            $soLopHoc = $lopHocIds->count();

            $soBaiTap = BaiTap::query()
                ->whereIn('lop_hoc_id', $lopHocIds)
                ->count();

            $soBaiNop = BaiNop::query()
                ->where('sinh_vien_id', $sinhVien->id)
                ->count();

            $soDiem = Diem::query()
                ->whereHas('baiNop', function ($query) use ($sinhVien) {
                    $query->where(
                        'sinh_vien_id',
                        $sinhVien->id
                    );
                })
                ->count();

            /*
            |--------------------------------------------------------------------------
            | Bài tập gần đây
            |--------------------------------------------------------------------------
            */

            $baiTapGanDay = BaiTap::query()
                ->whereIn('lop_hoc_id', $lopHocIds)

                ->with([
                    'lopHoc:id,ma_lop,ten_lop',

                    'baiNops' => function ($query) use ($sinhVien) {
                        $query->where(
                            'sinh_vien_id',
                            $sinhVien->id
                        );
                    },
                ])

                ->orderByRaw(
                    'CASE WHEN han_nop IS NULL THEN 1 ELSE 0 END'
                )

                ->orderBy('han_nop', 'asc')

                ->limit(5)

                ->get()

                ->map(function ($baiTap) {
                    return [
                        'id' => $baiTap->id,

                        'tieu_de' => $baiTap->tieu_de,

                        'mo_ta' => $baiTap->mo_ta,

                        'han_nop' => $baiTap->han_nop,

                        'ma_lop' =>
                            $baiTap->lopHoc?->ma_lop,

                        'ten_lop' =>
                            $baiTap->lopHoc?->ten_lop,

                        'da_nop' =>
                            $baiTap->baiNops->isNotEmpty(),
                    ];
                });

            /*
            |--------------------------------------------------------------------------
            | Lớp học gần đây
            |--------------------------------------------------------------------------
            */

            $lopHoc = ThanhVienLop::query()
                ->where('sinh_vien_id', $sinhVien->id)
                ->where('trang_thai', 'dang_hoc')

                ->with([
                    'lopHoc.giangVien:id,ho_ten,email'
                ])

                ->orderByDesc('created_at')

                ->limit(5)

                ->get()

                ->map(function ($thanhVien) {
                    $lop = $thanhVien->lopHoc;

                    if (!$lop) {
                        return null;
                    }

                    return [
                        'id' => $lop->id,

                        'ma_lop' => $lop->ma_lop,

                        'ten_lop' => $lop->ten_lop,

                        'mo_ta' => $lop->mo_ta,

                        'trang_thai' =>
                            $thanhVien->trang_thai,

                        'giang_vien' => $lop->giangVien
                            ? [
                                'id' =>
                                    $lop->giangVien->id,

                                'ho_ten' =>
                                    $lop->giangVien->ho_ten,

                                'email' =>
                                    $lop->giangVien->email,
                            ]
                            : null,
                    ];
                })

                ->filter()
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => true,

                'message' =>
                    'Lấy dữ liệu trang chủ thành công.',

                'data' => [
                    'thong_ke' => [
                        'lop_hoc' => $soLopHoc,
                        'bai_tap' => $soBaiTap,
                        'bai_nop' => $soBaiNop,
                        'diem' => $soDiem,
                    ],

                    'bai_tap_gan_day' =>
                        $baiTapGanDay,

                    'lop_hoc' =>
                        $lopHoc,
                ],
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,

                'message' =>
                    'Không thể lấy dữ liệu trang chủ.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}