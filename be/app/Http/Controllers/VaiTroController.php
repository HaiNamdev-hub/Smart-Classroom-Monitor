<?php

namespace App\Http\Controllers;

use App\Models\VaiTro;

class VaiTroController extends Controller
{
    public function getData()
    {
        $data = VaiTro::orderBy('id', 'ASC')->get();

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách vai trò thành công',
            'data' => $data
        ]);
    }
}