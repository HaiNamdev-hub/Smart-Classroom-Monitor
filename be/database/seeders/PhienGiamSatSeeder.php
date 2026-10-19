<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use App\Models\PhienGiamSat;
use Illuminate\Database\Seeder;

class PhienGiamSatSeeder extends Seeder
{
    public function run(): void
    {
        $class = LopHoc::where(
            'ma_lop',
            'SE01'
        )->firstOrFail();

        $assignment = BaiTap::where(
            'tieu_de',
            'Bai tap Laravel co ban'
        )->firstOrFail();

        $teacher = NguoiDung::where(
            'email',
            'giangvien@example.com'
        )->firstOrFail();

        PhienGiamSat::updateOrCreate(
            [
                'ten_phien' =>
                    'Phien giam sat bai tap Laravel',
            ],
            [
                'lop_hoc_id' =>
                    $class->id,

                'bai_tap_id' =>
                    $assignment->id,

                'nguoi_tao_id' =>
                    $teacher->id,

                'thoi_gian_bat_dau' =>
                    now()->subMinutes(30),

                'thoi_gian_ket_thuc' =>
                    null,

                'trang_thai' =>
                    'dang_dien_ra',
            ]
        );

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
            for ($number = 0; $number < 3; $number++) {
                $assignment = BaiTap::where(
                    'lop_hoc_id',
                    $class->id
                )
                    ->where(
                        'tieu_de',
                        'Bài thực hành ' .
                        ($number + 1) .
                        ' — ' .
                        $class->ten_lop
                    )
                    ->firstOrFail();

                $state = [
                    'da_ket_thuc',
                    'dang_dien_ra',
                    'chua_bat_dau',
                ][$number];

                PhienGiamSat::updateOrCreate(
                    [
                        'lop_hoc_id' =>
                            $class->id,

                        'ten_phien' =>
                            'Buổi thực hành ' .
                            ($number + 1) .
                            ' — ' .
                            $class->ten_lop,
                    ],
                    [
                        'bai_tap_id' =>
                            $assignment->id,

                        'nguoi_tao_id' =>
                            $class->giang_vien_id,

                        'thoi_gian_bat_dau' =>
                            $assignment
                                ->thoi_gian_bat_dau,

                        'thoi_gian_ket_thuc' =>
                            $state === 'da_ket_thuc'
                                ? $assignment
                                    ->thoi_gian_ket_thuc
                                : null,

                        'trang_thai' =>
                            $state,
                    ]
                );
            }
        }
    }
}