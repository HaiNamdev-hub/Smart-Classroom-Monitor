<?php

namespace Database\Seeders;

use App\Models\AnhManHinh;
use App\Models\BaiTap;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use App\Models\PhienGiamSat;
use App\Models\ThanhVienLop;
use Illuminate\Database\Seeder;

class AnhManHinhSeeder extends Seeder
{
    public function run(): void
    {
        AnhManHinh::query()->updateOrCreate(['duong_dan' => 'anh-man-hinh/sv-an-001.png'], ['phien_id' => PhienGiamSat::query()->where('ten_phien', 'Phien giam sat bai tap Laravel')->value('id'), 'sinh_vien_id' => NguoiDung::query()->where('email', 'sv.an@example.com')->value('id'), 'thoi_gian_chup' => now()->subMinutes(3)]);

        foreach (LopHoc::query()->whereIn('ma_lop', ['DEMO01', 'DEMO02', 'DEMO03', 'DEMO04', 'DEMO05'])->orderBy('ma_lop')->get() as $class) {
            for ($number = 0; $number < 3; $number++) {
                $assignment = BaiTap::query()->where('lop_hoc_id', $class->id)->where('tieu_de', 'Bài thực hành '.($number + 1).' — '.$class->ten_lop)->firstOrFail();
                $prefix = 'demo/'.$class->ma_lop.'/bai-'.($number + 1);
                $session = PhienGiamSat::query()->where('lop_hoc_id', $class->id)->where('bai_tap_id', $assignment->id)->firstOrFail();
                if ($session->trang_thai === 'chua_bat_dau') {
                    continue;
                }
                $members = ThanhVienLop::query()->where('lop_hoc_id', $class->id)->where('trang_thai', 'dang_hoc')->orderBy('id')->pluck('sinh_vien_id');
                foreach ($members as $memberIndex => $studentId) {
                    $eventTime = $assignment->thoi_gian_bat_dau->copy()->addMinutes(10 + $memberIndex);
                    AnhManHinh::query()->firstOrCreate(
                        ['duong_dan' => $prefix.'/man-hinh-'.$studentId.'.png'],
                        ['phien_id' => $session->id, 'sinh_vien_id' => $studentId, 'thoi_gian_chup' => $eventTime->copy()->addMinutes(3)],
                    );
                }
            }
        }
    }
}
