<?php

namespace App\Http\Controllers;

use App\Models\CanhBao;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use App\Models\PhienGiamSat;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function getData(): JsonResponse
    {
        return response()->json([
            'thong_ke' => [
                'nguoi_dung' => NguoiDung::count(),
                'lop_hoc' => LopHoc::count(),
                'giang_vien' => NguoiDung::whereHas('vaiTro', fn ($q) => $q->where('ten_vai_tro', 'giang_vien'))->count(),
                'phien_dang_dien_ra' => PhienGiamSat::where('trang_thai', 'dang_dien_ra')->count(),
                'canh_bao_chua_xu_ly' => CanhBao::where('da_xu_ly', false)->count(),
            ],
            'lop_hoc' => LopHoc::with('giangVien:id,ho_ten')->withCount('thanhViens')->latest('id')->limit(5)->get(),
            'nguoi_dung' => NguoiDung::with('vaiTro')->latest('id')->limit(5)->get(),
        ]);
    }
}
