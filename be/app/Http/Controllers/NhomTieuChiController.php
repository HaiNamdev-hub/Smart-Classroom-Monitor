<?php

namespace App\Http\Controllers;

use App\Models\NhomTieuChi;
use App\Models\PhanQuyen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NhomTieuChiController extends Controller
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

    public function getnhomtieuchi()
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $data = NhomTieuChi::orderBy('thu_tu', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function chiTietNhomTieuChi(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $check = NhomTieuChi::where('ma_nhom', $request->ma_nhom)->first();

        if ($check) {
            return response()->json([
                'status'  => false,
                'message' => 'Mã nhóm đã tồn tại'
            ]);
        }

        $data = new NhomTieuChi();

        $data->ma_nhom = $request->ma_nhom;
        $data->ten_nhom = $request->ten_nhom;
        $data->diem_toi_da = $request->diem_toi_da;
        $data->thu_tu = $request->thu_tu ?? 1;

        $data->save();

        return response()->json([
            'status'  => true,
            'message' => 'Thêm nhóm tiêu chí thành công'
        ]);
    }

    public function suaNhomTieuChi(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $data = NhomTieuChi::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Nhóm tiêu chí không tồn tại'
            ]);
        }

        $check = NhomTieuChi::where('ma_nhom', $request->ma_nhom)
            ->where('id', '!=', $request->id)
            ->first();

        if ($check) {
            return response()->json([
                'status'  => false,
                'message' => 'Mã nhóm đã tồn tại'
            ]);
        }

        $data->ma_nhom = $request->ma_nhom;
        $data->ten_nhom = $request->ten_nhom;
        $data->diem_toi_da = $request->diem_toi_da;
        $data->thu_tu = $request->thu_tu ?? 1;

        $data->save();

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật nhóm tiêu chí thành công'
        ]);
    }

    public function xoaNhomTieuChi(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

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
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $noi_dung = $request->noi_dung;

        $data = NhomTieuChi::where(function ($query) use ($noi_dung) {
            $query->where('ma_nhom', 'like', '%' . $noi_dung . '%')
                ->orWhere('ten_nhom', 'like', '%' . $noi_dung . '%')
                ->orWhere('diem_toi_da', 'like', '%' . $noi_dung . '%')
                ->orWhere('thu_tu', 'like', '%' . $noi_dung . '%');
        })
            ->orderBy('thu_tu', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
}
