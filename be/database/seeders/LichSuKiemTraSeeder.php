<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LichSuKiemTra;

class LichSuKiemTraSeeder extends Seeder
{
    public function run(): void
    {
        LichSuKiemTra::updateOrCreate(
            ['phieu_id' => 1, 'nguoi_kiem_tra_id' => 2, 'hanh_dong' => 'danh_gia'],
            [
                'vai_tro_kiem_tra' => 'lop_truong',
                'ghi_chu' => 'Phiếu hợp lệ và điểm ổn định.',
            ]
        );
    }
}
