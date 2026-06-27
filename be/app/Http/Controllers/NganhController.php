<?php

namespace App\Http\Controllers;

use App\Models\Nganh;
use Illuminate\Http\Request;

class NganhController extends Controller
{
    // Danh sách ngành
    public function getNganh()
    {
        $data = Nganh::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    // Chi tiết ngành
    public function chiTietNganh(Request $request)
    {
        $nganh = Nganh::find($request->id);

        if (!$nganh) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy ngành.'
            ]);
        }

        return response()->json([
            'status' => true,
            'data'   => $nganh
        ]);
    }

    // Thêm ngành
    public function themNganh(Request $request)
    {
        $request->validate([
            'ma_nganh'  => 'required|unique:nganh,ma_nganh',
            'ten_nganh' => 'required',
        ]);

        Nganh::create([
            'ma_nganh'  => $request->ma_nganh,
            'ten_nganh' => $request->ten_nganh,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Thêm ngành thành công.'
        ]);
    }

    // Sửa ngành
    public function suaNganh(Request $request)
    {
        $nganh = Nganh::find($request->id);

        if (!$nganh) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy ngành.'
            ]);
        }

        $request->validate([
            'ma_nganh'  => 'required|unique:nganh,ma_nganh,' . $request->id,
            'ten_nganh' => 'required',
        ]);

        $nganh->update([
            'ma_nganh'  => $request->ma_nganh,
            'ten_nganh' => $request->ten_nganh,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật ngành thành công.'
        ]);
    }

    // Xóa ngành
    public function xoaNganh(Request $request)
    {
        $nganh = Nganh::find($request->id);

        if (!$nganh) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy ngành.'
            ]);
        }

        $nganh->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa ngành thành công.'
        ]);
    }

    // Tìm kiếm
    public function timKiem(Request $request)
    {
        $keyword = $request->keyword;

        $data = Nganh::where('ma_nganh', 'like', '%' . $keyword . '%')
            ->orWhere('ten_nganh', 'like', '%' . $keyword . '%')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
}