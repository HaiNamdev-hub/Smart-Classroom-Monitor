<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_data_has_valid_relationships_and_can_be_run_again(): void
    {
        $this->seed();

        $counts = ['vai_tro' => 3, 'nguoi_dung' => 27, 'lop_hoc' => 6, 'thanh_vien_lop' => 67,
            'bai_tap' => 16, 'file_bai_tap' => 16, 'bai_nop' => 51, 'diem' => 41,
            'phien_giam_sat' => 16, 'hoat_dong' => 361, 'anh_man_hinh' => 121, 'canh_bao' => 51];
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertTrue(Hash::check('password', NguoiDung::where('email', 'sv01@example.com')->firstOrFail()->password));

        $invalidSubmissions = DB::table('bai_nop as n')->join('bai_tap as b', 'b.id', '=', 'n.bai_tap_id')
            ->leftJoin('thanh_vien_lop as t', function ($join) {
                $join->on('t.lop_hoc_id', '=', 'b.lop_hoc_id')->on('t.sinh_vien_id', '=', 'n.sinh_vien_id');
            })->whereNull('t.id')->count();
        $this->assertSame(0, $invalidSubmissions);
        $this->assertSame(0, DB::table('bai_nop as n')->join('bai_tap as b', 'b.id', '=', 'n.bai_tap_id')
            ->where('n.trang_thai', 'tre_han')->whereColumn('n.thoi_gian_nop', '<=', 'b.han_nop')->count());
        $this->assertSame(0, DB::table('diem as d')->join('bai_nop as n', 'n.id', '=', 'd.bai_nop_id')
            ->whereColumn('d.thoi_gian_cham', '<', 'n.thoi_gian_nop')->count());

        $this->travel(1)->days();
        $this->seed();
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->travelBack();
    }
}
