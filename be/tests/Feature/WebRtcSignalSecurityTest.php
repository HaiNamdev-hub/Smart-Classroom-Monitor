<?php

namespace Tests\Feature;

use App\Events\WebRtcSignalEvent;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebRtcSignalSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionFixture(): array
    {
        $now = now();
        $roleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $teacherId = DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Teacher RTC',
            'email' => 'teacher-rtc@example.test',
            'password' => 'secret',
            'vai_tro_id' => $roleId,
            'trang_thai' => 'hoat_dong',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $studentUserId = DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Student RTC',
            'email' => 'student-rtc@example.test',
            'password' => 'secret',
            'vai_tro_id' => $roleId,
            'trang_thai' => 'hoat_dong',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => 'RTC-FAC',
            'ten_khoa' => 'RTC Faculty',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId,
            'ma_nganh' => 'RTC-MAJ',
            'ten_nganh' => 'RTC Major',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $studentProfileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $studentUserId,
            'nganh_id' => $majorId,
            'ma_sinh_vien' => 'RTC-SEC-001',
            'nam_trung_tuyen' => (int) $now->format('Y'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'RTC-SEC-CLASS',
            'ten_lop' => 'RTC Security Class',
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
            'tieu_de' => 'RTC security test',
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
            'ten_phien' => 'RTC Security Session',
            'thoi_gian_bat_dau' => $now->copy()->subMinute(),
            'trang_thai' => 'dang_dien_ra',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$teacherId, $studentUserId, $studentProfileId, $sessionId];
    }

    public function test_valid_offer_payload_passes_validation_and_dispatches_event(): void
    {
        [$teacherId, $studentUserId, $studentProfileId, $sessionId] = $this->createSessionFixture();
        Event::fake([WebRtcSignalEvent::class]);

        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        $this->postJson('/api/webrtc/signal', [
            'phienId' => $sessionId,
            'sinhVienId' => $studentProfileId,
            'type' => 'offer',
            'data' => [
                'type' => 'offer',
                'sdp' => 'v=0\r\no=- 4611731400430051336 2 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\n',
            ],
        ])->assertOk();

        Event::assertDispatched(WebRtcSignalEvent::class, function ($event) use ($studentProfileId, $sessionId) {
            return $event->sinhVienId === $studentProfileId
                && $event->phienId === $sessionId
                && $event->type === 'offer'
                && $event->senderRole === 'student';
        });
    }

    public function test_oversized_sdp_payload_is_rejected(): void
    {
        [$teacherId, $studentUserId, $studentProfileId, $sessionId] = $this->createSessionFixture();
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        // SDP vượt quá 65536 ký tự
        $hugeSdp = str_repeat('a', 70000);

        $this->postJson('/api/webrtc/signal', [
            'phienId' => $sessionId,
            'sinhVienId' => $studentProfileId,
            'type' => 'offer',
            'data' => [
                'type' => 'offer',
                'sdp' => $hugeSdp,
            ],
        ])->assertStatus(422)
          ->assertJsonValidationErrors('data.sdp');
    }

    public function test_valid_ice_candidate_passes_and_oversized_candidate_is_rejected(): void
    {
        [$teacherId, $studentUserId, $studentProfileId, $sessionId] = $this->createSessionFixture();
        Event::fake([WebRtcSignalEvent::class]);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        // Candidate hợp lệ
        $this->postJson('/api/webrtc/signal', [
            'phienId' => $sessionId,
            'sinhVienId' => $studentProfileId,
            'type' => 'candidate',
            'data' => [
                'candidate' => 'candidate:842163049 1 udp 1677729535 192.168.1.100 54321 typ srflx raddr 192.168.1.100 rport 54321',
                'sdpMid' => '0',
                'sdpMLineIndex' => 0,
            ],
        ])->assertOk();

        // Candidate quá khổ (>4096 ký tự)
        $this->postJson('/api/webrtc/signal', [
            'phienId' => $sessionId,
            'sinhVienId' => $studentProfileId,
            'type' => 'candidate',
            'data' => [
                'candidate' => str_repeat('x', 5000),
                'sdpMid' => '0',
                'sdpMLineIndex' => 0,
            ],
        ])->assertStatus(422)
          ->assertJsonValidationErrors('data.candidate');
    }

    public function test_teacher_can_send_request_offer_with_restart(): void
    {
        [$teacherId, $studentUserId, $studentProfileId, $sessionId] = $this->createSessionFixture();
        Event::fake([WebRtcSignalEvent::class]);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));

        $this->postJson('/api/webrtc/signal', [
            'phienId' => $sessionId,
            'sinhVienId' => $studentProfileId,
            'type' => 'request_offer',
            'data' => [
                'restart' => true,
            ],
        ])->assertOk();

        Event::assertDispatched(WebRtcSignalEvent::class, function ($event) use ($studentProfileId, $sessionId) {
            return $event->sinhVienId === $studentProfileId
                && $event->phienId === $sessionId
                && $event->type === 'request_offer'
                && $event->senderRole === 'teacher';
        });
    }

    public function test_unauthorized_user_cannot_signal_in_session(): void
    {
        [$teacherId, $studentUserId, $studentProfileId, $sessionId] = $this->createSessionFixture();

        $stranger = NguoiDung::query()->create([
            'ho_ten' => 'Stranger',
            'email' => 'stranger@example.test',
            'password' => 'secret',
            'vai_tro_id' => DB::table('vai_tro')->where('ten_vai_tro', 'sinh_vien')->value('id'),
            'trang_thai' => 'hoat_dong',
        ]);

        Sanctum::actingAs($stranger);

        $this->postJson('/api/webrtc/signal', [
            'phienId' => $sessionId,
            'sinhVienId' => $studentProfileId,
            'type' => 'camera_ready',
            'data' => null,
        ])->assertStatus(403);
    }
}
