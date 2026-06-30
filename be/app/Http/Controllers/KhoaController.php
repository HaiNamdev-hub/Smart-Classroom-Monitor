<?php

namespace App\Http\Controllers;

use App\Models\Khoa;
use App\Models\PhanQuyen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KhoaController extends Controller
{
    private function kiemTraQuyen($ma_chuc_nang)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return false;
        }

        if ($user->vai_tro_id == 1) {
            return true;
        }

        return PhanQuyen::join('chuc_nang', 'chuc_nang.id', '=', 'phan_quyen.chuc_nang_id')
            ->where('phan_quyen.vai_tro_id', $user->vai_tro_id)
            ->where('chuc_nang.ma_chuc_nang', $ma_chuc_nang)
            ->exists();
    }

    private function khongCoQuyen()
    {
        return response()->json([
            'status'  => false,
            'message' => 'Bạn không có quyền thực hiện chức năng này!'
        ], 403);
    }
    public function getKhoa()
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = Khoa::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function chiTietKhoa(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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