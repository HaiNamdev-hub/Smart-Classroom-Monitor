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
        $roles = VaiTro::query()->pluck('id', 'ten_vai_tro');
        foreach ([['ho_ten' => 'Quan tri vien', 'email' => 'admin@example.com', 'vai_tro_id' => $roles['quan_tri_vien']], ['ho_ten' => 'Nguyen Van Giang', 'email' => 'giangvien@example.com', 'vai_tro_id' => $roles['giang_vien']], ['ho_ten' => 'Tran Minh An', 'email' => 'sv.an@example.com', 'vai_tro_id' => $roles['sinh_vien']], ['ho_ten' => 'Le Thu Binh', 'email' => 'sv.binh@example.com', 'vai_tro_id' => $roles['sinh_vien']]] as $nguoiDung) {
            NguoiDung::query()->updateOrCreate(['email' => $nguoiDung['email']], $nguoiDung + ['password' => 'password', 'trang_thai' => 'hoat_dong']);
        }

        $roles = VaiTro::query()->pluck('id', 'ten_vai_tro');
        $password = Hash::make('password');

        foreach (['Nguyễn Minh Đức', 'Trần Thị Mai', 'Lê Quốc Bảo'] as $index => $name) {
            NguoiDung::query()->firstOrCreate(
                ['email' => 'gv'.($index + 1).'@example.com'],
                ['ho_ten' => $name, 'password' => $password, 'vai_tro_id' => $roles['giang_vien'], 'trang_thai' => 'hoat_dong'],
            );
        }

        $names = [
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

        foreach ($names as $index => $name) {
            NguoiDung::query()->firstOrCreate(
                ['email' => sprintf('sv%02d@example.com', $index + 1)],
                [
                    'ho_ten' => $name,
                    'password' => $password,
                    'vai_tro_id' => $roles['sinh_vien'],
                    'trang_thai' => match ($index) {
                        18 => 'khong_hoat_dong',
                        19 => 'bi_khoa',
                        default => 'hoat_dong'
                    },
                ],
            );
        }
    }
}
