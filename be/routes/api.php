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
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChucNangController;
use App\Http\Controllers\PhanQuyenController;
use App\Http\Controllers\SinhVienController;
use App\Http\Controllers\GiangVienApiController;
use App\Http\Controllers\GiangVienCanhBaoCameraController;
use App\Http\Controllers\GiangVienCauHoiController;
use App\Http\Controllers\GiangVienCauHinhTronDeController;
use App\Http\Controllers\GiangVienChamDiemController;
use App\Http\Controllers\GiangVienLichKiemTraController;
use App\Http\Controllers\GiangVienNhapCauHoiController;
use App\Http\Controllers\GiangVienViPhamKiemTraController;
use App\Http\Controllers\SinhVienCanhBaoCameraController;
use App\Http\Controllers\SinhVienBaoVeBaiKiemTraController;
use App\Http\Controllers\SinhVienKiemTraController;
use App\Http\Controllers\WebRtcSignalController;
use App\Http\Controllers\ExamEvidenceFileController;
use App\Http\Middleware\QuanTriVien;
use App\Http\Middleware\GiangVien;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

Route::post('/dang-nhap', [
    NguoiDungController::class,
    'dangNhap'
]);

/*
|--------------------------------------------------------------------------
| API YÊU CẦU ĐĂNG NHẬP
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {






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

    Route::get(
        '/sinh-vien/bai-kiem-tra/{baiTapId}',
        [SinhVienKiemTraController::class, 'show']
    );

    Route::put(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/nhap',
        [SinhVienKiemTraController::class, 'saveDraft']
    );

    Route::post(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/nop',
        [SinhVienKiemTraController::class, 'submit']
    );

    Route::post(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/bat-dau',
        [SinhVienKiemTraController::class, 'startExam']
    )->middleware('throttle:10,1');


    Route::get(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/trang-thai',
        [SinhVienKiemTraController::class, 'examViolationState']
    );

    Route::post(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/roi-tab',
        [SinhVienKiemTraController::class, 'reportTabLeave']
    );
    Route::post(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/bao-ve',
        [SinhVienBaoVeBaiKiemTraController::class, 'store']
    )->middleware('throttle:60,1');

    // Chỉ tài khoản quản trị viên đang hoạt động được dùng các API này.
    Route::get('/check-token', [
        NguoiDungController::class,
        'checkToken'
    ]);

    Route::get('/thong-tin', [
        NguoiDungController::class,
        'thongTinNguoiDung'
    ]);

    Route::post('/dang-xuat', [
        NguoiDungController::class,
        'dangXuat'
    ]);
    // SINH VIÊN - GỬI CẢNH BÁO CAMERA
    Route::post(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/canh-bao-camera',
        [SinhVienCanhBaoCameraController::class, 'store']
    )->middleware('throttle:30,1');
    Route::post(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/canh-bao-camera/{eventId}/anh',
        [SinhVienCanhBaoCameraController::class, 'storeEvidence']
    )->middleware('throttle:20,1');
    Route::post(
        '/sinh-vien/bai-kiem-tra/{baiTapId}/canh-bao-camera/{eventId}/anh-that-bai',
        [SinhVienCanhBaoCameraController::class, 'markEvidenceFailed']
    )->middleware('throttle:30,1');

    // WEBRTC SIGNALING CHO GIÁM SÁT THI (SANCTUM PROTECTED)
    Route::post(
        '/webrtc/signal',
        [WebRtcSignalController::class, 'signal']
    );

    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    Route::middleware(QuanTriVien::class)->group(function () {

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
        Route::get(
            '/admin/trang-chu/get-data',
            [AdminController::class, 'getData']
        );

        // Người dùng
        Route::get(
            '/admin/nguoi-dung',
            [NguoiDungController::class, 'getNguoiDung']
        );

        Route::post(
            '/admin/nguoi-dung/them-nguoi-dung',
            [NguoiDungController::class, 'themNguoiDung']
        );

        Route::post(
            '/admin/nguoi-dung/sua-nguoi-dung/{id}',
            [NguoiDungController::class, 'suaNguoiDung']
        );

        // Lớp học
        Route::get(
            '/admin/lop-hoc',
            [LopHocController::class, 'getLopHoc']
        );

        Route::get(
            '/admin/lop-hoc/giang-vien',
            [LopHocController::class, 'getGiangVien']
        );

        Route::post(
            '/admin/lop-hoc/them-lop-hoc',
            [LopHocController::class, 'themLopHoc']
        );

        Route::post(
            '/admin/lop-hoc/sua-lop-hoc/{id}',
            [LopHocController::class, 'suaLopHoc']
        );

        // Vai trò
        Route::get(
            '/admin/vai-tro',
            [VaiTroController::class, 'getVaiTro']
        );

        Route::post(
            '/admin/vai-tro/sua-vai-tro/{id}',
            [VaiTroController::class, 'suaVaiTro']
        );
    });
});

/*
|--------------------------------------------------------------------------
| API RIÊNG CHO GIẢNG VIÊN
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    GiangVien::class
])->prefix('giang-vien')->group(function () {

    Route::get('/bai-tap/{baiTapId}/hinh-thuc-kiem-tra', [\App\Http\Controllers\GiangVienExamModeController::class, 'show'])->whereNumber('baiTapId');
    Route::put('/bai-tap/{baiTapId}/hinh-thuc-kiem-tra', [\App\Http\Controllers\GiangVienExamModeController::class, 'update'])->whereNumber('baiTapId');

    Route::get('/bai-tap/{baiTapId}/cau-hinh-tron', [GiangVienCauHinhTronDeController::class, 'show'])->whereNumber('baiTapId');
    Route::put('/bai-tap/{baiTapId}/cau-hinh-tron', [GiangVienCauHinhTronDeController::class, 'update'])->whereNumber('baiTapId');
    Route::get('/bao-cao-ky-thi', [\App\Http\Controllers\GiangVienExamReportController::class, 'preview']);
    Route::post('/bao-cao-ky-thi/xuat-excel', [\App\Http\Controllers\GiangVienExamReportController::class, 'export']);
    $controller = GiangVienApiController::class;

    // DASHBOARD
    Route::get(
        '/dashboard/stats',
        [$controller, 'dashboard']
    );

    // LỚP HỌC
    Route::get(
        '/lop-hoc',
        [$controller, 'lopHoc']
    );

    // THÀNH VIÊN LỚP
    Route::get(
        '/lop-hoc/{lopId}/thanh-vien',
        [$controller, 'thanhVien']
    );

    // BÀI TẬP
    Route::get(
        '/bai-tap',
        [$controller, 'baiTap']
    );

    Route::post(
        '/bai-tap',
        [$controller, 'createBaiTap']
    );

    // BÀI NỘP
    Route::get(
        '/bai-tap/{baiTapId}/bai-nop',
        [$controller, 'baiNop']
    );

    // ĐIỂM SỐ
    Route::get(
        '/diem',
        [$controller, 'diem']
    );

    // DANH SÁCH PHIÊN GIÁM SÁT
    Route::get(
        '/phien-giam-sat',
        [$controller, 'phienGiamSat']
    );

    // TẠO PHIÊN GIÁM SÁT
    Route::post(
        '/phien-giam-sat',
        [$controller, 'createPhien']
    );
    Route::get(
    '/bai-tap/{baiTapId}/lich-kiem-tra',
    [GiangVienLichKiemTraController::class, 'show']
);

Route::put(
    '/bai-tap/{baiTapId}/lich-kiem-tra',
    [GiangVienLichKiemTraController::class, 'update']
);
    
Route::post(
    '/bai-tap/{baiTapId}/cau-hoi/import-preview',
    [GiangVienNhapCauHoiController::class, 'preview']
);

Route::post(
    '/bai-tap/{baiTapId}/cau-hoi/import-save',
    [GiangVienNhapCauHoiController::class, 'save']
);
Route::get(
    '/bai-nop/{baiNopId}/chi-tiet',
    [
        GiangVienApiController::class,
        'chiTietBaiNop'
    ]
);
Route::put(
    '/bai-nop/{baiNopId}/cham-diem',
    [GiangVienChamDiemController::class, 'update']
);


Route::post(
    '/phien-giam-sat/{id}/bat-dau',
    [GiangVienApiController::class, 'batDauPhien']
);

Route::get(
    '/phien-giam-sat/{id}/chi-tiet',
    [GiangVienApiController::class, 'chiTietPhien']
)->whereNumber('id');

Route::get(
    '/canh-bao-camera',
    [GiangVienCanhBaoCameraController::class, 'index']
);

    
    // ==========================================
    // GIẢNG VIÊN - CÂU HỎI KIỂM TRA
    // ==========================================

    // Danh sách câu hỏi
    Route::get(
        '/bai-tap/{baiTapId}/cau-hoi',
        [GiangVienCauHoiController::class, 'index']
    );

    // Thêm câu hỏi
    Route::post(
        '/bai-tap/{baiTapId}/cau-hoi',
        [GiangVienCauHoiController::class, 'store']
    );

    // Chỉnh sửa câu hỏi
    Route::put(
        '/bai-tap/{baiTapId}/cau-hoi/{cauHoiId}',
        [GiangVienCauHoiController::class, 'update']
    );

    // Xóa câu hỏi
    Route::delete(
        '/bai-tap/{baiTapId}/cau-hoi/{cauHoiId}',
        [GiangVienCauHoiController::class, 'destroy']
    );

    // ==========================================
    // GIẢNG VIÊN - VI PHẠM KIỂM TRA
    // ==========================================

    Route::get(
        '/vi-pham-kiem-tra',
        [GiangVienViPhamKiemTraController::class, 'index']
    );


    /*
    |--------------------------------------------------------------------------
    | API MỚI: KẾT THÚC PHIÊN GIÁM SÁT
    |--------------------------------------------------------------------------
    */

    Route::put(
        '/phien-giam-sat/{id}/ket-thuc',
        [$controller, 'ketThucPhien']
    )->whereNumber('id');

    // NHẬT KÝ HOẠT ĐỘNG
    Route::get(
        '/hoat-dong',
        [$controller, 'hoatDong']
    );

    // ẢNH MÀN HÌNH
    Route::get(
        '/anh-man-hinh',
        [$controller, 'anhManHinh']
    );
    Route::get(
        '/anh-man-hinh/{imageId}/file',
        [ExamEvidenceFileController::class, 'show']
    )->whereNumber('imageId');

    // CẢNH BÁO HỆ THỐNG
    Route::get(
        '/canh-bao',
        [$controller, 'canhBao']
    );

    Route::put(
        '/su-kien-bao-ve/{eventId}/da-xem',
        [$controller, 'markExamProtectionEventViewed']
    )->whereUuid('eventId');

    // XỬ LÝ CẢNH BÁO
    Route::put(
        '/canh-bao/{id}/xu-ly',
        [$controller, 'xuLyCanhBao']
    );
});
