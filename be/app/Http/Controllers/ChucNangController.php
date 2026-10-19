<?php

namespace App\Http\Controllers;

use App\Models\ChucNang;
use App\Models\PhanQuyen;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChucNangController extends Controller
{
    /**
     * Kiểm tra người dùng có quyền phân quyền hay không.
     */
    private function kiemTraQuyen(string $maChucNang): bool
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return false;
        }

        $role = $user->vaiTro;

        // Quản trị viên có toàn quyền.
        if ($role && $role->ten_vai_tro === 'quan_tri_vien') {
            return true;
        }

        return PhanQuyen::join(
            'chuc_nang',
            'chuc_nang.id',
            '=',
            'phan_quyen.chuc_nang_id'
        )
            ->where(
                'phan_quyen.vai_tro_id',
                $user->vai_tro_id
            )
            ->where(
                'chuc_nang.ma_chuc_nang',
                $maChucNang
            )
            ->exists();
    }

    /**
     * Response khi không có quyền.
     */
    private function khongCoQuyen(): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => 'Bạn không có quyền thực hiện chức năng này!',
        ], 403);
    }

    /**
     * Lấy toàn bộ chức năng.
     */
    public function getData(): JsonResponse
    {
        if (!$this->kiemTraQuyen('phan_quyen')) {
            return $this->khongCoQuyen();
        }

        $data = ChucNang::orderBy('id', 'ASC')->get();

        return response()->json([
            'status'  => true,
            'message' => 'Lấy danh sách chức năng thành công',
            'data'    => $data,
        ]);
    }

    /**
     * Cập nhật chức năng.
     */
    public function suaChucNang(Request $request): JsonResponse
    {
        if (!$this->kiemTraQuyen('phan_quyen')) {
            return $this->khongCoQuyen();
        }

        $validator = Validator::make(
            $request->all(),
            [
                'id' => [
                    'required',
                    'exists:chuc_nang,id',
                ],

                'ten_chuc_nang' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'mo_ta' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'trang_thai' => [
                    'required',
                    Rule::in([
                        'hoat_dong',
                        'tam_dung',
                    ]),
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = ChucNang::find($request->id);

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Chức năng không tồn tại',
            ], 404);
        }

        $data->ten_chuc_nang =
            $request->ten_chuc_nang;

        $data->mo_ta =
            $request->mo_ta;

        $data->trang_thai =
            $request->trang_thai;

        $data->save();

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật chức năng thành công',
            'data'    => $data,
        ]);
    }
}
