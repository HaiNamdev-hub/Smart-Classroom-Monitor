<?php

namespace Database\Seeders;

use App\Models\BaiTap;
use App\Models\CanhBao;
use App\Models\LopHoc;
use App\Models\NguoiDung;
use App\Models\PhienGiamSat;
use App\Models\ThanhVienLop;
use Illuminate\Database\Seeder;

class CanhBaoSeeder extends Seeder
{
    public function run(): void
    {
        CanhBao::query()->updateOrCreate(['phien_id' => PhienGiamSat::query()->where('ten_phien', 'Phien giam sat bai tap Laravel')->value('id'), 'sinh_vien_id' => NguoiDung::query()->where('email', 'sv.binh@example.com')->value('id'), 'loai_canh_bao' => 'mat_ket_noi'], ['noi_dung' => 'Sinh vien mat ket noi trong phien giam sat.', 'thoi_gian' => now()->subMinutes(2), 'da_xu_ly' => false]);

        foreach (LopHoc::query()->whereIn('ma_lop', ['DEMO01', 'DEMO02', 'DEMO03', 'DEMO04', 'DEMO05'])->orderBy('ma_lop')->get() as $class) {
            for ($number = 0; $number < 3; $number++) {
                $assignment = BaiTap::query()->where('lop_hoc_id', $class->id)->where('tieu_de', 'Bài thực hành ' . ($number + 1) . ' — ' . $class->ten_lop)->firstOrFail();
                $session = PhienGiamSat::query()->where('lop_hoc_id', $class->id)->where('bai_tap_id', $assignment->id)->firstOrFail();
                if ($session->trang_thai === 'chua_bat_dau') {
                    continue;
                }
                $members = ThanhVienLop::query()->where('lop_hoc_id', $class->id)->where('trang_thai', 'dang_hoc')->orderBy('id')->pluck('sinh_vien_id');
                foreach ($members as $memberIndex => $studentId) {
                    $eventTime = $assignment->thoi_gian_bat_dau->copy()->addMinutes(10 + $memberIndex);
                    if ($memberIndex < 5) {
                        $type = ['ung_dung_khong_cho_phep', 'website_khong_cho_phep', 'copy_paste', 'roi_phien', 'mat_ket_noi'][$memberIndex];
                        CanhBao::query()->firstOrCreate(
                            ['phien_id' => $session->id, 'sinh_vien_id' => $studentId, 'loai_canh_bao' => $type],
                            [
                                'noi_dung' => 'Cảnh báo minh họa: ' . str_replace('_', ' ', $type) . '.',
                                'thoi_gian' => $eventTime->copy()->addMinutes(4),
                                'da_xu_ly' => $memberIndex % 2 === 0,
                            ],
                        );
                    }
                }
            }
        }
    }
}
