<?php

namespace App\Http\Controllers;

use App\Models\ChucNang;
use App\Models\PhanQuyen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChucNangController extends Controller
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

    public function getData()
    {
        if (!$this->kiemTraQuyen('phan_quyen')) {
            return $this->khongCoQuyen();
        }

        $data = ChucNang::orderBy('id', 'ASC')->get();

        return response()->json([
            'status'  => true,
            'message' => 'Lấy dữ liệu thành công',
            'data'    => $data,
        ]);
    }


    public function suaChucNang(Request $request)
    {
        if (!$this->kiemTraQuyen('phan_quyen')) {
            return $this->khongCoQuyen();
        }

        $data = ChucNang::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Chức năng không tồn tại'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id'            => ['required', 'exists:chuc_nang,id'],
            'ten_chuc_nang' => ['required', 'string', 'max:255'],
            'mo_ta'         => ['nullable', 'string'],
            'trang_thai'    => ['required', Rule::in(['hoat_dong', 'tam_dung'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data->ten_chuc_nang = $request->ten_chuc_nang;
        $data->mo_ta = $request->mo_ta;
        $data->trang_thai = $request->trang_thai;

        $data->save();

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật chức năng thành công',
            'data'    => $data,
        ]);
    }
}
