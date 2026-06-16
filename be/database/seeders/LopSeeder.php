<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LopSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $khoaIds = DB::table('khoa')->pluck('id', 'ma_khoa');
        $nganhIds = DB::table('nganh')->pluck('id', 'ma_nganh');
        $userIds = DB::table('nguoi_dung')->pluck('id', 'email');

        $lopData = [
            [
                'ma_lop' => 'CNTT62',
                'ten_lop' => 'Công nghệ thông tin 62',
                'khoa_id' => $khoaIds['CNTT'],
                'nganh_id' => $nganhIds['CNTT'],
                'co_van_id' => $userIds['covan1@example.com'],
                'lop_truong_id' => $userIds['loptruong1@example.com'],
                'bi_thu_id' => $userIds['bithu1@example.com'],
            ],
            [
                'ma_lop' => 'KT62',
                'ten_lop' => 'Kinh tế 62',
                'khoa_id' => $khoaIds['KinhTe'],
                'nganh_id' => $nganhIds['KT'],
                'co_van_id' => $userIds['covan2@example.com'],
                'lop_truong_id' => $userIds['loptruong2@example.com'],
                'bi_thu_id' => $userIds['bithu2@example.com'],
            ],
            [
                'ma_lop' => 'CK62',
                'ten_lop' => 'Cơ khí 62',
                'khoa_id' => $khoaIds['CoKhi'],
                'nganh_id' => $nganhIds['CK'],
                'co_van_id' => $userIds['covan1@example.com'],
                'lop_truong_id' => $userIds['loptruong1@example.com'],
                'bi_thu_id' => $userIds['bithu2@example.com'],
            ],
        ];

        foreach ($lopData as $lop) {
            DB::table('lop')->updateOrInsert(
                ['ma_lop' => $lop['ma_lop']],
                $lop
            );
        }
    }
}
