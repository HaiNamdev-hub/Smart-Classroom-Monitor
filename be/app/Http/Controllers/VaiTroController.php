<?php

namespace App\Http\Controllers;

use App\Models\VaiTro;

class VaiTroController extends Controller
{
    public function getData()
    {
        $order = [
            'admin' => 1,
            'phong_qldt' => 2,
            'co_van' => 3,
        ];

        $data = VaiTro::whereIn('ten_vai_tro', [
                'admin',
                'phong_qldt',
                'co_van',
            ])
            ->get()
            ->sortBy(function ($item) use ($order) {
                return $order[$item->ten_vai_tro] ?? 99;
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách vai trò thành công',
            'data' => $data
        ]);
    }
}