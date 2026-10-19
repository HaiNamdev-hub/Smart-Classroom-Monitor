<?php

namespace App\Http\Controllers;

use App\Models\PhanQuyen;
use App\Models\VaiTro;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PhanQuyenController extends Controller
{
    /**
     * Kiểm tra người dùng hiện tại có quyền
     * quản lý phân quyền hay không.
     */
    private function checkQuyenPhanQuyen(): bool
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return false;
        }

        $role = VaiTro::find($user->vai_tro_id);

        // Quản trị viên mặc định có toàn quyền.
        if (
            $role &&
            $role->ten_vai_tro === 'quan_tri_vien'
        ) {
            return true;
        }

        return PhanQuyen::join(
            'chuc_nang',
            'phan_quyen.chuc_nang_id',
            '=',
            'chuc_nang.id'
        )
            ->where(
                'phan_quyen.vai_tro_id',
                $user->vai_tro_id
            )
            ->where(
                'chuc_nang.ma_chuc_nang',
                'phan_quyen'
            )
            ->exists();
    }

    /**
     * Cấp quyền.
     */
    public function themPhanQuyen(
        Request $request
    ): JsonResponse {
        if (!$this->checkQuyenPhanQuyen()) {
            return response()->json([
                'status'  => false,
                'message' =>
                'Bạn không có quyền thực hiện chức năng này!',
            ], 403);
        }

        $validated = $request->validate([
            'vai_tro_id' => [
                'required',
                'integer',
                'exists:vai_tro,id',
            ],

            'chuc_nang_id' => [
                'required',
                'integer',
                'exists:chuc_nang,id',
            ],
        ]);

        $role = VaiTro::find(
            $validated['vai_tro_id']
        );

        /*
         * Không cần lưu quyền riêng cho quản trị viên,
         * vì quản trị viên mặc định có toàn quyền.
         */
        if (
            $role &&
            $role->ten_vai_tro === 'quan_tri_vien'
        ) {
            return response()->json([
                'status'  => false,
                'message' =>
                'Quản trị viên mặc định có toàn quyền.',
            ], 422);
        }

        PhanQuyen::firstOrCreate([
            'vai_tro_id' =>
            $validated['vai_tro_id'],

            'chuc_nang_id' =>
            $validated['chuc_nang_id'],
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Đã cấp quyền thành công!',
        ]);
    }

    /**
     * Gỡ quyền.
     */
    public function xoaPhanQuyen(
        Request $request
    ): JsonResponse {
        if (!$this->checkQuyenPhanQuyen()) {
            return response()->json([
                'status'  => false,
                'message' =>
                'Bạn không có quyền thực hiện chức năng này!',
            ], 403);
        }

        $validated = $request->validate([
            'vai_tro_id' => [
                'required',
                'integer',
                'exists:vai_tro,id',
            ],

            'chuc_nang_id' => [
                'required',
                'integer',
                'exists:chuc_nang,id',
            ],
        ]);

        $role = VaiTro::find(
            $validated['vai_tro_id']
        );

        if (
            $role &&
            $role->ten_vai_tro === 'quan_tri_vien'
        ) {
            return response()->json([
                'status'  => false,
                'message' =>
                'Không thể gỡ quyền của quản trị viên.',
            ], 422);
        }

        $data = PhanQuyen::where(
            'vai_tro_id',
            $validated['vai_tro_id']
        )
            ->where(
                'chuc_nang_id',
                $validated['chuc_nang_id']
            )
            ->first();

        if (!$data) {
            return response()->json([
                'status'  => false,
                'message' => 'Quyền không tồn tại!',
            ], 404);
        }

        $data->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Đã gỡ quyền thành công!',
        ]);
    }

    /**
     * Lấy quyền của một vai trò.
     */
    public function getChiTietPhanQuyen(
        Request $request
    ): JsonResponse {
        if (!$this->checkQuyenPhanQuyen()) {
            return response()->json([
                'status'  => false,
                'message' =>
                'Bạn không có quyền thực hiện chức năng này!',
            ], 403);
        }

        $validated = $request->validate([
            'vai_tro_id' => [
                'required',
                'integer',
                'exists:vai_tro,id',
            ],
        ]);

        $data = PhanQuyen::where(
            'phan_quyen.vai_tro_id',
            $validated['vai_tro_id']
        )
            ->join(
                'chuc_nang',
                'chuc_nang.id',
                '=',
                'phan_quyen.chuc_nang_id'
            )
            ->select(
                'phan_quyen.id',
                'phan_quyen.vai_tro_id',
                'phan_quyen.chuc_nang_id',
                'chuc_nang.ma_chuc_nang',
                'chuc_nang.ten_chuc_nang',
                'chuc_nang.mo_ta',
                'chuc_nang.trang_thai'
            )
            ->orderBy(
                'chuc_nang.id',
                'ASC'
            )
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }

    /**
     * Kiểm tra người dùng hiện tại có một
     * chức năng cụ thể hay không.
     */
    public function checkQuyen(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'ma_chuc_nang' => [
                'required',
                'string',
            ],
        ]);

        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Chưa đăng nhập',
            ], 401);
        }

        $role = VaiTro::find(
            $user->vai_tro_id
        );

        // Quản trị viên có toàn quyền.
        if (
            $role &&
            $role->ten_vai_tro === 'quan_tri_vien'
        ) {
            return response()->json([
                'status'  => true,
                'message' => 'Có quyền',
            ]);
        }

        $check = PhanQuyen::join(
            'chuc_nang',
            'phan_quyen.chuc_nang_id',
            '=',
            'chuc_nang.id'
        )
            ->where(
                'phan_quyen.vai_tro_id',
                $user->vai_tro_id
            )
            ->where(
                'chuc_nang.ma_chuc_nang',
                $validated['ma_chuc_nang']
            )
            ->where(
                'chuc_nang.trang_thai',
                'hoat_dong'
            )
            ->exists();

        return response()->json([
            'status' => $check,

            'message' => $check
                ? 'Có quyền'
                : 'Bạn không có quyền truy cập chức năng này',
        ]);
    }
}
