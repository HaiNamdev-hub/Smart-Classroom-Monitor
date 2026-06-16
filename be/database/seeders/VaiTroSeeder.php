<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VaiTroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['ten_vai_tro' => 'admin', 'mo_ta' => 'Quan tri he thong'],
            ['ten_vai_tro' => 'phong_qldt', 'mo_ta' => 'Phong quan ly dao tao'],
            ['ten_vai_tro' => 'co_van', 'mo_ta' => 'Co van hoc tap'],
            ['ten_vai_tro' => 'lop_truong', 'mo_ta' => 'Lop truong'],
            ['ten_vai_tro' => 'bi_thu', 'mo_ta' => 'Bi thu'],
            ['ten_vai_tro' => 'sinh_vien', 'mo_ta' => 'Sinh vien'],
        ] as $vaiTro) {
            DB::table('vai_tro')->updateOrInsert(
                ['ten_vai_tro' => $vaiTro['ten_vai_tro']],
                ['mo_ta' => $vaiTro['mo_ta']]
            );
        }
    }
}
