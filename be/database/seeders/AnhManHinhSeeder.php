<?php

namespace Database\Seeders;

use App\Models\AnhManHinh;
use App\Models\BaiTap;
use App\Models\LopHoc;
use App\Models\PhienGiamSat;
use App\Models\SinhVien;
use App\Models\ThanhVienLop;
use Illuminate\Database\Seeder;

class AnhManHinhSeeder extends Seeder
{
    public function run(): void
    {
        $session = PhienGiamSat::where(
            'ten_phien',
            'Phien giam sat bai tap Laravel'
        )->firstOrFail();

        $student = SinhVien::whereHas(
            'nguoiDung',
            fn ($query) =>
                $query->where(
                    'email',
                    'sv.an@example.com'
                )
        )->firstOrFail();

        AnhManHinh::updateOrCreate(
            [
                'duong_dan' =>
                    'anh-man-hinh/sv-an-001.png',
            ],
            [
                'phien_id' =>
                    $session->id,

                'sinh_vien_id' =>
                    $student->id,

                'thoi_gian_chup' =>
                    now()->subMinutes(3),
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

                $session = PhienGiamSat::where(
                    'lop_hoc_id',
                    $class->id
                )
                    ->where(
                        'bai_tap_id',
                        $assignment->id
                    )
                    ->firstOrFail();

                if (
                    $session->trang_thai ===
                    'chua_bat_dau'
                ) {
                    continue;
                }

                $prefix =
                    'demo/' .
                    $class->ma_lop .
                    '/bai-' .
                    ($number + 1);

                $members = ThanhVienLop::where(
                    'lop_hoc_id',
                    $class->id
                )
                    ->where(
                        'trang_thai',
                        'dang_hoc'
                    )
                    ->orderBy('id')
                    ->pluck('sinh_vien_id');

                foreach (
                    $members
                    as $memberIndex => $studentId
                ) {
                    $eventTime =
                        $assignment
                            ->thoi_gian_bat_dau
                            ->copy()
                            ->addMinutes(
                                10 + $memberIndex
                            );

                    AnhManHinh::updateOrCreate(
                        [
                            'duong_dan' =>
                                $prefix .
                                '/man-hinh-' .
                                $studentId .
                                '.png',
                        ],
                        [
                            'phien_id' =>
                                $session->id,

                            'sinh_vien_id' =>
                                $studentId,

                            'thoi_gian_chup' =>
                                $eventTime
                                    ->copy()
                                    ->addMinutes(3),
                        ]
                    );
                }
            }
        }
    }
}