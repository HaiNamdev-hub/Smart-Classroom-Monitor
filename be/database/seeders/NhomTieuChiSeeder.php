<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NhomTieuChiSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $groups = [
            [
                'ma_nhom' => 'P1',
                'ten_nhom' => 'I. Ý thức tham gia học tập',
                'diem_toi_da' => 20,
            ],
            [
                'ma_nhom' => 'P2',
                'ten_nhom' => 'II. Chấp hành tốt nội quy, quy chế và các quy định khác được áp dụng trong Học viện',
                'diem_toi_da' => 25,
            ],
            [
                'ma_nhom' => 'P3',
                'ten_nhom' => 'III. Ý thức và kết quả tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao, phòng chống tệ nạn xã hội',
                'diem_toi_da' => 20,
            ],
            [
                'ma_nhom' => 'P4',
                'ten_nhom' => 'IV. Ý thức công dân trong quan hệ cộng đồng',
                'diem_toi_da' => 25,
            ],
            [
                'ma_nhom' => 'P5',
                'ten_nhom' => 'V. Ý thức và kết quả tham gia Ban cán sự lớp, BCH Đoàn, Ban chủ nhiệm các Ban, CLB, Đội, Hội, Nhóm',
                'diem_toi_da' => 10,
            ],
        ];

        foreach ($groups as $group) {
            DB::table('nhom_tieu_chi')->updateOrInsert(
                ['ma_nhom' => $group['ma_nhom']],
                $group
            );
        }
    }
}