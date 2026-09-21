<?php

namespace Database\Seeders;

use App\Models\LopHoc;
use App\Models\NguoiDung;
use App\Models\ThanhVienLop;
use Illuminate\Database\Seeder;

class ThanhVienLopSeeder extends Seeder
{
    public function run(): void
    {
        $lopId = LopHoc::query()->where('ma_lop', 'SE01')->value('id');
        NguoiDung::query()->whereIn('email', ['sv.an@example.com', 'sv.binh@example.com'])->pluck('id')->each(fn (int $id) => ThanhVienLop::query()->updateOrCreate(['lop_hoc_id' => $lopId, 'sinh_vien_id' => $id], ['trang_thai' => 'dang_hoc']));
    }
}
