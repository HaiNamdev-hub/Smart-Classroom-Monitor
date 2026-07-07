<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SinhVienSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lopIds = DB::table('lop')->pluck('id', 'ma_lop');

        $vaiTroSinhVienId = DB::table('vai_tro')
            ->where('ten_vai_tro', 'sinh_vien')
            ->value('id');

        $userIds = DB::table('nguoi_dung')
            ->where('vai_tro_id', $vaiTroSinhVienId)
            ->pluck('id')
            ->values();

        foreach ($userIds as $index => $userId) {
            DB::table('sinh_vien')->updateOrInsert(
                [
                    'ma_sinh_vien' => sprintf('SV62%03d', $index + 1),
                ],
                [
                    'nam_trung_tuyen' => 2022,
                    'user_id' => $userId,
                    'lop_id' => $index < 5
                        ? $lopIds['CNTT62']
                        : ($index < 8 ? $lopIds['KT62'] : $lopIds['CK62']),
                ]
            );
        }
    }
}
