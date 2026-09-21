<?php

namespace Database\Seeders;

use App\Models\HoatDong;
use App\Models\NguoiDung;
use App\Models\PhienGiamSat;
use Illuminate\Database\Seeder;

class HoatDongSeeder extends Seeder
{
    public function run(): void
    {
        HoatDong::query()->updateOrCreate(['phien_id' => PhienGiamSat::query()->where('ten_phien', 'Phien giam sat bai tap Laravel')->value('id'), 'sinh_vien_id' => NguoiDung::query()->where('email', 'sv.an@example.com')->value('id'), 'thoi_gian' => now()->subMinutes(5)], ['loai_hoat_dong' => 'website', 'website_url' => 'https://laravel.com/docs', 'ten_ung_dung' => null]);
    }
}
