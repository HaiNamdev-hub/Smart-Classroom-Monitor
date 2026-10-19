<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhanQuyenSeeder extends Seeder
{
    public function run(): void
    {
        $giangVien = DB::table('vai_tro')
            ->where(
                'ten_vai_tro',
                'giang_vien'
            )
            ->first();

        $sinhVien = DB::table('vai_tro')
            ->where(
                'ten_vai_tro',
                'sinh_vien'
            )
            ->first();

        if (!$giangVien || !$sinhVien) {
            return;
        }

        $quyenGiangVien = [
            'quan_ly_lop_hoc',
            'quan_ly_thanh_vien_lop',
            'quan_ly_bai_tap',
            'quan_ly_bai_nop',
            'quan_ly_diem',
            'quan_ly_phien_giam_sat',
            'quan_ly_hoat_dong',
            'quan_ly_anh_man_hinh',
            'quan_ly_canh_bao',
        ];

        $quyenSinhVien = [
            'quan_ly_lop_hoc',
            'quan_ly_bai_tap',
            'quan_ly_bai_nop',
            'quan_ly_diem',
        ];

        $this->ganQuyen(
            $giangVien->id,
            $quyenGiangVien
        );

        $this->ganQuyen(
            $sinhVien->id,
            $quyenSinhVien
        );
    }

    private function ganQuyen(
        int $vaiTroId,
        array $maChucNangs
    ): void {
        $chucNangs = DB::table('chuc_nang')
            ->whereIn(
                'ma_chuc_nang',
                $maChucNangs
            )
            ->get();

        foreach ($chucNangs as $chucNang) {
            DB::table('phan_quyen')
                ->updateOrInsert(
                    [
                        'vai_tro_id' =>
                            $vaiTroId,

                        'chuc_nang_id' =>
                            $chucNang->id,
                    ],
                    [
                        'updated_at' =>
                            now(),
                    ]
                );
        }
    }
}