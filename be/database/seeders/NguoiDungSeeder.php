<?php

namespace Database\Seeders;

use App\Models\NguoiDung;
use App\Models\VaiTro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NguoiDungSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================
        // LẤY ID CÁC VAI TRÒ
        // =====================================================

        $roles = VaiTro::query()
            ->pluck('id', 'ten_vai_tro');

        if (
            !$roles->has('quan_tri_vien') ||
            !$roles->has('giang_vien') ||
            !$roles->has('sinh_vien')
        ) {
            throw new \Exception(
                'Chưa có đầy đủ vai trò. Hãy chạy VaiTroSeeder trước.'
            );
        }


        // =====================================================
        // MẬT KHẨU MẶC ĐỊNH
        // =====================================================

        // Tất cả tài khoản test dùng mật khẩu: 1234567
        // Hash::make() tạo BCrypt hash để Laravel Hash::check() sử dụng được.
        $password = Hash::make('1234567');


        // =====================================================
        // TÀI KHOẢN CƠ BẢN
        // =====================================================

        $users = [
            [
                'ho_ten' => 'Quản trị viên',
                'email' => 'admin@example.com',
                'vai_tro_id' => $roles['quan_tri_vien'],
            ],
            [
                'ho_ten' => 'Nguyễn Văn Giảng',
                'email' => 'giangvien@example.com',
                'vai_tro_id' => $roles['giang_vien'],
            ],
            [
                'ho_ten' => 'Trần Minh An',
                'email' => 'sv.an@example.com',
                'vai_tro_id' => $roles['sinh_vien'],
            ],
            [
                'ho_ten' => 'Lê Thu Bình',
                'email' => 'sv.binh@example.com',
                'vai_tro_id' => $roles['sinh_vien'],
            ],
        ];

        foreach ($users as $user) {
            NguoiDung::query()->updateOrCreate(
                [
                    'email' => $user['email'],
                ],
                [
                    'ho_ten' => $user['ho_ten'],
                    'password' => $password,
                    'vai_tro_id' => $user['vai_tro_id'],
                    'trang_thai' => 'hoat_dong',
                ]
            );
        }


        // =====================================================
        // GIẢNG VIÊN
        // =====================================================

        $teachers = [
            'Nguyễn Minh Đức',
            'Trần Thị Mai',
            'Lê Quốc Bảo',
        ];

        foreach ($teachers as $index => $name) {
            NguoiDung::query()->updateOrCreate(
                [
                    'email' => 'gv' . ($index + 1) . '@example.com',
                ],
                [
                    'ho_ten' => $name,
                    'password' => $password,
                    'vai_tro_id' => $roles['giang_vien'],
                    'trang_thai' => 'hoat_dong',
                ]
            );
        }


        // =====================================================
        // SINH VIÊN
        // =====================================================

        $students = [
            'Nguyễn Hoàng An',
            'Trần Ngọc Anh',
            'Lê Gia Bảo',
            'Phạm Minh Châu',
            'Võ Tiến Dũng',
            'Đặng Thu Hà',
            'Bùi Đức Hải',
            'Đỗ Quang Huy',
            'Hồ Bảo Khang',
            'Ngô Khánh Linh',
            'Dương Nhật Minh',
            'Lý Hoài Nam',
            'Mai Hồng Ngọc',
            'Đinh Hải Nhi',
            'Vũ Tuấn Phong',
            'Phan Minh Quân',
            'Trương Bảo Trâm',
            'Cao Anh Tú',
            'Lâm Thanh Vy',
            'Nguyễn Hải Yến',
        ];

        foreach ($students as $index => $name) {

            $email = sprintf(
                'sv%02d@example.com',
                $index + 1
            );

            $trangThai = match ($index) {
                // sv19@example.com
                18 => 'khong_hoat_dong',

                // sv20@example.com
                19 => 'bi_khoa',

                default => 'hoat_dong',
            };

            NguoiDung::query()->updateOrCreate(
                [
                    'email' => $email,
                ],
                [
                    'ho_ten' => $name,
                    'password' => $password,
                    'vai_tro_id' => $roles['sinh_vien'],
                    'trang_thai' => $trangThai,
                ]
            );
        }


        // =====================================================
        // THÔNG BÁO
        // =====================================================

      
    }
}