<?php

namespace Database\Seeders;

use App\Models\Khoa;
use App\Models\Nganh;
use Illuminate\Database\Seeder;

class NganhSeeder extends Seeder
{
    public function run(): void
    {
        $khoa = Khoa::where('ma_khoa', 'CNTT')
            ->firstOrFail();

        $data = [
            [
                'ma_nganh' => 'SE',
                'ten_nganh' => 'Kỹ thuật Phần mềm',
                'mo_ta' => 'Ngành Kỹ thuật Phần mềm',
            ],
            [
                'ma_nganh' => 'CS',
                'ten_nganh' => 'Khoa học Máy tính',
                'mo_ta' => 'Ngành Khoa học Máy tính',
            ],
            [
                'ma_nganh' => 'IS',
                'ten_nganh' => 'Hệ thống Thông tin',
                'mo_ta' => 'Ngành Hệ thống Thông tin',
            ],
        ];

        foreach ($data as $item) {
            Nganh::updateOrCreate(
                [
                    'ma_nganh' => $item['ma_nganh'],
                ],
                [
                    'khoa_id' => $khoa->id,
                    'ten_nganh' => $item['ten_nganh'],
                    'mo_ta' => $item['mo_ta'],
                ]
            );
        }
    }
}