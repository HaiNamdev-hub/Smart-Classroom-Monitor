<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\HoatDong;
use App\Models\LopHoc;
use App\Models\PhienGiamSat;
use App\Models\SinhVien;
use App\Models\ThanhVienLop;
use Illuminate\Database\Seeder;

class HoatDongSeeder extends Seeder
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

        HoatDong::updateOrCreate(
            [
                'phien_id' =>
                    $session->id,

                'sinh_vien_id' =>
                    $student->id,

                'loai_hoat_dong' =>
                    'website',

                'website_url' =>
                    'https://laravel.com/docs',
            ],
            [
                'thoi_gian' =>
                    now()->subMinutes(5),

                'ten_ung_dung' =>
                    null,
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

                    $types = [
                        'tham_gia',
                        'ung_dung',
                        'website',
                    ];

                    foreach (
                        $types
                        as $eventIndex => $type
                    ) {
                        HoatDong::updateOrCreate(
                            [
                                'phien_id' =>
                                    $session->id,

                                'sinh_vien_id' =>
                                    $studentId,

                                'loai_hoat_dong' =>
                                    $type,
                            ],
                            [
                                'ten_ung_dung' =>
                                    $type === 'ung_dung'
                                        ? 'Visual Studio Code'
                                        : null,

                                'website_url' =>
                                    $type === 'website'
                                        ? 'https://example.com/tai-lieu'
                                        : null,

                                'thoi_gian' =>
                                    $eventTime
                                        ->copy()
                                        ->addMinutes(
                                            $eventIndex
                                        ),
                            ]
                        );
                    }
                }
            }
        }
    }
}