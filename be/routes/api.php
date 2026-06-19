<?php

use App\Http\Controllers\NguoiDungController;
use App\Http\Controllers\FileDanhSachLopController;
use App\Http\Controllers\LichSuKiemTraController;
use Illuminate\Support\Facades\Route;





// Đăng nhập
Route::post('/dang-nhap', [NguoiDungController::class, 'dangNhap']);

// Kích hoạt tài khoản
Route::get('/kich-hoat/{token}', [NguoiDungController::class, 'kichHoat']);

// Gửi OTP đăng ký (không sử dụng nếu dùng link xác nhận email)
Route::post('/gui-otp-dang-ky', [NguoiDungController::class, 'guiOTP']);

// Xác nhận OTP đăng ký (không sử dụng nếu dùng link xác nhận email)
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
    //api  người dùng
    Route::get('/admin/nguoi-dung', [NguoiDungController::class, 'getNguoiDung']);
    Route::get('/admin/nguoi-dung/chi-tiet-nguoi-dung', [NguoiDungController::class, 'chiTietNguoiDung']);
    Route::post('/admin/nguoi-dung/them-nguoi-dung', [NguoiDungController::class, 'themNguoiDung']);
    Route::post('/admin/nguoi-dung/sua-nguoi-dung', [NguoiDungController::class, 'suaNguoiDung']);
    Route::post('/admin/nguoi-dung/xoa-nguoi-dung', [NguoiDungController::class, 'xoaNguoiDung']);
    Route::post('/admin/nguoi-dung/tim-kiem', [NguoiDungController::class, 'timKiem']);

    Route::apiResource('/admin/file-danh-sach-lop', FileDanhSachLopController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::apiResource('/admin/lich-su-kiem-tra', LichSuKiemTraController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

});