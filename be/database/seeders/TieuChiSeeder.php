<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TieuChiSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groupIds = DB::table('nhom_tieu_chi')->pluck('id', 'ma_nhom');

        $criteria = [
            ['nhom_id' => $groupIds['P1'], 'ma_tieu_chi' => 'TC01', 'ten_tieu_chi' => 'Chuyên cần tham gia học tập', 'diem_toi_da' => 15, 'loai_tieu_chi' => 'thuong', 'loai_nhap' => 'combobox'],
            ['nhom_id' => $groupIds['P1'], 'ma_tieu_chi' => 'TC02', 'ten_tieu_chi' => 'Hoàn thành bài tập cá nhân', 'diem_toi_da' => 15, 'loai_tieu_chi' => 'thuong', 'loai_nhap' => 'checkbox'],
            ['nhom_id' => $groupIds['P2'], 'ma_tieu_chi' => 'TC03', 'ten_tieu_chi' => 'Chấp hành nội quy lớp', 'diem_toi_da' => 10, 'loai_tieu_chi' => 'thuong', 'loai_nhap' => 'textbox'],
            ['nhom_id' => $groupIds['P2'], 'ma_tieu_chi' => 'TC04', 'ten_tieu_chi' => 'Không vi phạm kỷ luật', 'diem_toi_da' => 10, 'loai_tieu_chi' => 'thuong', 'loai_nhap' => 'checkbox'],
            ['nhom_id' => $groupIds['P3'], 'ma_tieu_chi' => 'TC05', 'ten_tieu_chi' => 'Tham gia hoạt động ngoại khóa', 'diem_toi_da' => 15, 'loai_tieu_chi' => 'thuong', 'loai_nhap' => 'combobox'],
            ['nhom_id' => $groupIds['P3'], 'ma_tieu_chi' => 'TC06', 'ten_tieu_chi' => 'Vi phạm quy tắc an toàn', 'diem_toi_da' => 20, 'loai_tieu_chi' => 'phat', 'loai_nhap' => 'checkbox'],
        ];

        foreach ($criteria as $criterion) {
            DB::table('tieu_chi')->updateOrInsert(
                ['ma_tieu_chi' => $criterion['ma_tieu_chi']],
                $criterion
            );
        }
    }
}
