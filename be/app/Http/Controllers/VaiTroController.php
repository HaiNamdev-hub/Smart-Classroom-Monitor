<?php

namespace App\Http\Controllers;

use App\Models\VaiTro;
use Illuminate\Http\JsonResponse;

class VaiTroController extends Controller
{
    /**
     * Lấy danh sách vai trò dùng cho phân quyền.
     */
    public function getData(): JsonResponse
    {
        $order = [
            'quan_tri_vien' => 1,
            'giang_vien'    => 2,
            'sinh_vien'     => 3,
        ];

        $data = VaiTro::whereIn('ten_vai_tro', [
            'quan_tri_vien',
            'giang_vien',
            'sinh_vien',
        ])
            ->withCount('nguoiDungs')
            ->get()
            ->sortBy(function ($item) use ($order) {
                return $order[$item->ten_vai_tro] ?? 99;
            })
            ->values();

        return response()->json([
            'status'  => true,
            'message' => 'Lấy danh sách vai trò thành công',
            'data'    => $data,
        ]);
    }

    /**
     * API cũ nếu các trang khác của project đang sử dụng.
     */
    public function getVaiTro(): JsonResponse
    {
        return response()->json([
            'data' => VaiTro::withCount('nguoiDungs')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
