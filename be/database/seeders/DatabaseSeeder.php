<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            VaiTroSeeder::class,
            ChucNangSeeder::class,
            PhanQuyenSeeder::class,
            KhoaSeeder::class,
            NganhSeeder::class,
            NguoiDungSeeder::class,
            LopSeeder::class,
            SinhVienSeeder::class,
            DotDanhGiaSeeder::class,
            PhieuRenLuyenSeeder::class,
            NhomTieuChiSeeder::class,
            TieuChiSeeder::class,
            TieuChiLuaChonSeeder::class,
            ChiTietDanhGiaSeeder::class,
            MinhChungSeeder::class,
            LichSuDuyetSeeder::class,
            LichSuKiemTraSeeder::class,
        ]);
    }
}
