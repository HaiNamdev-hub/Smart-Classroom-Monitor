<?php

namespace Database\Seeders;

use App\Models\VaiTro;
use Illuminate\Database\Seeder;

class VaiTroSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['ten_vai_tro' => 'quan_tri_vien', 'mo_ta' => 'Quan tri he thong'], ['ten_vai_tro' => 'giang_vien', 'mo_ta' => 'Giang vien'], ['ten_vai_tro' => 'sinh_vien', 'mo_ta' => 'Sinh vien']] as $vaiTro) {
            VaiTro::query()->updateOrCreate(['ten_vai_tro' => $vaiTro['ten_vai_tro']], $vaiTro);
        }
    }
}
