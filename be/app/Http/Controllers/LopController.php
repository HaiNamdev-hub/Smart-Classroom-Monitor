<?php

namespace App\Http\Controllers;

use App\Models\Lop;
use App\Models\PhanQuyen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LopController extends Controller
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

    public function getLop()
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = Lop::select(
            'id',
            'ma_lop',
            'ten_lop',
            'khoa_id',
            'nganh_id',
            'co_van_id',
            'lop_truong_id',
            'bi_thu_id'
        )
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Lấy danh sách lớp thành công',
            'data'    => $data
        ]);
    }

    public function chiTietLop(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $lop = Lop::find($request->id);

        if (!$lop) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy lớp'
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Lấy chi tiết lớp thành công',
            'data'    => $lop
        ]);
    }

    public function themLop(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $request->validate([
            'ma_lop'  => 'required|unique:lop,ma_lop',
            'ten_lop' => 'required',
            'khoa_id' => 'required|exists:khoa,id',
            'nganh_id' => 'required|exists:nganh,id',
            'co_van_id' => 'nullable|exists:nguoi_dung,id',
            'lop_truong_id' => 'nullable|exists:nguoi_dung,id',
            'bi_thu_id' => 'nullable|exists:nguoi_dung,id',
        ]);

        Lop::create([
            'ma_lop'       => $request->ma_lop,
            'ten_lop'      => $request->ten_lop,
            'khoa_id'      => $request->khoa_id,
            'nganh_id'     => $request->nganh_id,
            'co_van_id'    => $request->co_van_id,
            'lop_truong_id' => $request->lop_truong_id,
            'bi_thu_id'    => $request->bi_thu_id,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Thêm lớp thành công'
        ]);
    }

    public function suaLop(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $lop = Lop::find($request->id);

        if (!$lop) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy lớp'
            ]);
        }

        $request->validate([
            'ma_lop'  => 'required|unique:lop,ma_lop,' . $request->id,
            'ten_lop' => 'required',
            'khoa_id' => 'required|exists:khoa,id',
            'nganh_id' => 'required|exists:nganh,id',
            'co_van_id' => 'nullable|exists:nguoi_dung,id',
            'lop_truong_id' => 'nullable|exists:nguoi_dung,id',
            'bi_thu_id' => 'nullable|exists:nguoi_dung,id',
        ]);

        $lop->update([
            'ma_lop'       => $request->ma_lop,
            'ten_lop'      => $request->ten_lop,
            'khoa_id'      => $request->khoa_id,
            'nganh_id'     => $request->nganh_id,
            'co_van_id'    => $request->co_van_id,
            'lop_truong_id' => $request->lop_truong_id,
            'bi_thu_id'    => $request->bi_thu_id,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật lớp thành công'
        ]);
    }

    public function xoaLop(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $lop = Lop::find($request->id);

        if (!$lop) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy lớp'
            ]);
        }

        $lop->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa lớp thành công'
        ]);
    }

    public function timKiem(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $keyword = $request->keyword;

        $data = Lop::where('ma_lop', 'like', '%' . $keyword . '%')
            ->orWhere('ten_lop', 'like', '%' . $keyword . '%')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Tìm kiếm lớp thành công',
            'data'    => $data
        ]);
    }
}
