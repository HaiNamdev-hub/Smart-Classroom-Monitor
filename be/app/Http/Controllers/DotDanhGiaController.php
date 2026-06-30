<?php

namespace App\Http\Controllers;

use App\Models\DotDanhGia;
use App\Models\PhanQuyen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DotDanhGiaController extends Controller
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
    public function getdotdanhga()
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = DotDanhGia::orderByDESC('id')->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function chiTietDotDanhGia(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = DotDanhGia::find($request->id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Đợt đánh giá không tồn tại'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function themDotDanhGia(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $validator = Validator::make($request->all(), [
            'nam_hoc'       => 'required|string|max:20',
            'hoc_ky'        => 'required|integer|in:1,2',
            'ngay_bat_dau'  => 'required|date',
            'ngay_ket_thuc' => 'required|date|after_or_equal:ngay_bat_dau',
            'trang_thai'    => 'required|string|in:nhap,dang_mo,da_dong',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        DotDanhGia::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Thêm đợt đánh giá thành công'
        ]);
    }

    public function suaDotDanhGia(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = DotDanhGia::find($request->id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Đợt đánh giá không tồn tại'
            ]);
        }


        $check = DotDanhGia::where('nam_hoc', $request->nam_hoc)
            ->where('hoc_ky', $request->hoc_ky)
            ->where('id', '!=', $request->id)
            ->first();

        if ($check) {
            return response()->json([
                'status' => false,
                'message' => 'Năm học và học kỳ này đã tồn tại'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'nam_hoc'       => 'required|string|max:20',
            'hoc_ky'        => 'required|integer|in:1,2',
            'ngay_bat_dau'  => 'required|date',
            'ngay_ket_thuc' => 'required|date|after_or_equal:ngay_bat_dau',
            'trang_thai'    => 'required|string|in:nhap,dang_mo,da_dong',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $data->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật đợt đánh giá thành công'
        ]);
    }

    public function xoaDotDanhGia(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = DotDanhGia::find($request->id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Đợt đánh giá không tồn tại'
            ]);
        }

        $data->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa đợt đánh giá thành công'
        ]);
    }

    public function timKiemDotDanhGia(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $noi_dung = $request->noi_dung;

        $data = DotDanhGia::where('nam_hoc', 'like', '%' . $noi_dung . '%')
            ->orWhere('hoc_ky', 'like', '%' . $noi_dung . '%')
            ->orderByDESC('id')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function khoaDotDanhGia(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }
        $data = DotDanhGia::find($request->id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Đợt đánh giá không tồn tại'
            ]);
        }

        $data->trang_thai = $data->trang_thai == 1 ? 0 : 1;
        $data->save();

        return response()->json([
            'status' => true,
            'message' => $data->trang_thai == 1
                ? 'Mở đợt đánh giá thành công'
                : 'Khóa đợt đánh giá thành công'
        ]);
    }
}
