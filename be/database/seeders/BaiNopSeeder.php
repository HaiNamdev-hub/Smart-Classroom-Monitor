<?php

namespace Database\Seeders;

use App\Models\BaiNop;
use App\Models\BaiTap;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use App\Models\ThanhVienLop;
use Illuminate\Database\Seeder;

class BaiNopSeeder extends Seeder
{
    public function run(): void
    {
        BaiNop::query()->updateOrCreate(['bai_tap_id' => BaiTap::query()->where('tieu_de', 'Bai tap Laravel co ban')->value('id'), 'sinh_vien_id' => NguoiDung::query()->where('email', 'sv.an@example.com')->value('id')], ['ten_file' => 'bai-lam-tran-minh-an.zip', 'duong_dan' => 'bai-nop/bai-lam-tran-minh-an.zip', 'loai_file' => 'application/zip', 'thoi_gian_nop' => now(), 'trang_thai' => 'dung_han']);

        foreach (LopHoc::query()->whereIn('ma_lop', ['DEMO01', 'DEMO02', 'DEMO03', 'DEMO04', 'DEMO05'])->orderBy('ma_lop')->get() as $class) {
            for ($number = 0; $number < 3; $number++) {
                $assignment = BaiTap::query()->where('lop_hoc_id', $class->id)->where('tieu_de', 'Bài thực hành ' . ($number + 1) . ' — ' . $class->ten_lop)->firstOrFail();
                $prefix = 'demo/' . $class->ma_lop . '/bai-' . ($number + 1);
                if ($number !== 0) {
                    continue;
                }
                $members = ThanhVienLop::query()->where('lop_hoc_id', $class->id)->where('trang_thai', 'dang_hoc')->orderBy('id')->pluck('sinh_vien_id');
                foreach ($members->take(10) as $memberIndex => $studentId) {
                    $late = $memberIndex % 4 === 0;
                    $submission = BaiNop::query()->firstOrCreate(
                        ['bai_tap_id' => $assignment->id, 'sinh_vien_id' => $studentId],
                        [
                            'ten_file' => 'bai-lam.zip',
                            'duong_dan' => $prefix . '/bai-nop-' . $studentId . '.zip',
                            'loai_file' => 'application/zip',
                            'trang_thai' => $late ? 'tre_han' : 'dung_han',
                            'thoi_gian_nop' => $assignment->han_nop->copy()->addMinutes($late ? 15 : -30),
                        ],
                    );
                }
            }
        }
    }
}
