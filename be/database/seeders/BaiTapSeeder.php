<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;

class BaiTapSeeder extends Seeder
{
    public function run(): void
    {
        $class = LopHoc::where(
            'ma_lop',
            'SE01'
        )->firstOrFail();

        $teacher = NguoiDung::where(
            'email',
            'giangvien@example.com'
        )->firstOrFail();

        BaiTap::updateOrCreate(
            [
                'lop_hoc_id' => $class->id,
                'tieu_de' => 'Bai tap Laravel co ban',
            ],
            [
                'nguoi_tao_id' => $teacher->id,
                'mo_ta' => 'Tao API CRUD bang Laravel.',
                'thoi_gian_bat_dau' => now()->subDay(),
                'thoi_gian_ket_thuc' => now()->addDays(6),
                'han_nop' => now()->addDays(5),
                'cho_phep_nop_tre' => false,
            ]
        );

        $now = now()->startOfMinute();

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

        foreach ($classes as $class) {
            $states = [
                'da_ket_thuc',
                'dang_dien_ra',
                'chua_bat_dau',
            ];

            foreach ($states as $number => $state) {
                $start = match ($state) {
                    'da_ket_thuc' =>
                        $now->copy()->subDays(3),

                    'dang_dien_ra' =>
                        $now->copy()->subHour(),

                    default =>
                        $now->copy()->addDay(),
                };

                $deadline =
                    $start->copy()->addHours(2);

                BaiTap::updateOrCreate(
                    [
                        'lop_hoc_id' => $class->id,

                        'tieu_de' =>
                            'Bài thực hành ' .
                            ($number + 1) .
                            ' — ' .
                            $class->ten_lop,
                    ],
                    [
                        'nguoi_tao_id' =>
                            $class->giang_vien_id,

                        'mo_ta' =>
                            'Hoàn thành các yêu cầu thực hành và nộp mã nguồn kèm báo cáo.',

                        'thoi_gian_bat_dau' =>
                            $start,

                        'han_nop' =>
                            $deadline,

                        'thoi_gian_ket_thuc' =>
                            $deadline
                                ->copy()
                                ->addHour(),

                        'cho_phep_nop_tre' =>
                            true,
                    ]
                );
            }
        }
    }
}