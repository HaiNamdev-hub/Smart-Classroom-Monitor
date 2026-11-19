<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTabLeaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_retries_with_the_same_event_id_count_once_and_three_distinct_leaves_lock_the_exam(): void
    {
        $now = now();
        $roleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Test Student',
            'email' => 'tab-leave@example.test',
            'password' => bcrypt('password'),
            'vai_tro_id' => $roleId,
            'trang_thai' => 'hoat_dong',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => 'TEST',
            'ten_khoa' => 'Test Faculty',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId,
            'ma_nganh' => 'TEST-MAJOR',
            'ten_nganh' => 'Test Major',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $studentId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $userId,
            'nganh_id' => $majorId,
            'ma_sinh_vien' => 'TEST-STUDENT',
            'nam_trung_tuyen' => (int) $now->format('Y'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'TEST-CLASS',
            'ten_lop' => 'Test Class',
            'giang_vien_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId,
            'sinh_vien_id' => $userId,
            'trang_thai' => 'dang_hoc',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $assignmentId = DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId,
            'nguoi_tao_id' => $userId,
            'tieu_de' => 'Tab leave test',
            'thoi_gian_bat_dau' => $now->copy()->subHour(),
            'thoi_gian_ket_thuc' => $now->copy()->addHour(),
            'han_nop' => $now->copy()->addHour(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('bai_kiem_tra_luot_lam')->insert([
            'bai_tap_id' => $assignmentId,
            'sinh_vien_id' => $studentId,
            'bat_dau_luc' => $now->copy()->subMinutes(5),
            'created_at' => $now->copy()->subMinutes(5),
            'updated_at' => $now->copy()->subMinutes(5),
        ]);

        $this->assertGreaterThan(0, $studentId);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($userId));

        $eventId = 'a1b2c3d4-e5f6-4a12-8b34-c5d6e7f80910';
        $url = "/api/sinh-vien/bai-kiem-tra/{$assignmentId}/roi-tab";

        $this->postJson($url, ['eventId' => $eventId])
            ->assertOk()
            ->assertJsonPath('data.soLanViPham', 1)
            ->assertJsonPath('data.biKhoa', false);
        $this->postJson($url, ['eventId' => $eventId])
            ->assertOk()
            ->assertJsonPath('data.soLanViPham', 1)
            ->assertJsonPath('data.biKhoa', false);
        $this->assertDatabaseCount('vi_pham_kiem_tra', 1);

        foreach ([
            'b2c3d4e5-f6a1-4b23-9c45-d6e7f8091a2b',
            'c3d4e5f6-a1b2-4c34-ad56-e7f8091a2b3c',
        ] as $nextEventId) {
            $this->postJson($url, ['eventId' => $nextEventId])->assertOk();
        }

        $this->assertDatabaseCount('vi_pham_kiem_tra', 3);
        $this->postJson($url, ['eventId' => 'd4e5f6a1-b2c3-4d45-be67-f8091a2b3c4d'])
            ->assertOk()
            ->assertJsonPath('data.soLanViPham', 3)
            ->assertJsonPath('data.biKhoa', true)
            ->assertJsonPath('data.lyDoKhoa', 'roi_tab');
        $this->assertDatabaseCount('vi_pham_kiem_tra', 3);
    }
}
