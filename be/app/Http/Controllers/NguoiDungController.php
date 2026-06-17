<?php

namespace App\Http\Controllers;

use App\Models\NguoiDung;
use App\Models\SinhVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NguoiDungController extends Controller
{
    public function dangNhap(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ]);

        $user = NguoiDung::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Thông tin đăng nhập không chính xác.',
            ], 401);
        }

        if ($user->trang_thai !== 'hoat_dong') {
            return response()->json([
                'status' => false,
                'message' => 'Tài khoản của bạn hiện không hoạt động.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'so_dien_thoai' => $user->so_dien_thoai,
                'ngay_sinh' => $user->ngay_sinh,
                'avatar' => $user->avatar,
                'vai_tro_id' => $user->vai_tro_id,
                'trang_thai' => $user->trang_thai,
            ],
        ]);
    }

    public function dangKy(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'student_id' => 'required|string|max:50|unique:sinh_vien,ma_sinh_vien',
            'email' => 'required|email|unique:nguoi_dung,email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'fullname.required' => 'Họ và tên không được để trống.',
            'fullname.string' => 'Họ và tên không hợp lệ.',
            'fullname.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'student_id.required' => 'Mã sinh viên không được để trống.',
            'student_id.unique' => 'Mã sinh viên đã tồn tại.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $lopId = DB::table('lop')->value('id');
        if (!$lopId) {
            return response()->json([
                'status' => false,
                'message' => 'Chưa có lớp để đăng ký sinh viên.',
            ], 500);
        }

        DB::beginTransaction();

        try {
            $user = NguoiDung::create([
                'ho_ten' => $request->fullname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'so_dien_thoai' => $request->phone ?? null,
                'ngay_sinh' => $request->birthday ?? null,
                'avatar' => null,
                'vai_tro_id' => DB::table('vai_tro')->where('ten_vai_tro', 'sinh_vien')->value('id'),
                'trang_thai' => 'hoat_dong',
            ]);

            SinhVien::create([
                'ma_sinh_vien' => $request->student_id,
                'nam_trung_tuyen' => now()->year,
                'user_id' => $user->id,
                'lop_id' => $lopId,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Đăng ký thành công.',
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Đăng ký thất bại. Vui lòng thử lại.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function checkToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'user' => [
                'id' => $user->id,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'so_dien_thoai' => $user->so_dien_thoai,
                'ngay_sinh' => $user->ngay_sinh,
                'avatar' => $user->avatar,
                'vai_tro_id' => $user->vai_tro_id,
                'trang_thai' => $user->trang_thai,
            ],
        ]);
    }

    public function thongTinNguoiDung(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'data' => $user,
        ]);
    }

    public function dangXuat(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ.',
            ], 401);
        }

        $user->currentAccessToken()?->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    public function kichHoat(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Chức năng kích hoạt tài khoản hiện chưa được cấu hình.',
        ], 501);
    }

    public function guiOTP(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Chức năng gửi OTP đăng ký hiện chưa được cấu hình.',
        ], 501);
    }

    public function xacNhanOTP(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Chức năng xác nhận OTP hiện chưa được cấu hình.',
        ], 501);
    }

    public function guiOTPQuenMatKhau(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Chức năng gửi OTP quên mật khẩu hiện chưa được cấu hình.',
        ], 501);
    }

    public function xacNhanOTPQuenMatKhau(Request $request)
    {
        return response()->json([
            'status' => false,
            'message' => 'Chức năng xác nhận OTP quên mật khẩu hiện chưa được cấu hình.',
        ], 501);
    }

    public function doiMatKhau(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $user = NguoiDung::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Email không tồn tại.',
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }
}

