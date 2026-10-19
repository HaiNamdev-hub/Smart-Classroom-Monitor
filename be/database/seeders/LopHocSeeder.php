<?php

namespace Database\Seeders;

use App\Models\LopHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;

class LopHocSeeder extends Seeder
{
    public function run(): void
    {
        $giangVien = NguoiDung::where(
            'email',
            'giangvien@example.com'
        )->firstOrFail();

        LopHoc::updateOrCreate(
            [
                'ma_lop' => 'SE01',
            ],
            [
                'ten_lop' => 'Lập trình Web',
                'giang_vien_id' => $giangVien->id,
                'mo_ta' => 'Lớp học mẫu Smart Classroom',
            ]
        );

        $classes = [
            'Lập trình Web',
            'Cơ sở dữ liệu',
            'Lập trình hướng đối tượng',
            'Mạng máy tính',
            'Công nghệ phần mềm',
        ];

        foreach ($classes as $index => $name) {
            $teacher = NguoiDung::where(
                'email',
                'gv' . ($index % 3 + 1) . '@example.com'
            )->firstOrFail();

            LopHoc::updateOrCreate(
                [
                    'ma_lop' => sprintf(
                        'DEMO%02d',
                        $index + 1
                    ),
                ],
                [
                    'ten_lop' => $name,
                    'giang_vien_id' => $teacher->id,
                    'mo_ta' =>
                        'Lớp học minh họa học kỳ I, năm học 2026–2027.',
                ]
            );
        }
    }
}