<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiangVienCanhBaoCameraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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
                $request->user()->id
            )
            ->select(
                'cb.id',
                'cb.bai_tap_id',
                'cb.sinh_vien_id',
                'cb.loai_canh_bao',
                'cb.created_at',
                'bt.tieu_de',
                'lh.ten_lop',
                'sv.ma_sinh_vien',
                'nd.ho_ten'
            )
            ->orderByDesc('cb.id')
            ->limit(300)
            ->get();

        $result = $alerts->map(function ($item) {
            return [
                'id' => $item->id,
                'baiTapId' => $item->bai_tap_id,
                'sinhVienId' => $item->sinh_vien_id,
                'tenSinhVien' => $item->ho_ten,
                'mssv' => $item->ma_sinh_vien,
                'tenBaiKiemTra' => $item->tieu_de,
                'tenLop' => $item->ten_lop,
                'loaiCanhBao' => $item->loai_canh_bao,
                'thoiGian' => $item->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $result,
        ]);
    }
}
