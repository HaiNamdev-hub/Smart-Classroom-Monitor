<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DotDanhGiaSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periods = [
            ['nam_hoc' => '2025-2026', 'hoc_ky' => 1, 'ngay_bat_dau' => '2026-05-01 08:00:00', 'ngay_ket_thuc' => '2026-06-15 18:00:00', 'trang_thai' => 'dang_mo'],
            ['nam_hoc' => '2025-2026', 'hoc_ky' => 2, 'ngay_bat_dau' => '2026-11-01 08:00:00', 'ngay_ket_thuc' => '2026-12-15 18:00:00', 'trang_thai' => 'da_dong'],
        ];

        foreach ($periods as $period) {
            DB::table('dot_danh_gia')->updateOrInsert(
                ['nam_hoc' => $period['nam_hoc'], 'hoc_ky' => $period['hoc_ky']],
                $period
            );
        }
    }
}
