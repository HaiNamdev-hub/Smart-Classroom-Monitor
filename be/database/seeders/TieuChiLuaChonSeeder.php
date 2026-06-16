<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TieuChiLuaChonSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tieuChiIds = DB::table('tieu_chi')->pluck('id', 'ma_tieu_chi');

        $options = [
            ['tieu_chi_id' => $tieuChiIds['TC01'], 'noi_dung' => 'Có mặt đầy đủ', 'diem_tuong_ung' => 15],
            ['tieu_chi_id' => $tieuChiIds['TC01'], 'noi_dung' => 'Vắng 1-2 buổi', 'diem_tuong_ung' => 10],
            ['tieu_chi_id' => $tieuChiIds['TC01'], 'noi_dung' => 'Vắng quá 2 buổi', 'diem_tuong_ung' => 5],
            ['tieu_chi_id' => $tieuChiIds['TC02'], 'noi_dung' => 'Hoàn thành đủ', 'diem_tuong_ung' => 15],
            ['tieu_chi_id' => $tieuChiIds['TC02'], 'noi_dung' => 'Hoàn thành một phần', 'diem_tuong_ung' => 7],
            ['tieu_chi_id' => $tieuChiIds['TC04'], 'noi_dung' => 'Không vi phạm', 'diem_tuong_ung' => 10],
            ['tieu_chi_id' => $tieuChiIds['TC04'], 'noi_dung' => 'Vi phạm nhẹ', 'diem_tuong_ung' => 0],
            ['tieu_chi_id' => $tieuChiIds['TC05'], 'noi_dung' => 'Tham gia tích cực', 'diem_tuong_ung' => 15],
            ['tieu_chi_id' => $tieuChiIds['TC05'], 'noi_dung' => 'Tham gia bình thường', 'diem_tuong_ung' => 10],
            ['tieu_chi_id' => $tieuChiIds['TC05'], 'noi_dung' => 'Không tham gia', 'diem_tuong_ung' => 0],
            ['tieu_chi_id' => $tieuChiIds['TC06'], 'noi_dung' => 'Không vi phạm', 'diem_tuong_ung' => 0],
            ['tieu_chi_id' => $tieuChiIds['TC06'], 'noi_dung' => 'Vi phạm nhẹ', 'diem_tuong_ung' => -5],
            ['tieu_chi_id' => $tieuChiIds['TC06'], 'noi_dung' => 'Vi phạm nghiêm trọng', 'diem_tuong_ung' => -15],
        ];

        foreach ($options as $option) {
            DB::table('tieu_chi_lua_chon')->updateOrInsert(
                ['tieu_chi_id' => $option['tieu_chi_id'], 'noi_dung' => $option['noi_dung']],
                $option
            );
        }
    }
}
