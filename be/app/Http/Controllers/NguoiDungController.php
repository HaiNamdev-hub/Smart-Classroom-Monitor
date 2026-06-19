<?php

namespace App\Http\Controllers;

use App\Mail\MasterMail;
use App\Models\NguoiDung;
use App\Models\SinhVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

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

    public function kichHoat($token)
    {
        $user = NguoiDung::where('verification_token', $token)->first();
        $frontend = env('FRONTEND_URL', config('app.url'));

        if (!$user) {
            return redirect($frontend . '/dang-nhap?verified=0');
        }

        if ($user->email_verified_at !== null && $user->trang_thai === 'hoat_dong') {
            return redirect($frontend . '/dang-nhap?verified=already');
        }

        $user->update([
            'email_verified_at' => now(),
            'trang_thai' => 'hoat_dong',
            'verification_token' => null,
        ]);

        return redirect($frontend . '/dang-nhap?verified=1');
    }

    public function guiOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        if (NguoiDung::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Email đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.',
            ], 422);
        }

        $code = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('registration_verification_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $code,
                'created_at' => Carbon::now(),
            ]
        );

        try {
            Mail::to($request->email)->send(new MasterMail(
                'Mã xác nhận đăng ký',
                'otpDangKy',
                [
                    'ho_ten' => $request->email,
                    'code' => $code,
                    'link' => env('FRONTEND_URL', config('app.url')) . '/dang-ky',
                ]
            ));
        } catch (\Exception $exception) {
            return response()->json([
                'status' => false,
                'message' => 'Gửi email thất bại. Vui lòng kiểm tra cấu hình mail.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mã xác nhận đã được gửi đến email của bạn.',
        ]);
    }

    public function xacNhanOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'code.required' => 'Mã xác nhận không được để trống.',
            'code.digits' => 'Mã xác nhận phải có 6 chữ số.',
        ]);

        $record = DB::table('registration_verification_tokens')->where('email', $request->email)->first();

        if (!$record || $record->token !== $request->code) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác nhận không đúng hoặc đã hết hạn.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác nhận đã hết hạn. Vui lòng yêu cầu lại.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mã xác nhận hợp lệ.',
        ]);
    }

    public function guiOTPQuenMatKhau(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        $user = NguoiDung::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Email không tồn tại.',
            ], 404);
        }

        $code = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $code,
                'created_at' => Carbon::now(),
            ]
        );

        try {
            Mail::to($user->email)->send(new MasterMail(
                'Yêu cầu đặt lại mật khẩu',
                'quenMK',
                [
                    'ho_ten' => $user->ho_ten,
                    'code' => $code,
                    'link' => env('FRONTEND_URL', config('app.url')) . '/quen-mat-khau',
                    'expired_at' => $expiresAt->format('H:i d/m/Y'),
                ]
            ));
        } catch (\Exception $exception) {
            return response()->json([
                'status' => false,
                'message' => 'Gửi email thất bại. Vui lòng kiểm tra cấu hình mail.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mã xác thực đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
        ]);
    }

    public function xacNhanOTPQuenMatKhau(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'code.required' => 'Mã xác thực không được để trống.',
            'code.digits' => 'Mã xác thực phải có 6 chữ số.',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (!$record || $record->token !== $request->code) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác thực không đúng hoặc đã hết hạn.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác thực đã hết hạn. Vui lòng yêu cầu lại.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mã xác thực hợp lệ.',
        ]);
    }

    public function doiMatKhau(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'code.required' => 'Mã xác thực không được để trống.',
            'code.digits' => 'Mã xác thực phải có 6 chữ số.',
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

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (!$record || $record->token !== $request->code) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác thực không đúng hoặc đã hết hạn.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác thực đã hết hạn. Vui lòng yêu cầu lại.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }
}

