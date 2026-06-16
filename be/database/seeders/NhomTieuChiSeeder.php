<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NhomTieuChiSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['ma_nhom' => 'P1', 'ten_nhom' => 'Ý thức học tập', 'diem_toi_da' => 40],
            ['ma_nhom' => 'P2', 'ten_nhom' => 'Thái độ và kỷ luật', 'diem_toi_da' => 30],
            ['ma_nhom' => 'P3', 'ten_nhom' => 'Hoạt động ngoại khóa', 'diem_toi_da' => 30],
        ];

        foreach ($groups as $group) {
            DB::table('nhom_tieu_chi')->updateOrInsert(
                ['ma_nhom' => $group['ma_nhom']],
                $group
            );
        }
    }
}
