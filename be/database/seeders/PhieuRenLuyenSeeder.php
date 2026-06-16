<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PhieuRenLuyenSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dotId = DB::table('dot_danh_gia')->where(['nam_hoc' => '2025-2026', 'hoc_ky' => 1])->value('id');
        $students = DB::table('sinh_vien')->pluck('id');

        foreach ($students as $index => $studentId) {
            $tongDiemSv = 80 + ($index % 6) * 3;
            $tongDiemLop = $tongDiemSv + (($index % 3) * 2);
            $tongDiemCuoi = min($tongDiemSv, $tongDiemLop);
            $xeploai = match (true) {
                $tongDiemCuoi >= 95 => 'xuat_sac',
                $tongDiemCuoi >= 85 => 'tot',
                $tongDiemCuoi >= 75 => 'kha',
                $tongDiemCuoi >= 65 => 'trung_binh',
                $tongDiemCuoi >= 55 => 'yeu',
                default => 'kem',
            };

            DB::table('phieu_ren_luyen')->updateOrInsert(
                ['sinh_vien_id' => $studentId, 'dot_danh_gia_id' => $dotId],
                [
                    'tong_diem_sv' => $tongDiemSv,
                    'tong_diem_lop' => $tongDiemLop,
                    'tong_diem_cuoi' => $tongDiemCuoi,
                    'xep_loai' => $xeploai,
                    'trang_thai' => $index % 3 === 0 ? 'submitted' : 'draft',
                ]
            );
        }
    }
}
