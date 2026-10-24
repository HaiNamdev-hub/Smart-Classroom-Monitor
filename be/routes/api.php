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
use App\Http\Controllers\ChucNangController;
use App\Http\Controllers\PhanQuyenController;
use App\Http\Controllers\SinhVienController;
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





    
    //SINH VIÊN
    Route::get(
        '/sinh-vien/trang-chu/get-data',
        [SinhVienController::class, 'getTrangChu']
    );
    Route::get(
        '/sinh-vien/lop-hoc',
        [LopHocController::class, 'getLopHocSinhVien']
    );
    Route::get(
        '/sinh-vien/bai-tap',
        [BaiTapController::class, 'getBaiTapSinhVien']
    );
    Route::get(
        '/sinh-vien/bai-nop',
        [BaiNopController::class, 'getBaiNopSinhVien']
    );
    Route::post(
        '/sinh-vien/bai-tap/{baiTapId}/nop-bai',
        [BaiNopController::class, 'nopBai']
    );
    Route::get(
        '/sinh-vien/diem',
        [DiemController::class, 'getDiemSinhVien']
    );

    // Chỉ tài khoản quản trị viên đang hoạt động được dùng các API này.
    Route::middleware(QuanTriVien::class)->group(function () {
        Route::get('/admin/trang-chu/get-data', [AdminController::class, 'getData']);

        Route::get('/admin/nguoi-dung', [NguoiDungController::class, 'getNguoiDung']);
        Route::post('/admin/nguoi-dung/them-nguoi-dung', [NguoiDungController::class, 'themNguoiDung']);
        Route::post('/admin/nguoi-dung/sua-nguoi-dung/{id}', [NguoiDungController::class, 'suaNguoiDung']);
        Route::post('/admin/nguoi-dung/xoa-nguoi-dung/{id}', [NguoiDungController::class, 'xoaNguoiDung']);


        // ================================
        // PHÂN QUYỀN
        // ================================

        // Vai trò
        Route::get(
            '/admin/vai-tro/get-data',
            [VaiTroController::class, 'getData']
        );

        // Chức năng
        Route::get(
            '/admin/phan-quyen/chuc-nang/get-data',
            [ChucNangController::class, 'getData']
        );

        Route::post(
            '/admin/phan-quyen/chuc-nang/update',
            [ChucNangController::class, 'suaChucNang']
        );

        // Cấp quyền
        Route::post(
            '/admin/phan-quyen/chi-tiet-phan-quyen/add-data',
            [PhanQuyenController::class, 'themPhanQuyen']
        );

        // Gỡ quyền
        Route::post(
            '/admin/phan-quyen/chi-tiet-phan-quyen/delete',
            [PhanQuyenController::class, 'xoaPhanQuyen']
        );

        // Danh sách quyền của vai trò
        Route::post(
            '/admin/phan-quyen/chi-tiet-phan-quyen/get-data',
            [PhanQuyenController::class, 'getChiTietPhanQuyen']
        );

        // Kiểm tra quyền
        Route::post(
            '/admin/check-quyen',
            [PhanQuyenController::class, 'checkQuyen']
        );

        Route::get('/admin/lop-hoc', [LopHocController::class, 'getLopHoc']);
        Route::get('/admin/lop-hoc/giang-vien', [LopHocController::class, 'getGiangVien']);
        Route::post('/admin/lop-hoc/them-lop-hoc', [LopHocController::class, 'themLopHoc']);
        Route::post('/admin/lop-hoc/sua-lop-hoc/{id}', [LopHocController::class, 'suaLopHoc']);
        Route::post('/admin/lop-hoc/xoa-lop-hoc/{id}', [LopHocController::class, 'xoaLopHoc']);

        Route::get('/admin/vai-tro', [VaiTroController::class, 'getVaiTro']);
        Route::post('/admin/vai-tro/sua-vai-tro/{id}', [VaiTroController::class, 'suaVaiTro']);
    });
});
