<?php

namespace Database\Seeders;

use App\Models\CanhBao;
use App\Models\NguoiDung;
use App\Models\PhienGiamSat;
use Illuminate\Database\Seeder;

class CanhBaoSeeder extends Seeder
{
    public function run(): void
    {
        CanhBao::query()->updateOrCreate(['phien_id' => PhienGiamSat::query()->where('ten_phien', 'Phien giam sat bai tap Laravel')->value('id'), 'sinh_vien_id' => NguoiDung::query()->where('email', 'sv.binh@example.com')->value('id'), 'loai_canh_bao' => 'mat_ket_noi'], ['noi_dung' => 'Sinh vien mat ket noi trong phien giam sat.', 'thoi_gian' => now()->subMinutes(2), 'da_xu_ly' => false]);
    }
}
