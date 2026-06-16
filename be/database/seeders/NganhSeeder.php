<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NganhSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nganhData = [
            ['ma_nganh' => 'CNTT', 'ten_nganh' => 'Công nghệ thông tin'],
            ['ma_nganh' => 'KT', 'ten_nganh' => 'Kinh tế'],
            ['ma_nganh' => 'CK', 'ten_nganh' => 'Cơ khí'],
        ];

        foreach ($nganhData as $nganh) {
            DB::table('nganh')->updateOrInsert(
                ['ma_nganh' => $nganh['ma_nganh']],
                $nganh
            );
        }
    }
}
