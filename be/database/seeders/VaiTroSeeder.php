<?php

namespace Database\Seeders;

use App\Models\VaiTro;
use Illuminate\Database\Seeder;

class VaiTroSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'ten_vai_tro' => 'quan_tri_vien',
                'mo_ta' => 'Quản trị hệ thống',
            ],
            [
                'ten_vai_tro' => 'giang_vien',
                'mo_ta' => 'Giảng viên',
            ],
            [
                'ten_vai_tro' => 'sinh_vien',
                'mo_ta' => 'Sinh viên',
            ],
        ];

        foreach ($data as $item) {
            VaiTro::updateOrCreate(
                ['ten_vai_tro' => $item['ten_vai_tro']],
                ['mo_ta' => $item['mo_ta']]
            );
        }
    }
}