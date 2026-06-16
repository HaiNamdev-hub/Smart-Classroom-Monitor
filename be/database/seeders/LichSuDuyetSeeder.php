<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LichSuDuyetSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phanHoiIds = DB::table('phieu_ren_luyen')
            ->where('trang_thai', 'submitted')
            ->pluck('id');

        $reviewers = [
            'loptruong1@example.com' => 'lop_truong',
            'bithu1@example.com' => 'bi_thu',
            'covan1@example.com' => 'co_van',
            'phongqldt@example.com' => 'phong_qldt',
        ];

        foreach ($phanHoiIds as $phieuId) {
            foreach ($reviewers as $email => $vaiTro) {
                DB::table('lich_su_duyet')->insert([
                    'phieu_id' => $phieuId,
                    'nguoi_duyet_id' => DB::table('nguoi_dung')->where('email', $email)->value('id'),
                    'vai_tro_duyet' => $vaiTro,
                    'ghi_chu' => "Phê duyệt bởi {$email}",
                ]);
            }
        }
    }
}
