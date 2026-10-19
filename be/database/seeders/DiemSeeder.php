<?php

namespace Database\Seeders;

use App\Models\BaiNop;
use App\Models\BaiTap;
use App\Models\Diem;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Seeder;

class DiemSeeder extends Seeder
{
    public function run(): void
    {
        $submission = BaiNop::where(
            'duong_dan',
            'bai-nop/bai-lam-tran-minh-an.zip'
        )->firstOrFail();

        $teacher = NguoiDung::where(
            'email',
            'giangvien@example.com'
        )->firstOrFail();

        Diem::updateOrCreate(
            [
                'bai_nop_id' =>
                    $submission->id,
            ],
            [
                'nguoi_cham_id' =>
                    $teacher->id,

                'diem' => 9.00,

                'nhan_xet' =>
                    'Bài làm đạt yêu cầu.',

                'ket_qua' =>
                    'dat',

                'thoi_gian_cham' =>
                    now(),
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
            $assignment = BaiTap::where(
                'lop_hoc_id',
                $class->id
            )
                ->where(
                    'tieu_de',
                    'Bài thực hành 1 — ' .
                    $class->ten_lop
                )
                ->firstOrFail();

            $submissions = BaiNop::where(
                'bai_tap_id',
                $assignment->id
            )
                ->orderBy('id')
                ->limit(8)
                ->get();

            $scores = [
                9,
                8.5,
                7,
                4,
                6.5,
                10,
                3.5,
                8,
            ];

            foreach (
                $submissions
                as $index => $submission
            ) {
                $score = $scores[$index];

                Diem::updateOrCreate(
                    [
                        'bai_nop_id' =>
                            $submission->id,
                    ],
                    [
                        'nguoi_cham_id' =>
                            $class->giang_vien_id,

                        'diem' =>
                            $score,

                        'nhan_xet' =>
                            $score >= 5
                                ? 'Hoàn thành yêu cầu bài thực hành.'
                                : 'Cần bổ sung nội dung và kiểm tra lại bài làm.',

                        'ket_qua' =>
                            $score >= 5
                                ? 'dat'
                                : 'khong_dat',

                        'thoi_gian_cham' =>
                            $assignment
                                ->thoi_gian_ket_thuc
                                ->copy()
                                ->addHour(),
                    ]
                );
            }
        }
    }
}