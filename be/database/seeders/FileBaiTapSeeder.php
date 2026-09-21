<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\FileBaiTap;
use Illuminate\Database\Seeder;

class FileBaiTapSeeder extends Seeder
{
    public function run(): void
    {
        FileBaiTap::query()->updateOrCreate(['duong_dan' => 'bai-tap/de-bai-laravel.pdf'], ['bai_tap_id' => BaiTap::query()->where('tieu_de', 'Bai tap Laravel co ban')->value('id'), 'ten_file' => 'de-bai-laravel.pdf', 'loai_file' => 'application/pdf', 'kich_thuoc' => 102400]);
    }
}
