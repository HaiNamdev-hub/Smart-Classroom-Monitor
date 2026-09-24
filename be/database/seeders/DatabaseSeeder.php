<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            VaiTroSeeder::class,
            NguoiDungSeeder::class,
            LopHocSeeder::class,
            ThanhVienLopSeeder::class,
            BaiTapSeeder::class,
            FileBaiTapSeeder::class,
            BaiNopSeeder::class,
            DiemSeeder::class,
            PhienGiamSatSeeder::class,
            HoatDongSeeder::class,
            AnhManHinhSeeder::class,
            CanhBaoSeeder::class,
        ]);
    }
}