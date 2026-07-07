<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChucNang;

class ChucNangSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['ma_chuc_nang' => 'quan_ly_nguoi_dung', 'ten_chuc_nang' => 'Quản lý người dùng'],
            ['ma_chuc_nang' => 'quan_ly_khoa', 'ten_chuc_nang' => 'Quản lý khoa'],
            ['ma_chuc_nang' => 'quan_ly_nganh', 'ten_chuc_nang' => 'Quản lý ngành'],
            ['ma_chuc_nang' => 'quan_ly_lop', 'ten_chuc_nang' => 'Quản lý lớp'],
            ['ma_chuc_nang' => 'quan_ly_danh_sach_lop', 'ten_chuc_nang' => 'Quản lý danh sách lớp'],
            ['ma_chuc_nang' => 'xem_diem_lop', 'ten_chuc_nang' => 'Xem điểm lớp'],
            ['ma_chuc_nang' => 'duyet_phieu_ren_luyen', 'ten_chuc_nang' => 'Duyệt phiếu rèn luyện'],
            ['ma_chuc_nang' => 'xuat_bao_cao', 'ten_chuc_nang' => 'Xuất báo cáo'],
        ];

        foreach ($data as $item) {
            ChucNang::updateOrCreate(
                ['ma_chuc_nang' => $item['ma_chuc_nang']],
                [
                    'ten_chuc_nang' => $item['ten_chuc_nang'],
                    'trang_thai' => 'hoat_dong',
                ]
            );
        }
    }
}
