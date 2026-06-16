<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChiTietDanhGiaSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phieuIds = DB::table('phieu_ren_luyen')->pluck('id');
        $criteria = DB::table('tieu_chi')->get();
        $options = DB::table('tieu_chi_lua_chon')->get()->groupBy('tieu_chi_id');

        foreach ($phieuIds as $phieuId) {
            foreach ($criteria as $criterion) {
                $luaChonId = null;
                $giaTriNhap = null;
                $diemSv = 0;
                $diemLop = 0;
                $diemDuyet = 0;

                if ($criterion->loai_nhap === 'combobox') {
                    $choice = $options[$criterion->id]->first();
                    $luaChonId = $choice?->id;
                    $diemSv = $choice?->diem_tuong_ung ?? 0;
                    $diemLop = $diemSv;
                    $diemDuyet = $diemSv;
                } elseif ($criterion->loai_nhap === 'checkbox') {
                    $choice = $options[$criterion->id]->last();
                    $luaChonId = $choice?->id;
                    $diemSv = $choice?->diem_tuong_ung ?? 0;
                    $diemLop = max(0, $diemSv);
                    $diemDuyet = max(0, $diemSv);
                } else {
                    $giaTriNhap = 'Hoạt động tích cực và tuân thủ nội quy';
                    $diemSv = min($criterion->diem_toi_da, 8);
                    $diemLop = $diemSv;
                    $diemDuyet = $diemSv;
                }

                DB::table('chi_tiet_danh_gia')->updateOrInsert(
                    ['phieu_id' => $phieuId, 'tieu_chi_id' => $criterion->id],
                    [
                        'lua_chon_id' => $luaChonId,
                        'gia_tri_nhap' => $giaTriNhap,
                        'diem_sv' => $diemSv,
                        'diem_lop' => $diemLop,
                        'diem_duyet' => $diemDuyet,
                        'ghi_chu' => 'Dữ liệu mẫu',
                    ]
                );
            }
        }
    }
}
