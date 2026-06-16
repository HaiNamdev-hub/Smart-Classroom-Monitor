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
        $faker = Faker::create('vi_VN');
        $lopIds = DB::table('lop')->pluck('id', 'ma_lop');
        $userIds = DB::table('nguoi_dung')->where('vai_tro_id', DB::table('vai_tro')->where('ten_vai_tro', 'sinh_vien')->value('id'))->pluck('id', 'email');

        $students = [];
        foreach ($userIds as $index => $userId) {
            $students[] = [
                'ma_sinh_vien' => sprintf('SV62%03d', $index),
                'nam_trung_tuyen' => 2022,
                'user_id' => $userId,
                'lop_id' => $index <= 5 ? $lopIds['CNTT62'] : ($index <= 8 ? $lopIds['KT62'] : $lopIds['CK62']),
            ];
        }

        foreach ($students as $student) {
            DB::table('sinh_vien')->updateOrInsert(
                ['ma_sinh_vien' => $student['ma_sinh_vien']],
                $student
            );
        }
    }
}
