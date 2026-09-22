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
        NguoiDung::query()->whereIn('email', ['sv.an@example.com', 'sv.binh@example.com'])->pluck('id')->each(fn(int $id) => ThanhVienLop::query()->updateOrCreate(['lop_hoc_id' => $lopId, 'sinh_vien_id' => $id], ['trang_thai' => 'dang_hoc']));

        $students = [];
        for ($i = 1; $i <= 20; $i++) {
            $students[] = NguoiDung::query()->where('email', sprintf('sv%02d@example.com', $i))->firstOrFail();
        }
        foreach (LopHoc::query()->whereIn('ma_lop', ['DEMO01', 'DEMO02', 'DEMO03', 'DEMO04', 'DEMO05'])->orderBy('ma_lop')->get() as $index => $class) {
            // Mỗi lớp có 12 thành viên; hai tài khoản không hoạt động không tham gia phiên học.
            for ($i = 0; $i < 12; $i++) {
                $student = $students[($index * 3 + $i) % 18];
                ThanhVienLop::query()->firstOrCreate(
                    ['lop_hoc_id' => $class->id, 'sinh_vien_id' => $student->id],
                    ['trang_thai' => 'dang_hoc'],
                );
            }
            ThanhVienLop::query()->firstOrCreate(
                ['lop_hoc_id' => $class->id, 'sinh_vien_id' => $students[19]->id],
                ['trang_thai' => 'da_roi_lop'],
            );
        }
    }
}
