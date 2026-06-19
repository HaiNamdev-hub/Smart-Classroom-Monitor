<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class NguoiDungSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $vaiTroIds = DB::table('vai_tro')->pluck('id', 'ten_vai_tro');

        $users = [
            [
                'ho_ten' => 'Administrator',
                'email' => 'admin@example.com',
                'email_verified_at' => now(),
                'verification_token' => null,
                'password' => Hash::make('Password123!'),
                'so_dien_thoai' => '0912345678',
                'ngay_sinh' => '1990-01-01',
                'avatar' => null,
                'vai_tro_id' => $vaiTroIds['admin'],
                'trang_thai' => 'hoat_dong',
            ],
            [
                'ho_ten' => 'Phong QLDT',
                'email' => 'phongqldt@example.com',
                'email_verified_at' => now(),
                'verification_token' => null,
                'password' => Hash::make('Password123!'),
                'so_dien_thoai' => '0987654321',
                'ngay_sinh' => '1988-03-15',
                'avatar' => null,
                'vai_tro_id' => $vaiTroIds['phong_qldt'],
                'trang_thai' => 'hoat_dong',
            ],
            [
                'ho_ten' => 'Cố vấn 1',
                'email' => 'covan1@example.com',
                'email_verified_at' => now(),
                'verification_token' => null,
                'password' => Hash::make('Password123!'),
                'so_dien_thoai' => $faker->phoneNumber(),
                'ngay_sinh' => '1985-05-10',
                'avatar' => null,
                'vai_tro_id' => $vaiTroIds['co_van'],
                'trang_thai' => 'hoat_dong',
            ],
            [
                'ho_ten' => 'Cố vấn 2',
                'email' => 'covan2@example.com',
                'email_verified_at' => now(),
                'verification_token' => null,
                'password' => Hash::make('Password123!'),
                'so_dien_thoai' => $faker->phoneNumber(),
                'ngay_sinh' => '1986-07-22',
                'avatar' => null,
                'vai_tro_id' => $vaiTroIds['co_van'],
                'trang_thai' => 'hoat_dong',
            ],
            [
                'ho_ten' => 'Lớp trưởng 1',
                'email' => 'loptruong1@example.com',
                'email_verified_at' => now(),
                'verification_token' => null,
                'password' => Hash::make('Password123!'),
                'so_dien_thoai' => $faker->phoneNumber(),
                'ngay_sinh' => '2002-09-14',
                'avatar' => null,
                'vai_tro_id' => $vaiTroIds['lop_truong'],
                'trang_thai' => 'hoat_dong',
            ],
            [
                'ho_ten' => 'Lớp trưởng 2',
                'email' => 'loptruong2@example.com',
                'email_verified_at' => now(),
                'verification_token' => null,
                'password' => Hash::make('Password123!'),
                'so_dien_thoai' => $faker->phoneNumber(),
                'ngay_sinh' => '2002-11-02',
                'avatar' => null,
                'vai_tro_id' => $vaiTroIds['lop_truong'],
                'trang_thai' => 'hoat_dong',
            ],
            [
                'ho_ten' => 'Bí thư 1',
                'email' => 'bithu1@example.com',
                'email_verified_at' => now(),
                'verification_token' => null,
                'password' => Hash::make('Password123!'),
                'so_dien_thoai' => $faker->phoneNumber(),
                'ngay_sinh' => '2001-08-30',
                'avatar' => null,
                'vai_tro_id' => $vaiTroIds['bi_thu'],
                'trang_thai' => 'hoat_dong',
            ],
            [
                'ho_ten' => 'Bí thư 2',
                'email' => 'bithu2@example.com',
                'email_verified_at' => now(),
                'verification_token' => null,
                'password' => Hash::make('Password123!'),
                'so_dien_thoai' => $faker->phoneNumber(),
                'ngay_sinh' => '2001-06-25',
                'avatar' => null,
                'vai_tro_id' => $vaiTroIds['bi_thu'],
                'trang_thai' => 'hoat_dong',
            ],
        ];

        for ($index = 1; $index <= 10; $index++) {
            $users[] = [
                'ho_ten' => "Sinh viên $index",

                'email' => "sinhvien{$index}@example.com",

                'email_verified_at' => now(),

                'verification_token' => null,

                'password' => Hash::make('Password123!'),

                'so_dien_thoai' => $faker->phoneNumber(),

                'ngay_sinh' => $faker->date('Y-m-d', '2004-01-01'),

                'avatar' => null,

                'vai_tro_id' => $vaiTroIds['sinh_vien'],

                'trang_thai' => 'hoat_dong',
            ];
        }

        foreach ($users as $user) {
            DB::table('nguoi_dung')->updateOrInsert(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
