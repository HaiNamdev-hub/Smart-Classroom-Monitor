<?php

namespace App\Http\Controllers;

use App\Models\PhanQuyen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhanQuyenController extends Controller
{
    private function checkQuyenPhanQuyen()
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return false;
        }

        // Admin toàn quyền
        if ($user->vai_tro_id == 1) {
            return true;
        }

        return PhanQuyen::join('chuc_nang', 'phan_quyen.chuc_nang_id', '=', 'chuc_nang.id')
            ->where('phan_quyen.vai_tro_id', $user->vai_tro_id)
            ->where('chuc_nang.ma_chuc_nang', 'phan_quyen')
            ->exists();
    }

    public function themPhanQuyen(Request $request)
    {
        if (!$this->checkQuyenPhanQuyen()) {
            return response()->json([
                'status'  => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này!'
            ]);
        }

        $request->validate([
            'vai_tro_id'   => 'required|integer',
            'chuc_nang_id' => 'required|integer',
        ]);

        PhanQuyen::firstOrCreate([
            'vai_tro_id'   => $request->vai_tro_id,
            'chuc_nang_id' => $request->chuc_nang_id,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Đã cấp quyền thành công!'
        ]);
    }

    public function xoaPhanQuyen(Request $request)
    {
        if (!$this->checkQuyenPhanQuyen()) {
            return response()->json([
                'status'  => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này!'
            ]);
        }

        $data = PhanQuyen::where('vai_tro_id', $request->vai_tro_id)
            ->where('chuc_nang_id', $request->chuc_nang_id)
            ->first();

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Quyền không tồn tại!'
            ]);
        }

        $data->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Đã xóa quyền thành công!'
        ]);
    }

    public function getChiTietPhanQuyen(Request $request)
    {
        if (!$this->checkQuyenPhanQuyen()) {
            return response()->json([
                'status'  => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này!'
            ]);
        }

        $request->validate([
            'vai_tro_id' => 'required|integer',
        ]);

        $data = PhanQuyen::where('phan_quyen.vai_tro_id', $request->vai_tro_id)
            ->join('chuc_nang', 'chuc_nang.id', '=', 'phan_quyen.chuc_nang_id')
            ->select(
                'phan_quyen.id',
                'phan_quyen.vai_tro_id',
                'phan_quyen.chuc_nang_id',
                'chuc_nang.ma_chuc_nang',
                'chuc_nang.ten_chuc_nang'
            )
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function checkQuyen(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Chưa đăng nhập'
            ]);
        }

        if ($user->vai_tro_id == 1) {
            return response()->json([
                'status' => true,
                'message' => 'Có quyền'
            ]);
        }

        $check = PhanQuyen::join('chuc_nang', 'phan_quyen.chuc_nang_id', '=', 'chuc_nang.id')
            ->where('phan_quyen.vai_tro_id', $user->vai_tro_id)
            ->where('chuc_nang.ma_chuc_nang', $request->ma_chuc_nang)
            ->exists();

        return response()->json([
            'status'  => $check,
            'message' => $check ? 'Có quyền' : 'Bạn không có quyền truy cập chức năng này'
        ]);
    }
}