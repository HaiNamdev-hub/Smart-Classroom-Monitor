<?php

namespace App\Http\Controllers;

use App\Models\PhanQuyen;
use App\Models\TieuChi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TieuChiController extends Controller
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
    public function gettieuchi()
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = TieuChi::leftJoin('nhom_tieu_chi', 'tieu_chi.nhom_id', '=', 'nhom_tieu_chi.id')
            ->leftJoin('tieu_chi as parent', 'tieu_chi.parent_id', '=', 'parent.id')
            ->select(
                'tieu_chi.*',
                'nhom_tieu_chi.ma_nhom',
                'nhom_tieu_chi.ten_nhom',
                'parent.ma_tieu_chi as ma_tieu_chi_cha',
                'parent.ten_tieu_chi as ten_tieu_chi_cha'
            )
            ->orderBy('nhom_tieu_chi.thu_tu', 'ASC')
            ->orderBy('tieu_chi.thu_tu', 'ASC')
            ->orderBy('tieu_chi.id', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function chiTietTieuChi(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = TieuChi::leftJoin('nhom_tieu_chi', 'tieu_chi.nhom_id', '=', 'nhom_tieu_chi.id')
            ->leftJoin('tieu_chi as parent', 'tieu_chi.parent_id', '=', 'parent.id')
            ->select(
                'tieu_chi.*',
                'nhom_tieu_chi.ma_nhom',
                'nhom_tieu_chi.ten_nhom',
                'parent.ma_tieu_chi as ma_tieu_chi_cha',
                'parent.ten_tieu_chi as ten_tieu_chi_cha'
            )
            ->where('tieu_chi.id', $request->id)
            ->first();

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Tiêu chí không tồn tại'
            ]);
        }

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    public function themTieuChi(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $check = TieuChi::where('nhom_id', $request->nhom_id)
            ->where('ma_tieu_chi', $request->ma_tieu_chi)
            ->first();

        if ($check) {
            return response()->json([
                'status'  => false,
                'message' => 'Mã tiêu chí trong nhóm này đã tồn tại'
            ]);
        }

        if ($request->parent_id && Number_format($request->parent_id) == Number_format($request->id)) {
            return response()->json([
                'status'  => false,
                'message' => 'Tiêu chí cha không hợp lệ'
            ]);
        }

        $data = new TieuChi();

        $data->nhom_id = $request->nhom_id;
        $data->parent_id = $request->parent_id ?: null;
        $data->ma_tieu_chi = $request->ma_tieu_chi;
        $data->ten_tieu_chi = $request->ten_tieu_chi;
        $data->diem_toi_da = $request->diem_toi_da;
        $data->loai_tieu_chi = $request->loai_tieu_chi;
        $data->loai_nhap = $request->loai_nhap;
        $data->bat_buoc_minh_chung = $request->bat_buoc_minh_chung ?? 0;
        $data->thu_tu = $request->thu_tu ?? 1;

        $data->save();

        return response()->json([
            'status'  => true,
            'message' => 'Thêm tiêu chí thành công'
        ]);
    }

    public function suaTieuChi(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = TieuChi::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Tiêu chí không tồn tại'
            ]);
        }

        $check = TieuChi::where('nhom_id', $request->nhom_id)
            ->where('ma_tieu_chi', $request->ma_tieu_chi)
            ->where('id', '!=', $request->id)
            ->first();

        if ($check) {
            return response()->json([
                'status'  => false,
                'message' => 'Mã tiêu chí trong nhóm này đã tồn tại'
            ]);
        }

        if ($request->parent_id && (int) $request->parent_id === (int) $request->id) {
            return response()->json([
                'status'  => false,
                'message' => 'Tiêu chí cha không được là chính nó'
            ]);
        }

        $data->nhom_id = $request->nhom_id;
        $data->parent_id = $request->parent_id ?: null;
        $data->ma_tieu_chi = $request->ma_tieu_chi;
        $data->ten_tieu_chi = $request->ten_tieu_chi;
        $data->diem_toi_da = $request->diem_toi_da;
        $data->loai_tieu_chi = $request->loai_tieu_chi;
        $data->loai_nhap = $request->loai_nhap;
        $data->bat_buoc_minh_chung = $request->bat_buoc_minh_chung ?? 0;
        $data->thu_tu = $request->thu_tu ?? 1;

        $data->save();

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật tiêu chí thành công'
        ]);
    }

    public function xoaTieuChi(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = TieuChi::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Tiêu chí không tồn tại'
            ]);
        }

        $data->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa tiêu chí thành công'
        ]);
    }

    public function timKiemTieuChi(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $noi_dung = $request->noi_dung;

        $data = TieuChi::leftJoin('nhom_tieu_chi', 'tieu_chi.nhom_id', '=', 'nhom_tieu_chi.id')
            ->leftJoin('tieu_chi as parent', 'tieu_chi.parent_id', '=', 'parent.id')
            ->select(
                'tieu_chi.*',
                'nhom_tieu_chi.ma_nhom',
                'nhom_tieu_chi.ten_nhom',
                'parent.ma_tieu_chi as ma_tieu_chi_cha',
                'parent.ten_tieu_chi as ten_tieu_chi_cha'
            )
            ->where(function ($query) use ($noi_dung) {
                $query->where('tieu_chi.ma_tieu_chi', 'like', '%' . $noi_dung . '%')
                    ->orWhere('tieu_chi.ten_tieu_chi', 'like', '%' . $noi_dung . '%')
                    ->orWhere('tieu_chi.diem_toi_da', 'like', '%' . $noi_dung . '%')
                    ->orWhere('tieu_chi.loai_tieu_chi', 'like', '%' . $noi_dung . '%')
                    ->orWhere('tieu_chi.loai_nhap', 'like', '%' . $noi_dung . '%')
                    ->orWhere('tieu_chi.thu_tu', 'like', '%' . $noi_dung . '%')
                    ->orWhere('nhom_tieu_chi.ma_nhom', 'like', '%' . $noi_dung . '%')
                    ->orWhere('nhom_tieu_chi.ten_nhom', 'like', '%' . $noi_dung . '%')
                    ->orWhere('parent.ma_tieu_chi', 'like', '%' . $noi_dung . '%')
                    ->orWhere('parent.ten_tieu_chi', 'like', '%' . $noi_dung . '%');
            })
            ->orderBy('nhom_tieu_chi.thu_tu', 'ASC')
            ->orderBy('tieu_chi.thu_tu', 'ASC')
            ->orderBy('tieu_chi.id', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
}
