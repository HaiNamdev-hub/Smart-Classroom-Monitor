<?php

namespace Database\Seeders;

use App\Models\AnhManHinh;
use App\Models\NguoiDung;
use App\Models\PhienGiamSat;
use Illuminate\Database\Seeder;

class AnhManHinhSeeder extends Seeder
{
    public function run(): void
    {
        AnhManHinh::query()->updateOrCreate(['duong_dan' => 'anh-man-hinh/sv-an-001.png'], ['phien_id' => PhienGiamSat::query()->where('ten_phien', 'Phien giam sat bai tap Laravel')->value('id'), 'sinh_vien_id' => NguoiDung::query()->where('email', 'sv.an@example.com')->value('id'), 'thoi_gian_chup' => now()->subMinutes(3)]);
    }
}
