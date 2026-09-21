<?php

namespace Database\Seeders;

use App\Models\LopHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;

class LopHocSeeder extends Seeder
{
    public function run(): void
    {
        $giangVienId = NguoiDung::query()->where('email', 'giangvien@example.com')->value('id');
        LopHoc::query()->updateOrCreate(['ma_lop' => 'SE01'], ['ten_lop' => 'Lap trinh Web', 'giang_vien_id' => $giangVienId, 'mo_ta' => 'Lop hoc mau Smart Classroom']);
    }
}
