<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\FileBaiTap;
use App\Models\LopHoc;
use Illuminate\Database\Seeder;

class FileBaiTapSeeder extends Seeder
{
    public function run(): void
    {
        $assignment = BaiTap::where(
            'tieu_de',
            'Bai tap Laravel co ban'
        )->firstOrFail();

        FileBaiTap::updateOrCreate(
            [
                'duong_dan' =>
                    'bai-tap/de-bai-laravel.pdf',
            ],
            [
                'bai_tap_id' => $assignment->id,
                'ten_file' => 'de-bai-laravel.pdf',
                'loai_file' => 'application/pdf',
                'kich_thuoc' => 102400,
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

                $prefix =
                    'demo/' .
                    $class->ma_lop .
                    '/bai-' .
                    ($number + 1);

                FileBaiTap::updateOrCreate(
                    [
                        'duong_dan' =>
                            $prefix . '/de-bai.pdf',
                    ],
                    [
                        'bai_tap_id' =>
                            $assignment->id,

                        'ten_file' =>
                            'de-bai.pdf',

                        'loai_file' =>
                            'application/pdf',

                        'kich_thuoc' =>
                            102400,
                    ]
                );
            }
        }
    }
}