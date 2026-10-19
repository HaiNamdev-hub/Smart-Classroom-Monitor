<?php

namespace Database\Seeders;

use App\Models\Nganh;
use App\Models\NguoiDung;
use App\Models\SinhVien;
use App\Models\VaiTro;
use Illuminate\Database\Seeder;

class SinhVienSeeder extends Seeder
{
    public function run(): void
    {
        $nganhSE = Nganh::where('ma_nganh', 'SE')
            ->firstOrFail();

        $vaiTroSinhVien = VaiTro::where(
            'ten_vai_tro',
            'sinh_vien'
        )->firstOrFail();

        $users = NguoiDung::where(
            'vai_tro_id',
            $vaiTroSinhVien->id
        )
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            SinhVien::updateOrCreate(
                [
                    'nguoi_dung_id' => $user->id,
                ],
                [
                    'nganh_id' => $nganhSE->id,

                    'ma_sinh_vien' =>
                        'SV' .
                        str_pad(
                            $user->id,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'nam_trung_tuyen' => 2023,
                ]
            );
        }
    }
}