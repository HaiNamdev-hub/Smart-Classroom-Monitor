<?php

use App\Http\Controllers\AnhManHinhController;
use App\Http\Controllers\BaiNopController;
use App\Http\Controllers\BaiTapController;
use App\Http\Controllers\CanhBaoController;
use App\Http\Controllers\DiemController;
use App\Http\Controllers\FileBaiTapController;
use App\Http\Controllers\HoatDongController;
use App\Http\Controllers\LopHocController;
use App\Http\Controllers\NguoiDungController;
use App\Http\Controllers\PhienGiamSatController;
use App\Http\Controllers\ThanhVienLopController;
use App\Http\Controllers\VaiTroController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\QuanTriVien;

/*
|--------------------------------------------------------------------------
| ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/



Route::post('/dang-nhap', [NguoiDungController::class, 'dangNhap']);


// Các API yêu cầu token đăng nhập hợp lệ.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/check-token', [NguoiDungController::class, 'checkToken']);
    Route::get('/thong-tin', [NguoiDungController::class, 'thongTinNguoiDung']);
    Route::post('/dang-xuat', [NguoiDungController::class, 'dangXuat']);

    // Chỉ tài khoản quản trị viên đang hoạt động được dùng các API này.
    Route::middleware(QuanTriVien::class)->group(function () {
        Route::get('/admin/trang-chu/get-data', [AdminController::class, 'getData']);

        Route::get('/admin/nguoi-dung', [NguoiDungController::class, 'getNguoiDung']);
        Route::post('/admin/nguoi-dung/them-nguoi-dung', [NguoiDungController::class, 'themNguoiDung']);
        Route::post('/admin/nguoi-dung/sua-nguoi-dung/{id}', [NguoiDungController::class, 'suaNguoiDung']);
        Route::post('/admin/nguoi-dung/xoa-nguoi-dung/{id}', [NguoiDungController::class, 'xoaNguoiDung']);

        Route::get('/admin/lop-hoc', [LopHocController::class, 'getLopHoc']);
        Route::get('/admin/lop-hoc/giang-vien', [LopHocController::class, 'getGiangVien']);
        Route::post('/admin/lop-hoc/them-lop-hoc', [LopHocController::class, 'themLopHoc']);
        Route::post('/admin/lop-hoc/sua-lop-hoc/{id}', [LopHocController::class, 'suaLopHoc']);

        Route::get('/admin/vai-tro', [VaiTroController::class, 'getVaiTro']);
        Route::post('/admin/vai-tro/sua-vai-tro/{id}', [VaiTroController::class, 'suaVaiTro']);
    });
});
