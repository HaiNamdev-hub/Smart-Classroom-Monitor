<?php

namespace App\Http\Controllers;

use App\Models\BaiTap;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class BaiTapController extends CrudController
{
    protected string $modelClass = BaiTap::class;
    protected function rules(?Model $record = null): array
    {
        return ['lop_hoc_id' => ['required', 'integer', 'exists:lop_hoc,id'], 'nguoi_tao_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'tieu_de' => ['required', 'string', 'max:255'], 'mo_ta' => ['nullable', 'string'], 'thoi_gian_bat_dau' => ['required', 'date'], 'thoi_gian_ket_thuc' => ['required', 'date', 'after_or_equal:thoi_gian_bat_dau'], 'han_nop' => ['required', 'date'], 'cho_phep_nop_tre' => ['boolean']];
    }
    public function getBaiTapSinhVien(Request $request)
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
        | Lấy lớp đang học
        |--------------------------------------------------------------------------
        */

            $lopHocIds = \App\Models\ThanhVienLop::query()

                ->where(
                    'sinh_vien_id',
                    $sinhVien->id
                )

                ->where(
                    'trang_thai',
                    'dang_hoc'
                )

                ->pluck('lop_hoc_id');

            /*
        |--------------------------------------------------------------------------
        | Lấy bài tập
        |--------------------------------------------------------------------------
        */

            $baiTaps = \App\Models\BaiTap::query()

                ->whereIn(
                    'lop_hoc_id',
                    $lopHocIds
                )

                ->with([
                    'lopHoc:id,ma_lop,ten_lop',

                    'nguoiTao:id,ho_ten,email',

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

                ->get()

                ->map(function ($baiTap) {

                    $baiNop =
                        $baiTap->baiNops->first();

                    return [
                        'id' =>
                        $baiTap->id,

                        'lop_hoc_id' =>
                        $baiTap->lop_hoc_id,

                        'tieu_de' =>
                        $baiTap->tieu_de,

                        'mo_ta' =>
                        $baiTap->mo_ta,

                        'thoi_gian_bat_dau' =>
                        $baiTap->thoi_gian_bat_dau,

                        'thoi_gian_ket_thuc' =>
                        $baiTap->thoi_gian_ket_thuc,

                        'han_nop' =>
                        $baiTap->han_nop,

                        'cho_phep_nop_tre' =>
                        (bool) $baiTap->cho_phep_nop_tre,

                        /*
                    |------------------------------------------
                    | Lớp học
                    |------------------------------------------
                    */

                        'lop_hoc' =>
                        $baiTap->lopHoc
                            ? [
                                'id' =>
                                $baiTap->lopHoc->id,

                                'ma_lop' =>
                                $baiTap->lopHoc->ma_lop,

                                'ten_lop' =>
                                $baiTap->lopHoc->ten_lop,
                            ]
                            : null,

                        /*
                    |------------------------------------------
                    | Người tạo
                    |------------------------------------------
                    */

                        'nguoi_tao' =>
                        $baiTap->nguoiTao
                            ? [
                                'id' =>
                                $baiTap->nguoiTao->id,

                                'ho_ten' =>
                                $baiTap->nguoiTao->ho_ten,

                                'email' =>
                                $baiTap->nguoiTao->email,
                            ]
                            : null,

                        /*
                    |------------------------------------------
                    | Trạng thái nộp
                    |------------------------------------------
                    */

                        'da_nop' =>
                        $baiNop !== null,

                        'bai_nop' =>
                        $baiNop
                            ? [
                                'id' =>
                                $baiNop->id,

                                'thoi_gian_nop' =>
                                $baiNop->thoi_gian_nop,

                                'trang_thai' =>
                                $baiNop->trang_thai,
                            ]
                            : null,
                    ];
                });

            return response()->json([
                'status' => true,

                'message' =>
                'Lấy danh sách bài tập thành công.',

                'data' =>
                $baiTaps,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,

                'message' =>
                'Không thể lấy danh sách bài tập.',

                'error' =>
                $e->getMessage(),
            ], 500);
        }
    }
}
