<?php

namespace App\Http\Controllers;

use App\Models\Nganh;
use App\Models\PhanQuyen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NganhController extends Controller
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
    // Danh sách ngành
    public function getNganh()
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = Nganh::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    // Chi tiết ngành
    public function chiTietNganh(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
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
