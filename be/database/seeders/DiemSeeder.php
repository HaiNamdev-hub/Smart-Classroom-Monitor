<?php

namespace Database\Seeders;

use App\Models\BaiNop;
use App\Models\Diem;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;

class DiemSeeder extends Seeder
{
    public function run(): void
    {
        Diem::query()->updateOrCreate(['bai_nop_id' => BaiNop::query()->where('duong_dan', 'bai-nop/bai-lam-tran-minh-an.zip')->value('id')], ['nguoi_cham_id' => NguoiDung::query()->where('email', 'giangvien@example.com')->value('id'), 'diem' => 9.00, 'nhan_xet' => 'Bai lam dat yeu cau.', 'ket_qua' => 'dat', 'thoi_gian_cham' => now()]);
    }
}
