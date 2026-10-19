<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\CanhBao;
use App\Models\LopHoc;
use App\Models\PhienGiamSat;
use App\Models\SinhVien;
use App\Models\ThanhVienLop;
use Illuminate\Database\Seeder;

class CanhBaoSeeder extends Seeder
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

        CanhBao::updateOrCreate(
            [
                'phien_id' => $session->id,
                'sinh_vien_id' => $student->id,
                'loai_canh_bao' =>
                    'website_khong_cho_phep',
            ],
            [
                'noi_dung' =>
                    'Phát hiện truy cập website không được cho phép.',

                'thoi_gian' =>
                    now()->subMinutes(5),

                'da_xu_ly' =>
                    false,
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
            for ($number = 0; $number < 2; $number++) {
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
                    $members->take(3)
                    as $index => $studentId
                ) {
                    $types = [
                        'website_khong_cho_phep',
                        'copy_paste',
                        'roi_phien',
                    ];

                    $type =
                        $types[$index % count($types)];

                    CanhBao::updateOrCreate(
                        [
                            'phien_id' =>
                                $session->id,

                            'sinh_vien_id' =>
                                $studentId,

                            'loai_canh_bao' =>
                                $type,
                        ],
                        [
                            'noi_dung' =>
                                match ($type) {
                                    'website_khong_cho_phep' =>
                                        'Sinh viên truy cập website không được cho phép.',

                                    'copy_paste' =>
                                        'Phát hiện thao tác sao chép/dán.',

                                    'roi_phien' =>
                                        'Sinh viên rời khỏi phiên giám sát.',

                                    default =>
                                        'Phát hiện hoạt động bất thường.',
                                },

                            'thoi_gian' =>
                                $assignment
                                    ->thoi_gian_bat_dau
                                    ->copy()
                                    ->addMinutes(
                                        15 + $index
                                    ),

                            'da_xu_ly' =>
                                false,
                        ]
                    );
                }
            }
        }
    }
}