<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_alert_evidence_upload_is_private_and_idempotent(): void
    {
        Storage::fake('local');
        [$teacherId, $studentUserId, $studentId, $assignmentId, $alertId, $eventId] = $this->fixture('phat_hien_dien_thoai');

        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $path = "/api/sinh-vien/bai-kiem-tra/{$assignmentId}/canh-bao-camera/{$eventId}/anh";
        $payload = ['eventId' => $eventId, 'image' => UploadedFile::fake()->image('evidence.jpg', 640, 480)];

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/canh-bao-camera", [
            'eventId' => $eventId,
            'loaiCanhBao' => 'phat_hien_dien_thoai',
        ])->assertOk()->assertJsonPath('data.eventAccepted', true);
        $this->assertSame(1, DB::table('canh_bao_camera')->where('sinh_vien_id', $studentId)->count());

        $this->postJson($path, $payload)->assertCreated()
            ->assertJsonPath('data.evidenceStatus', 'available');
        $this->assertSame(1, DB::table('anh_man_hinh')->where('event_id', $eventId)->count());
        $this->assertSame(1, DB::table('canh_bao_camera')->where('id', $alertId)->count());
        $this->assertSame('available', DB::table('canh_bao_camera')->where('id', $alertId)->value('evidence_status'));

        $this->postJson($path, $payload)->assertOk()
            ->assertJsonPath('data.evidenceStatus', 'available');
        $this->assertSame(1, DB::table('anh_man_hinh')->where('event_id', $eventId)->count());

        $imageId = (int) DB::table('anh_man_hinh')->where('event_id', $eventId)->value('id');
        $image = DB::table('anh_man_hinh')->where('id', $imageId)->first();
        $imageBytes = Storage::disk('local')->get($image->duong_dan);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));
        $this->get("/api/giang-vien/anh-man-hinh/{$imageId}/file")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Content-Length', (string) strlen($imageBytes))
            ->assertContent($imageBytes);

        $teacherRoleId = (int) DB::table('vai_tro')->where('ten_vai_tro', 'giang_vien')->value('id');
        $otherTeacherId = $this->createUser($teacherRoleId, 'other-teacher@example.test');
        Sanctum::actingAs(NguoiDung::query()->findOrFail($otherTeacherId));
        $this->get("/api/giang-vien/anh-man-hinh/{$imageId}/file")->assertNotFound();
    }

    public function test_camera_disconnect_alert_cannot_accept_a_stale_image(): void
    {
        Storage::fake('local');
        [, $studentUserId, , $assignmentId, , $eventId] = $this->fixture('camera_ngat');
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/canh-bao-camera/{$eventId}/anh", [
            'eventId' => $eventId,
            'image' => UploadedFile::fake()->image('stale.jpg'),
        ])->assertStatus(422);
        $this->assertSame(0, DB::table('anh_man_hinh')->where('event_id', $eventId)->count());
    }

    public function test_failed_image_upload_changes_only_evidence_status(): void
    {
        [, $studentUserId, $studentId, $assignmentId, $alertId, $eventId] = $this->fixture('nhieu_khuon_mat');
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/canh-bao-camera/{$eventId}/anh-that-bai", [
            'eventId' => $eventId,
        ])->assertOk()->assertJsonPath('data.evidenceStatus', 'failed');

        $this->assertSame(1, DB::table('canh_bao_camera')->where('sinh_vien_id', $studentId)->count());
        $this->assertSame('failed', DB::table('canh_bao_camera')->where('id', $alertId)->value('evidence_status'));
        $this->assertSame(0, DB::table('anh_man_hinh')->where('event_id', $eventId)->count());
    }

    public function test_teacher_image_list_returns_empty_or_session_images(): void
    {
        Storage::fake('local');
        [$teacherId, $studentUserId, , $assignmentId, , $eventId] = $this->fixture('nhieu_khuon_mat');
        $now = now();
        $classId = DB::table('bai_tap')->where('id', $assignmentId)->value('lop_hoc_id');
        $sessionId = DB::table('phien_giam_sat')->insertGetId([
            'lop_hoc_id' => $classId,
            'bai_tap_id' => $assignmentId,
            'nguoi_tao_id' => $teacherId,
            'ten_phien' => 'Evidence gallery session',
            'thoi_gian_bat_dau' => $now->copy()->subMinute(),
            'trang_thai' => 'dang_dien_ra',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));
        $url = "/api/giang-vien/anh-man-hinh?phienId={$sessionId}";
        $this->getJson($url)->assertOk()->assertJsonPath('data', []);

        $fileBytes = 'test image bytes';
        Storage::disk('local')->put("exam-evidence/{$eventId}.jpg", $fileBytes);
        $imageId = DB::table('anh_man_hinh')->insertGetId([
            'phien_id' => $sessionId,
            'sinh_vien_id' => $studentUserId,
            'bai_tap_id' => $assignmentId,
            'event_id' => $eventId,
            'nguon' => 'webcam_ai',
            'loai_canh_bao' => 'nhieu_khuon_mat',
            'duong_dan' => "exam-evidence/{$eventId}.jpg",
            'storage_disk' => 'local',
            'mime_type' => 'image/jpeg',
            'kich_thuoc_bytes' => strlen($fileBytes),
            'thoi_gian_chup' => $now,
            'expires_at' => $now->copy()->addDays(90),
            'created_at' => $now,
        ]);

        $this->getJson($url)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.eventId', $eventId)
            ->assertJsonPath('data.0.imageAvailable', true);

        $this->get("/api/giang-vien/anh-man-hinh/{$imageId}/file")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Content-Length', (string) strlen($fileBytes))
            ->assertContent($fileBytes);
    }

    private function fixture(string $alertType): array
    {
        $now = now();
        $studentRoleId = DB::table('vai_tro')->insertGetId(['ten_vai_tro' => 'sinh_vien', 'created_at' => $now, 'updated_at' => $now]);
        $teacherRoleId = DB::table('vai_tro')->insertGetId(['ten_vai_tro' => 'giang_vien', 'created_at' => $now, 'updated_at' => $now]);
        $teacherId = $this->createUser($teacherRoleId, 'teacher@example.test');
        $studentUserId = $this->createUser($studentRoleId, 'student@example.test');
        $facultyId = DB::table('khoa')->insertGetId(['ma_khoa' => uniqid('F'), 'ten_khoa' => 'Faculty', 'created_at' => $now, 'updated_at' => $now]);
        $majorId = DB::table('nganh')->insertGetId(['khoa_id' => $facultyId, 'ma_nganh' => uniqid('M'), 'ten_nganh' => 'Major', 'created_at' => $now, 'updated_at' => $now]);
        $studentId = DB::table('sinh_vien')->insertGetId(['nguoi_dung_id' => $studentUserId, 'nganh_id' => $majorId, 'ma_sinh_vien' => uniqid('S'), 'nam_trung_tuyen' => (int) $now->format('Y'), 'created_at' => $now, 'updated_at' => $now]);
        $classId = DB::table('lop_hoc')->insertGetId(['ma_lop' => uniqid('C'), 'ten_lop' => 'Class', 'giang_vien_id' => $teacherId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('thanh_vien_lop')->insert(['lop_hoc_id' => $classId, 'sinh_vien_id' => $studentUserId, 'trang_thai' => 'dang_hoc', 'created_at' => $now, 'updated_at' => $now]);
        $assignmentId = DB::table('bai_tap')->insertGetId(['lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacherId, 'tieu_de' => 'Exam', 'thoi_gian_bat_dau' => $now->copy()->subHour(), 'thoi_gian_ket_thuc' => $now->copy()->addHour(), 'han_nop' => $now->copy()->addHour(), 'created_at' => $now, 'updated_at' => $now]);
        $eventId = (string) \Illuminate\Support\Str::uuid();
        $alertId = DB::table('canh_bao_camera')->insertGetId(['bai_tap_id' => $assignmentId, 'sinh_vien_id' => $studentId, 'event_id' => $eventId, 'loai_canh_bao' => $alertType, 'evidence_status' => $alertType === 'camera_ngat' ? 'not_applicable' : 'pending', 'created_at' => $now, 'updated_at' => $now]);

        return [$teacherId, $studentUserId, $studentId, $assignmentId, $alertId, $eventId];
    }

    private function createUser(int $roleId, string $email): int
    {
        return DB::table('nguoi_dung')->insertGetId(['ho_ten' => 'Evidence User', 'email' => $email, 'password' => bcrypt('password'), 'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong', 'created_at' => now(), 'updated_at' => now()]);
    }
}
