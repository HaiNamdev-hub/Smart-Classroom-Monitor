<?php

namespace App\Http\Controllers;

use App\Models\PhanQuyen;
use App\Models\TieuChiLuaChon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TieuChiLuaChonController extends Controller
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
    public function getluachon()
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = TieuChiLuaChon::join('tieu_chi', 'tieu_chi_lua_chon.tieu_chi_id', '=', 'tieu_chi.id')
            ->select(
                'tieu_chi_lua_chon.*',
                'tieu_chi.ma_tieu_chi',
                'tieu_chi.ten_tieu_chi'
            )
            ->orderByDESC('tieu_chi_lua_chon.id')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function chiTietLuaChon(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = TieuChiLuaChon::join('tieu_chi', 'tieu_chi_lua_chon.tieu_chi_id', '=', 'tieu_chi.id')
            ->select(
                'tieu_chi_lua_chon.*',
                'tieu_chi.ma_tieu_chi',
                'tieu_chi.ten_tieu_chi'
            )
            ->where('tieu_chi_lua_chon.id', $request->id)
            ->first();

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Lựa chọn tiêu chí không tồn tại'
            ]);
        }

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function themLuaChon(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = new TieuChiLuaChon();

        $data->tieu_chi_id = $request->tieu_chi_id;
        $data->noi_dung = $request->noi_dung;
        $data->diem_tuong_ung = $request->diem_tuong_ung;
        $data->thu_tu = $request->thu_tu;

        $data->save();

        return response()->json([
            'status' => true,
            'message' => 'Thêm lựa chọn thành công'
        ]);
    }

    public function suaLuaChon(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = TieuChiLuaChon::find($request->id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Lựa chọn không tồn tại'
            ]);
        }

        $data->tieu_chi_id = $request->tieu_chi_id;
        $data->noi_dung = $request->noi_dung;
        $data->diem_tuong_ung = $request->diem_tuong_ung;
        $data->thu_tu = $request->thu_tu;

        $data->save();

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật lựa chọn thành công'
        ]);
    }

    public function xoaLuaChon(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = TieuChiLuaChon::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Lựa chọn tiêu chí không tồn tại'
            ]);
        }

        $data->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa lựa chọn tiêu chí thành công'
        ]);
    }

    public function timKiemLuaChon(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $noi_dung = $request->noi_dung;

        $data = TieuChiLuaChon::join('tieu_chi', 'tieu_chi_lua_chon.tieu_chi_id', '=', 'tieu_chi.id')
            ->select(
                'tieu_chi_lua_chon.*',
                'tieu_chi.ma_tieu_chi',
                'tieu_chi.ten_tieu_chi'
            )
            ->where('tieu_chi_lua_chon.noi_dung', 'like', '%' . $noi_dung . '%')
            ->orWhere('tieu_chi_lua_chon.diem_tuong_ung', 'like', '%' . $noi_dung . '%')
            ->orWhere('tieu_chi.ma_tieu_chi', 'like', '%' . $noi_dung . '%')
            ->orWhere('tieu_chi.ten_tieu_chi', 'like', '%' . $noi_dung . '%')
            ->orderByDESC('tieu_chi_lua_chon.id')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
}
