<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MinhChungSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $details = DB::table('chi_tiet_danh_gia')
            ->whereNotNull('gia_tri_nhap')
            ->limit(10)
            ->pluck('id');

        foreach ($details as $index => $detailId) {
            DB::table('minh_chung')->updateOrInsert(
                ['chi_tiet_id' => $detailId],
                [
                    'ten_file' => "giay_to_{$index}.pdf",
                    'duong_dan' => "uploads/giay_to_{$index}.pdf",
                    'trang_thai_ai' => $index % 3 === 0 ? 'valid' : 'pending',
                ]
            );
        }
    }
}
