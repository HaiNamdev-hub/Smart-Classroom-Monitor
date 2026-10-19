<?php

namespace Database\Seeders;

use App\Models\LopHoc;
use App\Models\SinhVien;
use App\Models\ThanhVienLop;
use Illuminate\Database\Seeder;

class ThanhVienLopSeeder extends Seeder
{
    public function run(): void
    {
        $lopSE01 = LopHoc::where(
            'ma_lop',
            'SE01'
        )->firstOrFail();

        $sinhVienMau = SinhVien::whereHas(
            'nguoiDung',
            function ($query) {
                $query->whereIn(
                    'email',
                    [
                        'sv.an@example.com',
                        'sv.binh@example.com',
                    ]
                );
            }
        )->get();

        foreach ($sinhVienMau as $student) {
            ThanhVienLop::updateOrCreate(
                [
                    'lop_hoc_id' => $lopSE01->id,
                    'sinh_vien_id' => $student->id,
                ],
                [
                    'trang_thai' => 'dang_hoc',
                ]
            );
        }

        $students = [];

        for ($i = 1; $i <= 20; $i++) {
            $email = sprintf(
                'sv%02d@example.com',
                $i
            );

            $students[] = SinhVien::whereHas(
                'nguoiDung',
                fn ($query) =>
                    $query->where('email', $email)
            )->firstOrFail();
        }

        $classes = LopHoc::whereIn(
            'ma_lop',
            [
                'DEMO01',
                'DEMO02',
                'DEMO03',
                'DEMO04',
                'DEMO05',
            ]
        )
            ->orderBy('ma_lop')
            ->get();

        foreach ($classes as $index => $class) {
            // Chỉ 18 sinh viên đang hoạt động tham gia lớp.
            for ($i = 0; $i < 12; $i++) {
                $student =
                    $students[
                        ($index * 3 + $i) % 18
                    ];

                ThanhVienLop::updateOrCreate(
                    [
                        'lop_hoc_id' => $class->id,
                        'sinh_vien_id' => $student->id,
                    ],
                    [
                        'trang_thai' => 'dang_hoc',
                    ]
                );
            }

            // Sinh viên thứ 20 dùng để minh họa đã rời lớp.
            ThanhVienLop::updateOrCreate(
                [
                    'lop_hoc_id' => $class->id,
                    'sinh_vien_id' => $students[19]->id,
                ],
                [
                    'trang_thai' => 'da_roi_lop',
                ]
            );
        }
    }
}