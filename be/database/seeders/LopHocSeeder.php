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

        foreach (['Lập trình Web', 'Cơ sở dữ liệu', 'Lập trình hướng đối tượng', 'Mạng máy tính', 'Công nghệ phần mềm'] as $index => $name) {
            $teacher = NguoiDung::query()->where('email', 'gv' . ($index % 3 + 1) . '@example.com')->firstOrFail();
            LopHoc::query()->firstOrCreate(
                ['ma_lop' => sprintf('DEMO%02d', $index + 1)],
                ['ten_lop' => $name, 'giang_vien_id' => $teacher->id, 'mo_ta' => 'Lớp học minh họa học kỳ I, năm học 2026–2027.'],
            );
        }
    }
}
