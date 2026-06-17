<?php

use App\Http\Controllers\NguoiDungController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

// Đăng nhập
Route::post('/dang-nhap', [NguoiDungController::class, 'dangNhap']);

// Đăng ký
Route::post('/dang-ky', [NguoiDungController::class, 'dangKy']);

// Kích hoạt tài khoản
Route::post('/kich-hoat', [NguoiDungController::class, 'kichHoat']);

// Gửi OTP đăng ký
Route::post('/gui-otp-dang-ky', [NguoiDungController::class, 'guiOTP']);

// Xác nhận OTP đăng ký
Route::post('/xac-nhan-otp', [NguoiDungController::class, 'xacNhanOTP']);

/*
|--------------------------------------------------------------------------
| QUÊN MẬT KHẨU
|--------------------------------------------------------------------------
*/

// Gửi OTP quên mật khẩu
Route::post(
    '/quen-mat-khau/gui-otp',
    [NguoiDungController::class, 'guiOTPQuenMatKhau']
);

// Xác nhận OTP quên mật khẩu
Route::post(
    '/quen-mat-khau/xac-nhan-otp',
    [NguoiDungController::class, 'xacNhanOTPQuenMatKhau']
);

// Đổi mật khẩu mới
Route::post(
    '/quen-mat-khau/doi-mat-khau',
    [NguoiDungController::class, 'doiMatKhau']
);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (Yêu cầu đăng nhập)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Kiểm tra token còn hiệu lực
    Route::get(
        '/check-token',
        [NguoiDungController::class, 'checkToken']
    );

    // Lấy thông tin người dùng hiện tại
    Route::get(
        '/thong-tin',
        [NguoiDungController::class, 'thongTinNguoiDung']
    );

    // Đăng xuất
    Route::post(
        '/dang-xuat',
        [NguoiDungController::class, 'dangXuat']
    );
});