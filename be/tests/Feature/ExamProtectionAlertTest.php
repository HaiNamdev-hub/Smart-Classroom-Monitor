<?php

namespace Tests\Feature;

use App\Events\ExamProtectionAlertEvent;
use App\Events\CanhBaoExamEvent;
use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamProtectionAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_and_tab_alert_event_targets_student_and_teacher_session_channels(): void
    {
        $eventId = (string) \Illuminate\Support\Str::uuid();
        $event = new CanhBaoExamEvent(
            phienId: 41,
            sinhVienId: 7,
            loaiCanhBao: 'roi_tab',
            noiDung: 'Rời tab',
            thoiGian: now()->toIso8601String(),
            soLanCamera: 0,
            soLanDienThoai: 0,
            soLanViPham: 1,
            eventId: $eventId,
            hoTen: 'Test Student',
            maSinhVien: 'SV0007',
            tenLop: 'Test Class',
            tenBaiKiemTra: 'Test Exam',
            baiTapId: 12,
        );

        $channelNames = collect($event->broadcastOn())->map(fn ($channel) => $channel->name);
        $this->assertTrue($channelNames->contains('private-exam-monitor.41.student.7'));
        $this->assertTrue($channelNames->contains('private-exam-session.41'));
        $this->assertSame($eventId, $event->broadcastWith()['eventId']);
        $this->assertSame('SV0007', $event->broadcastWith()['maSinhVien']);
    }

    public function test_student_cannot_begin_exam_when_stored_question_scores_do_not_total_ten(): void
    {
        [, $studentUserId, , $assignmentId] = $this->examFixture();
        DB::table('cau_hoi')->where('bai_tap_id', $assignmentId)->update(['diem' => 9]);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", [
            'fullscreen' => true,
            'cameraReady' => true,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('bai_kiem_tra_luot_lam', 0);
        $this->assertSame('9', (string) DB::table('cau_hoi')->where('bai_tap_id', $assignmentId)->value('diem'));
    }

    public function test_teacher_cannot_start_monitoring_session_with_invalid_score_total(): void
    {
        [$teacherId, , , $assignmentId] = $this->examFixture();
        $sessionId = DB::table('phien_giam_sat')->where('bai_tap_id', $assignmentId)->value('id');
        DB::table('phien_giam_sat')->where('id', $sessionId)->update(['trang_thai' => 'chua_bat_dau']);
        DB::table('cau_hoi')->where('bai_tap_id', $assignmentId)->update(['diem' => 9]);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));

        $this->postJson("/api/giang-vien/phien-giam-sat/{$sessionId}/bat-dau")
            ->assertUnprocessable();

        $this->assertDatabaseHas('phien_giam_sat', [
            'id' => $sessionId,
            'trang_thai' => 'chua_bat_dau',
        ]);
        $this->assertSame('9', (string) DB::table('cau_hoi')->where('bai_tap_id', $assignmentId)->value('diem'));
    }

    public function test_teacher_can_start_monitoring_session_when_stored_scores_total_ten(): void
    {
        [$teacherId, , , $assignmentId] = $this->examFixture();
        $sessionId = DB::table('phien_giam_sat')->where('bai_tap_id', $assignmentId)->value('id');
        DB::table('phien_giam_sat')->where('id', $sessionId)->update(['trang_thai' => 'chua_bat_dau']);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));

        $this->postJson("/api/giang-vien/phien-giam-sat/{$sessionId}/bat-dau")
            ->assertOk()
            ->assertJsonPath('data.trangThai', 'DANG_DIEN_RA');

        $this->assertDatabaseHas('phien_giam_sat', [
            'id' => $sessionId,
            'trang_thai' => 'dang_dien_ra',
        ]);
    }

    public function test_student_can_submit_empty_answers_after_exam_started(): void
    {
        [, $studentUserId, , $assignmentId] = $this->examFixture();
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", [
            'fullscreen' => true,
            'cameraReady' => true,
        ])->assertCreated();

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/nop", [
            'answers' => (object) [],
            'tuDongNop' => false,
        ])->assertCreated()->assertJsonPath('status', true);

        $this->assertDatabaseCount('bai_nop', 1);
        $this->assertDatabaseCount('cau_tra_loi', 0);
    }

    public function test_stale_draft_revision_cannot_overwrite_a_newer_server_draft(): void
    {
        [, $studentUserId, , $assignmentId] = $this->examFixture();
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", [
            'fullscreen' => true,
            'cameraReady' => true,
        ])->assertCreated();

        $this->getJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}")
            ->assertOk()
            ->assertJsonPath('data.draftRevision', null);

        $first = $this->putJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/nhap", [
            'answers' => ['1' => 'new server answer'],
            'baseDraftRevision' => null,
        ])->assertOk();
        $revision = $first->json('data.draftRevision');
        $this->assertIsString($revision);
        $this->assertSame(64, strlen($revision));

        $this->putJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/nhap", [
            'answers' => ['1' => 'stale offline answer'],
            'baseDraftRevision' => null,
        ])->assertStatus(409)->assertJsonPath('data.draftRevision', $revision);

        $this->assertSame(
            '{"1":"new server answer"}',
            DB::table('bai_lam_nhap')->where('bai_tap_id', $assignmentId)
                ->where('sinh_vien_id', DB::table('sinh_vien')->where('nguoi_dung_id', $studentUserId)->value('id'))
                ->value('cau_tra_loi')
        );
    }

    public function test_protection_event_is_idempotent_and_does_not_count_as_exam_violation(): void
    {
        Event::fake();
        [$teacherId, $studentUserId, $studentId, $assignmentId] = $this->examFixture();
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $eventId = (string) \Illuminate\Support\Str::uuid();
        $payload = [
            'eventId' => $eventId,
            'eventType' => 'copy_blocked',
            'occurredAt' => now()->toIso8601String(),
        ];
        $url = "/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bao-ve";

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/roi-tab", [
            'eventId' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertOk()->assertJsonPath('daBatDau', false);
        $this->getJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/trang-thai")
            ->assertOk()->assertJsonPath('data.trangThaiLamBai', 'PRE_EXAM');
        $this->assertSame(0, DB::table('vi_pham_kiem_tra')
            ->where('bai_tap_id', $assignmentId)->where('sinh_vien_id', $studentId)->count());
        $this->postJson($url, $payload)->assertStatus(409);
        $this->assertSame(0, DB::table('su_kien_bao_ve_bai_kiem_tra')->count());

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", [
            'fullscreen' => false,
            'cameraReady' => false,
        ])->assertUnprocessable();
        $startPayload = ['fullscreen' => true, 'cameraReady' => true];
        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", $startPayload)
            ->assertCreated()
            ->assertJsonPath('data.trangThaiLamBai', 'IN_PROGRESS');
        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", $startPayload)
            ->assertOk()
            ->assertJsonPath('data.duplicate', true);
        $this->assertSame(1, DB::table('bai_kiem_tra_luot_lam')
            ->where('bai_tap_id', $assignmentId)->where('sinh_vien_id', $studentId)->count());
        $this->getJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/trang-thai")
            ->assertOk()->assertJsonPath('data.trangThaiLamBai', 'IN_PROGRESS');

        $tabEventId = (string) \Illuminate\Support\Str::uuid();
        $tabUrl = "/api/sinh-vien/bai-kiem-tra/{$assignmentId}/roi-tab";
        $this->postJson($tabUrl, ['eventId' => $tabEventId])->assertOk();
        $this->postJson($tabUrl, ['eventId' => $tabEventId])->assertOk();
        $this->assertSame(1, DB::table('vi_pham_kiem_tra')
            ->where('bai_tap_id', $assignmentId)->where('sinh_vien_id', $studentId)->count());

        $firstResponse = $this->postJson($url, $payload);
        $this->assertSame(201, $firstResponse->status(), $firstResponse->getContent());
        $firstResponse
            ->assertCreated()
            ->assertJsonPath('data.accepted', true)
            ->assertJsonPath('data.duplicate', false)
            ->assertJsonPath('data.countsAsViolation', false);

        $this->postJson($url, $payload)
            ->assertOk()
            ->assertJsonPath('data.duplicate', true);

        $now = now('Asia/Ho_Chi_Minh');
        DB::table('bai_nop')->insert([
            'bai_tap_id' => $assignmentId,
            'sinh_vien_id' => $studentUserId,
            'ten_file' => 'submitted',
            'duong_dan' => '',
            'loai_file' => 'online_exam',
            'thoi_gian_nop' => $now,
            'trang_thai' => 'dung_han',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->postJson($tabUrl, ['eventId' => (string) \Illuminate\Support\Str::uuid()])
            ->assertOk()->assertJsonPath('daNop', true);
        $this->getJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/trang-thai")
            ->assertOk()->assertJsonPath('data.trangThaiLamBai', 'SUBMITTED');
        $this->postJson($url, [
            'eventId' => (string) \Illuminate\Support\Str::uuid(),
            'eventType' => 'focus_lost',
        ])->assertForbidden();

        $this->assertSame(1, DB::table('su_kien_bao_ve_bai_kiem_tra')
            ->where('event_id', $eventId)->count());
        $this->assertSame(1, DB::table('vi_pham_kiem_tra')
            ->where('bai_tap_id', $assignmentId)->where('sinh_vien_id', $studentId)->count());
        $this->assertSame(0, DB::table('canh_bao_camera')
            ->where('bai_tap_id', $assignmentId)->where('sinh_vien_id', $studentId)->count());
        Event::assertDispatchedTimes(ExamProtectionAlertEvent::class, 1);

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        ]);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));
        $this->getJson('/api/giang-vien/canh-bao')
            ->assertOk()
            ->assertJsonFragment([
                'baoVeEventId' => $eventId,
                'nguon' => 'bao_ve_bai_thi',
                'loaiCanhBao' => 'copy_blocked',
                'trangThai' => 'CAN_XEM_XET',
                'biKhoa' => false,
            ]);

        $this->postJson($url, [
            'eventId' => (string) \Illuminate\Support\Str::uuid(),
            'eventType' => 'fullscreen_exit',
        ])->assertForbidden();
    }

    public function test_exam_draft_accepts_empty_answer_map_and_persists_answered_questions(): void
    {
        [, $studentUserId, $studentId, $assignmentId] = $this->examFixture();
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", [
            'fullscreen' => true,
            'cameraReady' => true,
        ])->assertCreated();

        $draftUrl = "/api/sinh-vien/bai-kiem-tra/{$assignmentId}/nhap";
        $this->putJson($draftUrl, [])->assertUnprocessable()->assertJsonValidationErrors(['answers']);
        $emptyDraft = $this->putJson($draftUrl, [
            'answers' => (object) [],
            'baseDraftRevision' => null,
        ])->assertOk();

        $savedDraft = DB::table('bai_lam_nhap')
            ->where('bai_tap_id', $assignmentId)
            ->where('sinh_vien_id', $studentId)
            ->value('cau_tra_loi');
        $this->assertSame([], json_decode($savedDraft, true));

        $questionId = DB::table('cau_hoi')
            ->where('bai_tap_id', $assignmentId)
            ->value('id');
        $answers = [(string) $questionId => 'Bài làm nháp'];
        $this->putJson($draftUrl, [
            'answers' => $answers,
            'baseDraftRevision' => $emptyDraft->json('data.draftRevision'),
        ])->assertOk();

        $savedDraft = DB::table('bai_lam_nhap')
            ->where('bai_tap_id', $assignmentId)
            ->where('sinh_vien_id', $studentId)
            ->value('cau_tra_loi');
        $this->assertSame($answers, json_decode($savedDraft, true));
    }

    public function test_screenshot_shortcut_is_recorded_broadcast_and_scoped_to_its_exam_session(): void
    {
        Event::fake();
        $this->assertTrue(Schema::hasColumn('su_kien_bao_ve_bai_kiem_tra', 'thong_tin_tin_hieu'));
        $this->assertTrue(Schema::hasColumn('su_kien_bao_ve_bai_kiem_tra', 'phien_id'));
        [$teacherId, $studentUserId, $studentId, $assignmentId] = $this->examFixture();
        $eventUrl = "/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bao-ve";
        $payload = [
            'eventId' => (string) \Illuminate\Support\Str::uuid(),
            'eventType' => 'SCREENSHOT_SHORTCUT_DETECTED',
            'signal' => 'PRINT_SCREEN',
            'source' => 'keydown',
            'occurredAt' => now()->toIso8601String(),
        ];

        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $this->postJson($eventUrl, $payload)->assertConflict();
        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", [
            'fullscreen' => true,
            'cameraReady' => true,
        ])->assertCreated();

        $this->postJson($eventUrl, $payload)
            ->assertCreated()
            ->assertJsonPath('data.accepted', true)
            ->assertJsonPath('data.countsAsViolation', false);
        $this->postJson($eventUrl, $payload)
            ->assertOk()
            ->assertJsonPath('data.duplicate', true);

        $throttledPayload = [...$payload, 'eventId' => (string) \Illuminate\Support\Str::uuid()];
        $this->postJson($eventUrl, $throttledPayload)
            ->assertOk()
            ->assertJsonPath('data.accepted', false)
            ->assertJsonPath('data.rateLimited', true);

        $stored = DB::table('su_kien_bao_ve_bai_kiem_tra')
            ->where('event_id', $payload['eventId'])
            ->first();
        $this->assertSame('SCREENSHOT_SHORTCUT_DETECTED', $stored->loai_su_kien);
        $this->assertSame(['signal' => 'PRINT_SCREEN', 'source' => 'keydown'], json_decode($stored->thong_tin_tin_hieu, true));
        $this->assertSame(1, DB::table('su_kien_bao_ve_bai_kiem_tra')->count());
        $this->assertSame(0, DB::table('vi_pham_kiem_tra')->count());
        Event::assertDispatchedTimes(ExamProtectionAlertEvent::class, 1);

        $sessionId = DB::table('phien_giam_sat')->where('bai_tap_id', $assignmentId)->value('id');
        Event::assertDispatched(ExamProtectionAlertEvent::class, function (ExamProtectionAlertEvent $event) use ($sessionId, $studentId) {
            $channels = $event->broadcastOn();
            return $event->phienId === (int) $sessionId
                && $event->sinhVienId === $studentId
                && $event->signalInfo === ['signal' => 'PRINT_SCREEN', 'source' => 'keydown']
                && $channels[0]->name === "private-exam-monitor.{$sessionId}.student.{$studentId}"
                && $channels[1]->name === "private-exam-session.{$sessionId}";
        });

        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));
        $this->getJson("/api/giang-vien/phien-giam-sat/{$sessionId}/chi-tiet")
            ->assertOk()
            ->assertJsonPath('data.recentAlerts.0.loai', 'SCREENSHOT_SHORTCUT_DETECTED')
            ->assertJsonPath('data.recentAlerts.0.signalLabel', 'phím Print Screen')
            ->assertJsonPath('data.recentAlerts.0.daXem', false);
        $this->getJson('/api/giang-vien/canh-bao')
            ->assertOk()
            ->assertJsonFragment([
                'eventId' => $payload['eventId'],
                'loaiCanhBao' => 'SCREENSHOT_SHORTCUT_DETECTED',
                'daXem' => false,
                'tinHieu' => ['signal' => 'PRINT_SCREEN', 'source' => 'keydown'],
            ]);

        $reviewUrl = "/api/giang-vien/su-kien-bao-ve/{$payload['eventId']}/da-xem";
        $this->putJson($reviewUrl)->assertOk()->assertJsonPath('data.daXem', true);
        $this->assertDatabaseHas('su_kien_bao_ve_bai_kiem_tra', [
            'event_id' => $payload['eventId'],
            'da_xem' => true,
            'xem_boi_id' => $teacherId,
        ]);

        $otherTeacherId = $this->createUser(
            (int) DB::table('nguoi_dung')->where('id', $teacherId)->value('vai_tro_id'),
            'other-screenshot-teacher@example.test'
        );
        Sanctum::actingAs(NguoiDung::query()->findOrFail($otherTeacherId));
        $this->putJson($reviewUrl)->assertNotFound();

        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $now = now();
        DB::table('bai_nop')->insert([
            'bai_tap_id' => $assignmentId,
            'sinh_vien_id' => $studentUserId,
            'ten_file' => 'online',
            'duong_dan' => '',
            'thoi_gian_nop' => $now,
            'trang_thai' => 'dung_han',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->postJson($eventUrl, [...$payload, 'eventId' => (string) \Illuminate\Support\Str::uuid()])
            ->assertForbidden();
        $this->assertSame(1, DB::table('su_kien_bao_ve_bai_kiem_tra')->count());
    }

    private function examFixture(): array
    {
        $now = now('Asia/Ho_Chi_Minh');
        $studentRoleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $teacherRoleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'giang_vien', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $teacherId = $this->createUser($teacherRoleId, 'protection-teacher@example.test');
        $studentUserId = $this->createUser($studentRoleId, 'protection-student@example.test');
        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => uniqid('F'), 'ten_khoa' => 'Faculty', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId, 'ma_nganh' => uniqid('M'), 'ten_nganh' => 'Major', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $studentId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $studentUserId,
            'nganh_id' => $majorId,
            'ma_sinh_vien' => uniqid('S'),
            'nam_trung_tuyen' => (int) $now->format('Y'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => uniqid('C'), 'ten_lop' => 'Class', 'giang_vien_id' => $teacherId,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId, 'sinh_vien_id' => $studentUserId, 'trang_thai' => 'dang_hoc',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $assignmentId = DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId,
            'nguoi_tao_id' => $teacherId,
            'tieu_de' => 'Protected exam',
            'thoi_gian_bat_dau' => $now->copy()->subMinute(),
            'thoi_gian_ket_thuc' => $now->copy()->addHour(),
            'han_nop' => $now->copy()->addHour(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('cau_hoi')->insert([
            'bai_tap_id' => $assignmentId,
            'noi_dung' => 'Fixture question',
            'loai_cau_hoi' => 'tu_luan',
            'diem' => 10,
            'thu_tu' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phien_giam_sat')->insert([
            'lop_hoc_id' => $classId,
            'bai_tap_id' => $assignmentId,
            'nguoi_tao_id' => $teacherId,
            'ten_phien' => 'Protection session',
            'thoi_gian_bat_dau' => $now->copy()->subMinute(),
            'trang_thai' => 'dang_dien_ra',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$teacherId, $studentUserId, $studentId, $assignmentId];
    }

    private function createUser(int $roleId, string $email): int
    {
        return DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Protection User',
            'email' => $email,
            'password' => bcrypt('password'),
            'vai_tro_id' => $roleId,
            'trang_thai' => 'hoat_dong',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
