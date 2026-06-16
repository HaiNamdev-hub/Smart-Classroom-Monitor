<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KhoaSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $khoaData = [
            ['ma_khoa' => 'CNTT', 'ten_khoa' => 'Công nghệ thông tin'],
            ['ma_khoa' => 'KinhTe', 'ten_khoa' => 'Kinh tế'],
            ['ma_khoa' => 'CoKhi', 'ten_khoa' => 'Cơ khí'],
        ];

        foreach ($khoaData as $khoa) {
            DB::table('khoa')->updateOrInsert(
                ['ma_khoa' => $khoa['ma_khoa']],
                $khoa
            );
        }
    }
}
