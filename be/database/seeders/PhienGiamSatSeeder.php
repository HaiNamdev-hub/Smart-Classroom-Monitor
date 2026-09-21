<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use App\Models\PhienGiamSat;
use Illuminate\Database\Seeder;

class PhienGiamSatSeeder extends Seeder
{
    public function run(): void
    {
        PhienGiamSat::query()->updateOrCreate(['ten_phien' => 'Phien giam sat bai tap Laravel'], ['lop_hoc_id' => LopHoc::query()->where('ma_lop', 'SE01')->value('id'), 'bai_tap_id' => BaiTap::query()->where('tieu_de', 'Bai tap Laravel co ban')->value('id'), 'nguoi_tao_id' => NguoiDung::query()->where('email', 'giangvien@example.com')->value('id'), 'thoi_gian_bat_dau' => now()->subMinutes(30), 'thoi_gian_ket_thuc' => null, 'trang_thai' => 'dang_dien_ra']);
    }
}
