<?php

namespace Database\Seeders;

use App\Models\Khoa;
use Illuminate\Database\Seeder;

class KhoaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'ma_khoa' => 'CNTT',
                'ten_khoa' => 'Khoa Công nghệ Thông tin',
                'mo_ta' => 'Khoa Công nghệ Thông tin',
            ],
        ];

        foreach ($data as $item) {
            Khoa::updateOrCreate(
                [
                    'ma_khoa' => $item['ma_khoa'],
                ],
                [
                    'ten_khoa' => $item['ten_khoa'],
                    'mo_ta' => $item['mo_ta'],
                ]
            );
        }
    }
}