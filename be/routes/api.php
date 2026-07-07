<?php

use App\Http\Controllers\DotDanhGiaController;
use App\Http\Controllers\NguoiDungController;
use App\Http\Controllers\FileDanhSachLopController;
use App\Http\Controllers\KhoaController;
use App\Http\Controllers\LichSuKiemTraController;
use App\Http\Controllers\LopController;
use App\Http\Controllers\NganhController;
use App\Http\Controllers\NhomTieuChiController;
use App\Http\Controllers\PhieuRenLuyenController;
use App\Http\Controllers\TieuChiController;
use App\Http\Controllers\TieuChiLuaChonController;
use App\Http\Controllers\PhanQuyenController;
use App\Http\Controllers\ChucNangController;
use App\Http\Controllers\MinhChungController;
use App\Http\Controllers\SinhVienController;
use App\Http\Controllers\SinhVienTrangChuController;
use App\Http\Controllers\TrangChuController;
use App\Http\Controllers\VaiTroController;
use App\Models\SinhVien;
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
    //api QLDT Khoa
    Route::get('/admin/khoa', [KhoaController::class, 'getKhoa']);
    Route::get('/admin/khoa/chi-tiet-khoa', [KhoaController::class, 'chiTietKhoa']);
    Route::post('/admin/khoa/them-khoa', [KhoaController::class, 'themKhoa']);
    Route::post('/admin/khoa/sua-khoa', [KhoaController::class, 'suaKhoa']);
    Route::post('/admin/khoa/xoa-khoa', [KhoaController::class, 'xoaKhoa']);
    Route::post('/admin/khoa/tim-kiem', [KhoaController::class, 'timKiem']);



    //api QLDT Ngành
    Route::get('/admin/nganh', [NganhController::class, 'getNganh']);
    Route::get('/admin/nganh/chi-tiet-nganh', [NganhController::class, 'chiTietNganh']);
    Route::post('/admin/nganh/them-nganh', [NganhController::class, 'themNganh']);
    Route::post('/admin/nganh/sua-nganh', [NganhController::class, 'suaNganh']);
    Route::post('/admin/nganh/xoa-nganh', [NganhController::class, 'xoaNganh']);
    Route::post('/admin/nganh/tim-kiem', [NganhController::class, 'timKiem']);

    //api QLDT lop
    Route::get('/admin/lop', [LopController::class, 'getLop']);
    Route::get('/admin/lop/chi-tiet-lop', [LopController::class, 'chiTietLop']);
    Route::post('/admin/lop/them-lop', [LopController::class, 'themLop']);
    Route::post('/admin/lop/sua-lop', [LopController::class, 'suaLop']);
    Route::post('/admin/lop/xoa-lop', [LopController::class, 'xoaLop']);
    Route::post('/admin/lop/tim-lop', [LopController::class, 'timKiem']);




    //api QL đợt đánh giá
    Route::get('/admin/dotdanhgia', [DotDanhGiaController::class, 'getdotdanhga']);
    Route::get('/admin/dotdanhgia/chi-tiet-dotdanhgia', [DotDanhGiaController::class, 'chiTietDotDanhGia']);
    Route::post('/admin/dotdanhgia/them-dotdanhgia', [DotDanhGiaController::class, 'themDotDanhGia']);
    Route::post('/admin/dotdanhgia/sua-dotdanhgia', [DotDanhGiaController::class, 'suaDotDanhGia']);
    Route::post('/admin/dotdanhgia/xoa-dotdanhgia', [DotDanhGiaController::class, 'xoaDotDanhGia']);
    Route::post('/admin/dotdanhgia/tim-dotdanhgia', [DotDanhGiaController::class, 'timKiemDotDanhGia']);
    Route::post('/admin/dotdanhgia/khoa-dotdanhgia', [DotDanhGiaController::class, 'khoaDotDanhGia']);

    //api Ql tiêu chí
    //nhom tieu chi
    Route::get('/admin/quanlytieuchi/nhomtieuchi', [NhomTieuChiController::class, 'getnhomtieuchi']);
    Route::get('/admin/quanlytieuchi/chi-tiet-nhomtieuchi', [NhomTieuChiController::class, 'chiTietNhomTieuChi']);
    Route::post('/admin/quanlytieuchi/them-nhomtieuchi', [NhomTieuChiController::class, 'themNhomTieuChi']);
    Route::post('/admin/quanlytieuchi/sua-nhomtieuchi', [NhomTieuChiController::class, 'suaNhomTieuChi']);
    Route::post('/admin/quanlytieuchi/xoa-nhomtieuchi', [NhomTieuChiController::class, 'xoaNhomTieuChi']);
    Route::post('/admin/quanlytieuchi/tim-nhomtieuchi', [NhomTieuChiController::class, 'timKiemNhomTieuChi']);


    //tieu chi
    Route::get('/admin/quanlytieuchi/tieuchi', [TieuChiController::class, 'gettieuchi']);
    Route::get('/admin/quanlytieuchi/chi-tiet-tieuchi', [TieuChiController::class, 'chiTietTieuChi']);
    Route::post('/admin/quanlytieuchi/them-tieuchi', [TieuChiController::class, 'themTieuChi']);
    Route::post('/admin/quanlytieuchi/sua-tieuchi', [TieuChiController::class, 'suaTieuChi']);
    Route::post('/admin/quanlytieuchi/xoa-tieuchi', [TieuChiController::class, 'xoaTieuChi']);
    Route::post('/admin/quanlytieuchi/tim-tieuchi', [TieuChiController::class, 'timKiemTieuChi']);


    //lua chon
    Route::get('/admin/quanlytieuchi/luachon', [TieuChiLuaChonController::class, 'getluachon']);
    Route::get('/admin/quanlytieuchi/chi-tiet-luachon', [TieuChiLuaChonController::class, 'chiTietLuaChon']);
    Route::post('/admin/quanlytieuchi/them-luachon', [TieuChiLuaChonController::class, 'themLuaChon']);
    Route::post('/admin/quanlytieuchi/sua-luachon', [TieuChiLuaChonController::class, 'suaLuaChon']);
    Route::post('/admin/quanlytieuchi/xoa-luachon', [TieuChiLuaChonController::class, 'xoaLuaChon']);
    Route::post('/admin/quanlytieuchi/tim-luachon', [TieuChiLuaChonController::class, 'timKiemLuaChon']);


    //api phiếu chờ duyệt
    Route::get('/admin/phieu-cho-duyet', [PhieuRenLuyenController::class, 'getPhieuChoDuyet']);
    Route::get('/admin/phieu-cho-duyet/chi-tiet', [PhieuRenLuyenController::class, 'chiTietPhieu']);
    Route::post('/admin/phieu-cho-duyet/duyet', [PhieuRenLuyenController::class, 'duyetPhieu']);
    Route::post('/admin/phieu-cho-duyet/tra-lai', [PhieuRenLuyenController::class, 'traLaiPhieu']);
    Route::post('/admin/phieu-cho-duyet/tim-kiem', [PhieuRenLuyenController::class, 'timkiem']);


    // PHAN QUYEN :
    Route::get('/admin/vai-tro/get-data', [VaiTroController::class, 'getData']);

    Route::get('/admin/phan-quyen/chuc-nang/get-data', [ChucNangController::class, 'getData']);
    Route::post('/admin/phan-quyen/chi-tiet-phan-quyen/add-data', [PhanQuyenController::class, 'themPhanQuyen']);
    Route::post('/admin/phan-quyen/chi-tiet-phan-quyen/delete', [PhanQuyenController::class, 'xoaPhanQuyen']);
    Route::post('/admin/phan-quyen/chi-tiet-phan-quyen/get-data', [PhanQuyenController::class, 'getChiTietPhanQuyen']);
    Route::post('/admin/check-quyen', [PhanQuyenController::class, 'checkQuyen']);


    //chức năng
    Route::post('/admin/phan-quyen/chuc-nang/update', [ChucNangController::class, 'suaChucNang']);

    //api  người dùng
    Route::get('/admin/nguoi-dung', [NguoiDungController::class, 'getNguoiDung']);
    Route::get('/admin/nguoi-dung/chi-tiet-nguoi-dung', [NguoiDungController::class, 'chiTietNguoiDung']);
    Route::post('/admin/nguoi-dung/them-nguoi-dung', [NguoiDungController::class, 'themNguoiDung']);
    Route::post('/admin/nguoi-dung/sua-nguoi-dung', [NguoiDungController::class, 'suaNguoiDung']);
    Route::post('/admin/nguoi-dung/xoa-nguoi-dung', [NguoiDungController::class, 'xoaNguoiDung']);
    Route::post('/admin/nguoi-dung/tim-kiem', [NguoiDungController::class, 'timKiem']);


    // api minh chứng
    Route::get('/admin/minh-chung/xem/{id}', [MinhChungController::class, 'xem']);
    Route::get('/admin/minh-chung/tai/{id}', [MinhChungController::class, 'tai']);


    //api trang chu admin
    Route::get('/admin/trang-chu/get-data', [TrangChuController::class, 'getData']);




    //api trang chu sinh vien
    Route::get('/sinh-vien/trang-chu/get-data', [SinhVienTrangChuController::class, 'getData']);

    //api phieu ren luyen sinh vien
    Route::get('/sinh-vien/phieu-ren-luyen/get-data', [SinhVienController::class, 'getData']);
    Route::post('/sinh-vien/phieu-ren-luyen/luu-nhap', [SinhVienController::class, 'luuNhap']);
    Route::post('/sinh-vien/phieu-ren-luyen/gui-phieu', [SinhVienController::class, 'guiPhieu']);
    Route::post('/sinh-vien/phieu-ren-luyen/upload-minh-chung', [SinhVienController::class, 'uploadMinhChung']);
    Route::post('/sinh-vien/phieu-ren-luyen/xoa-minh-chung', [SinhVienController::class, 'xoaMinhChung']);



    //api kết quả rèn luyện 
    Route::get('/sinh-vien/ket-qua-ren-luyen/get-data', [SinhVienController::class, 'getKetQuaRenLuyen']);

    //api Phiếu lớp chờ đánh giá cho cán sự lớp 
    //api Phiếu lớp chờ đánh giá dành cho lớp trưởng / bí thư
    Route::get('/can-su-lop/phieu-ren-luyen/get-data', [SinhVienController::class, 'getPhieuCanSuLop']);
    Route::get('/can-su-lop/phieu-ren-luyen/chi-tiet', [SinhVienController::class, 'chiTietPhieuCanSuLop']);
    Route::post('/can-su-lop/phieu-ren-luyen/danh-gia', [SinhVienController::class, 'danhGiaPhieuCanSuLop']);


    //File danh sách lớp
    Route::get('/file-danh-sach-lop', [FileDanhSachLopController::class, 'index']);
    Route::post('/file-danh-sach-lop', [FileDanhSachLopController::class, 'store']);
    Route::get('/file-danh-sach-lop/{id}', [FileDanhSachLopController::class, 'show']);
    Route::put('/file-danh-sach-lop/{id}', [FileDanhSachLopController::class, 'update']);
    Route::delete('/file-danh-sach-lop/{id}', [FileDanhSachLopController::class, 'destroy']);
    Route::get('/file-danh-sach-lop/{id}/download', [FileDanhSachLopController::class, 'download']);
    Route::post('/file-danh-sach-lop/{id}/replace', [FileDanhSachLopController::class, 'replace']);
    Route::get('/file-danh-sach-lop/{id}/doc-du-lieu', [FileDanhSachLopController::class, 'docDuLieu']);

    Route::apiResource('/admin/lich-su-kiem-tra', LichSuKiemTraController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
});
