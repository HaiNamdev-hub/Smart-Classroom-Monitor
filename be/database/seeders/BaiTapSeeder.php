<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;

class BaiTapSeeder extends Seeder
{
    public function run(): void
    {
        BaiTap::query()->updateOrCreate(['tieu_de' => 'Bai tap Laravel co ban'], ['lop_hoc_id' => LopHoc::query()->where('ma_lop', 'SE01')->value('id'), 'nguoi_tao_id' => NguoiDung::query()->where('email', 'giangvien@example.com')->value('id'), 'mo_ta' => 'Tao API CRUD bang Laravel.', 'thoi_gian_bat_dau' => now()->subDay(), 'thoi_gian_ket_thuc' => now()->addDays(6), 'han_nop' => now()->addDays(5), 'cho_phep_nop_tre' => false]);
    }
}
