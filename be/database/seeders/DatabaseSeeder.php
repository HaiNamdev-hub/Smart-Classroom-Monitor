<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. VAI TRÒ + NGƯỜI DÙNG
        |--------------------------------------------------------------------------
        */

        $this->call([
            VaiTroSeeder::class,
            NguoiDungSeeder::class,
        ]);


        /*
        |--------------------------------------------------------------------------
        | 2. KHOA + NGÀNH + SINH VIÊN
        |--------------------------------------------------------------------------
        */

        $this->call([
            KhoaSeeder::class,
            NganhSeeder::class,
            SinhVienSeeder::class,
        ]);


        /*
        |--------------------------------------------------------------------------
        | 3. LỚP HỌC
        |--------------------------------------------------------------------------
        */

        $this->call([
            LopHocSeeder::class,
            ThanhVienLopSeeder::class,
        ]);


        /*
        |--------------------------------------------------------------------------
        | 4. BÀI TẬP + BÀI NỘP + ĐIỂM
        |--------------------------------------------------------------------------
        */

        $this->call([
            BaiTapSeeder::class,
            FileBaiTapSeeder::class,
            BaiNopSeeder::class,
            DiemSeeder::class,
        ]);


        /*
        |--------------------------------------------------------------------------
        | 5. GIÁM SÁT
        |--------------------------------------------------------------------------
        */

        $this->call([
            PhienGiamSatSeeder::class,
            HoatDongSeeder::class,
            AnhManHinhSeeder::class,
            CanhBaoSeeder::class,
        ]);


        /*
        |--------------------------------------------------------------------------
        | 6. PHÂN QUYỀN
        |--------------------------------------------------------------------------
        */

        $this->call([
            ChucNangSeeder::class,
            PhanQuyenSeeder::class,
        ]);
    }
}