<?php

namespace App\Http\Controllers;

use App\Models\NhomTieuChi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NhomTieuChiController extends Controller
{
    public function getnhomtieuchi()
    {
        $data = NhomTieuChi::orderByDESC('id')->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function chiTietNhomTieuChi(Request $request)
    {
        $data = NhomTieuChi::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Nhóm tiêu chí không tồn tại'
            ]);
        }

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function themNhomTieuChi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ma_nhom'     => 'required|string|max:50|unique:nhom_tieu_chi,ma_nhom',
            'ten_nhom'    => 'required|string|max:255',
            'diem_toi_da' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validator->errors()
            ], 422);
        }

        NhomTieuChi::create([
            'ma_nhom'     => $request->ma_nhom,
            'ten_nhom'    => $request->ten_nhom,
            'diem_toi_da' => $request->diem_toi_da,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Thêm nhóm tiêu chí thành công'
        ]);
    }

    public function suaNhomTieuChi(Request $request)
    {
        $data = NhomTieuChi::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Nhóm tiêu chí không tồn tại'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'ma_nhom'     => 'required|string|max:50|unique:nhom_tieu_chi,ma_nhom,' . $request->id,
            'ten_nhom'    => 'required|string|max:255',
            'diem_toi_da' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data->update([
            'ma_nhom'     => $request->ma_nhom,
            'ten_nhom'    => $request->ten_nhom,
            'diem_toi_da' => $request->diem_toi_da,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật nhóm tiêu chí thành công'
        ]);
    }

    public function xoaNhomTieuChi(Request $request)
    {
        $data = NhomTieuChi::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Nhóm tiêu chí không tồn tại'
            ]);
        }

        $data->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa nhóm tiêu chí thành công'
        ]);
    }

    public function timKiemNhomTieuChi(Request $request)
    {
        $noi_dung = $request->noi_dung;

        $data = NhomTieuChi::where('ma_nhom', 'like', '%' . $noi_dung . '%')
            ->orWhere('ten_nhom', 'like', '%' . $noi_dung . '%')
            ->orWhere('diem_toi_da', 'like', '%' . $noi_dung . '%')
            ->orderByDESC('id')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
}