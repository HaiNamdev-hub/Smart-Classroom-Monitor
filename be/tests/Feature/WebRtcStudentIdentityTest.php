<?php

namespace Tests\Feature;

use App\Events\WebRtcSignalEvent;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebRtcStudentIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_signaling_and_private_channel_resolve_student_profile_id_to_membership_user_id(): void
    {
        $now = now();
        $roleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $teacherId = $this->createUser($roleId, 'teacher-webrtc@example.test', $now);
        $studentUserId = $this->createUser($roleId, 'student-webrtc@example.test', $now);
        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => 'RTC-FAC',
            'ten_khoa' => 'RTC Faculty',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId,
            'ma_nganh' => 'RTC-MAJOR',
            'ten_nganh' => 'RTC Major',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Deliberately create profile ID 1 for account ID 2.
        $studentId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $studentUserId,
            'nganh_id' => $majorId,
            'ma_sinh_vien' => 'RTC-001',
            'nam_trung_tuyen' => (int) $now->format('Y'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->assertNotSame($studentUserId, $studentId);

        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'RTC-CLASS',
            'ten_lop' => 'RTC Class',
            'giang_vien_id' => $teacherId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId,
            'sinh_vien_id' => $studentUserId,
            'trang_thai' => 'dang_hoc',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $assignmentId = DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId,
            'nguoi_tao_id' => $teacherId,
            'tieu_de' => 'RTC identity test',
            'thoi_gian_bat_dau' => $now->copy()->subHour(),
            'thoi_gian_ket_thuc' => $now->copy()->addHour(),
            'han_nop' => $now->copy()->addHour(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $sessionId = DB::table('phien_giam_sat')->insertGetId([
            'lop_hoc_id' => $classId,
            'bai_tap_id' => $assignmentId,
            'nguoi_tao_id' => $teacherId,
            'ten_phien' => 'RTC session',
            'thoi_gian_bat_dau' => $now->copy()->subMinute(),
            'trang_thai' => 'dang_dien_ra',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        ]);
        require base_path('routes/channels.php');

        Event::fake([WebRtcSignalEvent::class]);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $this->postJson('/api/webrtc/signal', [
            'phienId' => $sessionId,
            'sinhVienId' => $studentId,
            'type' => 'camera_ready',
            'data' => null,
        ])->assertOk();
        Event::assertDispatched(WebRtcSignalEvent::class, fn ($event) => $event->sinhVienId === $studentId);

        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));
        $callback = Broadcast::connection('reverb')->getChannels()->get('exam-monitor.{phienId}.student.{sinhVienId}');
        $this->assertTrue($callback(NguoiDung::query()->findOrFail($teacherId), $sessionId, $studentId));
        $sessionCallback = Broadcast::connection('reverb')->getChannels()->get('exam-session.{phienId}');
        $this->assertTrue($sessionCallback(NguoiDung::query()->findOrFail($teacherId), $sessionId));
        $unassignedUserId = $this->createUser($roleId, 'unassigned-session-user@example.test', $now);
        $this->assertFalse($sessionCallback(NguoiDung::query()->findOrFail($unassignedUserId), $sessionId));
        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => "private-exam-monitor.{$sessionId}.student.{$studentId}",
        ])->assertOk();
    }

    private function createUser(int $roleId, string $email, $now): int
    {
        return DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'RTC User',
            'email' => $email,
            'password' => bcrypt('password'),
            'vai_tro_id' => $roleId,
            'trang_thai' => 'hoat_dong',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
