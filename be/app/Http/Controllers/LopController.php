<?php

namespace App\Http\Controllers;

use App\Models\Lop;
use Illuminate\Http\Request;

class LopController extends Controller
{
     public function getLop(Request $request)
    {
        $data = Lop::select(
                'id',
                'ma_lop',
                'ten_lop',
                'khoa_id',
                'nganh_id',
                'co_van_id',
                'lop_truong_id',
                'bi_thu_id'
            )
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách lớp thành công',
            'data' => $data
        ]);
    }
}
