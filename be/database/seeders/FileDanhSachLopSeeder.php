<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FileDanhSachLop;

class FileDanhSachLopSeeder extends Seeder
{
    public function run(): void
    {
        FileDanhSachLop::updateOrCreate(
            ['lop_id' => 1, 'ten_file' => 'danh_sach_lop_D23CQCN01.pdf'],
            [
                'nguoi_tai_id' => 1,
                'duong_dan' => '/uploads/danh_sach_lop/D23CQCN01.pdf',
                'trang_thai' => 'da_xu_ly',
            ]
        );
    }
}
