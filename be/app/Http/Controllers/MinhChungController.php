<?php

namespace App\Http\Controllers;

use App\Models\MinhChung;

class MinhChungController extends Controller
{
    public function xem($id)
    {
        $data = MinhChung::find($id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy minh chứng!'
            ], 404);
        }

        $duongDan = $data->duong_dan;

        if ($duongDan && !str_starts_with($duongDan, 'http')) {
            $duongDan = asset('storage/' . $duongDan);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Lấy minh chứng thành công!',
            'data'    => [
                'id'          => $data->id,
                'chi_tiet_id' => $data->chi_tiet_id,
                'ten_file'    => $data->ten_file,
                'duong_dan'   => $duongDan,
                'trang_thai'  => $data->trang_thai,
                'created_at'  => $data->created_at,
            ]
        ]);
    }

    public function tai($id)
    {
        $data = MinhChung::find($id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy minh chứng!'
            ], 404);
        }

        $duongDan = $data->duong_dan;

        if (str_starts_with($duongDan, 'http')) {
            return redirect($duongDan);
        }

        $path = storage_path('app/public/' . $duongDan);

        if (!file_exists($path)) {
            return response()->json([
                'status'  => false,
                'message' => 'File không tồn tại!',
                'path'    => $path
            ], 404);
        }

        return response()->download($path, $data->ten_file);
    }
}