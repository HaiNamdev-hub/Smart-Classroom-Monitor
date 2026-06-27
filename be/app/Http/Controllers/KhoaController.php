<?php

namespace App\Http\Controllers;

use App\Models\Khoa;
use Illuminate\Http\Request;

class KhoaController extends Controller
{
    public function getKhoa()
    {
        $data = Khoa::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function chiTietKhoa(Request $request)
    {
        $khoa = Khoa::find($request->id);

        if (!$khoa) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy khoa'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $khoa
        ]);
    }

    public function themKhoa(Request $request)
    {
        $request->validate([
            'ma_khoa' => 'required|unique:khoa,ma_khoa',
            'ten_khoa' => 'required',
        ]);

        Khoa::create([
            'ma_khoa' => $request->ma_khoa,
            'ten_khoa' => $request->ten_khoa,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Thêm khoa thành công'
        ]);
    }

    public function suaKhoa(Request $request)
    {
        $khoa = Khoa::find($request->id);

        if (!$khoa) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy khoa'
            ]);
        }

        $request->validate([
            'ma_khoa' => 'required|unique:khoa,ma_khoa,' . $request->id,
            'ten_khoa' => 'required',
        ]);

        $khoa->update([
            'ma_khoa' => $request->ma_khoa,
            'ten_khoa' => $request->ten_khoa,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật khoa thành công'
        ]);
    }

    public function xoaKhoa(Request $request)
    {
        $khoa = Khoa::find($request->id);

        if (!$khoa) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy khoa'
            ]);
        }

        $khoa->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa khoa thành công'
        ]);
    }

    public function timKiem(Request $request)
    {
        $keyword = $request->keyword;

        $data = Khoa::where('ma_khoa', 'like', '%' . $keyword . '%')
            ->orWhere('ten_khoa', 'like', '%' . $keyword . '%')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
}