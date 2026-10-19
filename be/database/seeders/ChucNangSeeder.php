<?php

namespace Database\Seeders;

use App\Models\ChucNang;
use Illuminate\Database\Seeder;

class ChucNangSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'ma_chuc_nang' => 'phan_quyen',
                'ten_chuc_nang' => 'Phân quyền chức năng',
                'mo_ta' =>
                    'Quản lý và phân quyền chức năng cho các vai trò.',
            ],
            [
                'ma_chuc_nang' => 'quan_ly_nguoi_dung',
                'ten_chuc_nang' => 'Quản lý người dùng',
                'mo_ta' =>
                    'Xem và quản lý thông tin người dùng trong hệ thống.',
            ],
            [
                'ma_chuc_nang' => 'quan_ly_lop_hoc',
                'ten_chuc_nang' => 'Quản lý lớp học',
                'mo_ta' =>
                    'Xem và quản lý thông tin lớp học.',
            ],
            [
                'ma_chuc_nang' =>
                    'quan_ly_thanh_vien_lop',

                'ten_chuc_nang' =>
                    'Quản lý thành viên lớp',

                'mo_ta' =>
                    'Quản lý sinh viên và thành viên trong lớp học.',
            ],
            [
                'ma_chuc_nang' =>
                    'quan_ly_bai_tap',

                'ten_chuc_nang' =>
                    'Quản lý bài tập',

                'mo_ta' =>
                    'Tạo, cập nhật và quản lý bài tập.',
            ],
            [
                'ma_chuc_nang' =>
                    'quan_ly_bai_nop',

                'ten_chuc_nang' =>
                    'Quản lý bài nộp',

                'mo_ta' =>
                    'Xem và quản lý bài nộp của sinh viên.',
            ],
            [
                'ma_chuc_nang' =>
                    'quan_ly_diem',

                'ten_chuc_nang' =>
                    'Quản lý điểm',

                'mo_ta' =>
                    'Xem, nhập và cập nhật điểm của sinh viên.',
            ],
            [
                'ma_chuc_nang' =>
                    'quan_ly_phien_giam_sat',

                'ten_chuc_nang' =>
                    'Quản lý phiên giám sát',

                'mo_ta' =>
                    'Tạo và quản lý các phiên giám sát lớp học.',
            ],
            [
                'ma_chuc_nang' =>
                    'quan_ly_hoat_dong',

                'ten_chuc_nang' =>
                    'Quản lý hoạt động',

                'mo_ta' =>
                    'Theo dõi và quản lý hoạt động trong hệ thống.',
            ],
            [
                'ma_chuc_nang' =>
                    'quan_ly_anh_man_hinh',

                'ten_chuc_nang' =>
                    'Quản lý ảnh màn hình',

                'mo_ta' =>
                    'Xem và quản lý ảnh màn hình được ghi nhận.',
            ],
            [
                'ma_chuc_nang' =>
                    'quan_ly_canh_bao',

                'ten_chuc_nang' =>
                    'Quản lý cảnh báo',

                'mo_ta' =>
                    'Xem và xử lý các cảnh báo của hệ thống.',
            ],
        ];

        foreach ($data as $item) {
            ChucNang::updateOrCreate(
                [
                    'ma_chuc_nang' =>
                        $item['ma_chuc_nang'],
                ],
                [
                    'ten_chuc_nang' =>
                        $item['ten_chuc_nang'],

                    'mo_ta' =>
                        $item['mo_ta'],

                    'trang_thai' =>
                        'hoat_dong',
                ]
            );
        }
    }
}