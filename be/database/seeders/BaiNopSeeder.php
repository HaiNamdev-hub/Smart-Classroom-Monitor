<?php

namespace Database\Seeders;

use App\Models\BaiNop;
use App\Models\BaiTap;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;

class BaiNopSeeder extends Seeder
{
    public function run(): void
    {
        BaiNop::query()->updateOrCreate(['bai_tap_id' => BaiTap::query()->where('tieu_de', 'Bai tap Laravel co ban')->value('id'), 'sinh_vien_id' => NguoiDung::query()->where('email', 'sv.an@example.com')->value('id')], ['ten_file' => 'bai-lam-tran-minh-an.zip', 'duong_dan' => 'bai-nop/bai-lam-tran-minh-an.zip', 'loai_file' => 'application/zip', 'thoi_gian_nop' => now(), 'trang_thai' => 'dung_han']);
    }
}
