<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAssignmentIdentityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_with_distinct_user_and_profile_ids_can_complete_assigned_exam_without_cross_student_data(): void
    {
        [$teacherId, $studentUserId, $studentProfileId, $classId, $examId, $questionId, $correctAnswerId] = $this->fixture();

        $this->assertSame(1, $teacherId);
        $this->assertSame(2, $studentUserId);
        $this->assertSame(1, $studentProfileId);
        $this->assertNotSame($studentUserId, $studentProfileId);
        $this->assertDatabaseHas('thanh_vien_lop', [
            'lop_hoc_id' => $classId,
            'sinh_vien_id' => $studentUserId,
        ]);

        $login = $this->postJson('/api/dang-nhap', [
            'email' => 'identity-student@example.test',
            'password' => 'test-password',
        ])->assertOk()->assertJsonPath('nguoi_dung.id', $studentUserId);
        $token = $login->json('access_token');
        $this->assertNotEmpty($token);

        $this->withToken($token)->getJson('/api/sinh-vien/lop-hoc')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $classId);

        $this->withToken($token)->getJson('/api/sinh-vien/bai-tap')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $examId)
            ->assertJsonPath('data.0.da_nop', false);

        $start = $this->withToken($token)->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", [
            'fullscreen' => true,
            'cameraReady' => true,
        ]);
        $this->assertSame(201, $start->status(), $start->getContent());
        $start
            ->assertJsonPath('data.trangThaiLamBai', 'IN_PROGRESS');

        $this->withToken($token)->getJson("/api/sinh-vien/bai-kiem-tra/{$examId}")
            ->assertOk()
            ->assertJsonPath('data.trangThaiLamBai', 'IN_PROGRESS')
            ->assertJsonPath('data.questions.0.id', $questionId)
            ->assertJsonMissingPath('data.questions.0.dap_an.0.la_dap_an_dung');

        $save = $this->withToken($token)->putJson("/api/sinh-vien/bai-kiem-tra/{$examId}/nhap", [
            'answers' => [(string) $questionId => $correctAnswerId],
            'baseDraftRevision' => null,
        ])->assertOk()->assertJsonPath('data.answers.' . $questionId, $correctAnswerId);
        $revision = $save->json('data.draftRevision');
        $this->assertSame(64, strlen($revision));

        $this->withToken($token)->getJson("/api/sinh-vien/bai-kiem-tra/{$examId}")
            ->assertOk()
            ->assertJsonPath('data.answers.' . $questionId, $correctAnswerId)
            ->assertJsonPath('data.draftRevision', $revision);

        $this->withToken($token)->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/nop", [
            'answers' => [(string) $questionId => $correctAnswerId],
        ])->assertCreated()->assertJsonPath('data.daNop', true);

        $submission = DB::table('bai_nop')->where('bai_tap_id', $examId)->sole();
        $this->assertSame($studentUserId, (int) $submission->sinh_vien_id);
        $this->withToken($token)->getJson('/api/sinh-vien/bai-tap')
            ->assertOk()
            ->assertJsonPath('data.0.da_nop', true)
            ->assertJsonPath('data.0.bai_nop.id', (int) $submission->id);
        $this->assertDatabaseHas('bai_kiem_tra_luot_lam', [
            'bai_tap_id' => $examId,
            'sinh_vien_id' => $studentProfileId,
        ]);
        $this->assertDatabaseHas('cau_tra_loi', [
            'bai_nop_id' => $submission->id,
            'cau_hoi_id' => $questionId,
            'dap_an_id' => $correctAnswerId,
        ]);
        $this->assertDatabaseHas('diem', [
            'bai_nop_id' => $submission->id,
            'diem' => 10,
        ]);
        $this->assertDatabaseMissing('bai_lam_nhap', [
            'bai_tap_id' => $examId,
            'sinh_vien_id' => $studentProfileId,
        ]);

    }

    public function test_student_outside_class_cannot_list_or_open_an_exam(): void
    {
        [, , , , $examId] = $this->fixture();
        [$outsideUserId] = $this->createStudent('outside-class@example.test', 'Outside Student');
        Sanctum::actingAs(NguoiDung::query()->findOrFail($outsideUserId));

        $this->getJson('/api/sinh-vien/lop-hoc')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/sinh-vien/bai-tap')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/sinh-vien/bai-kiem-tra/{$examId}")
            ->assertNotFound();
    }

    public function test_assignment_list_remains_compatible_when_user_and_profile_ids_are_equal(): void
    {
        $studentRole = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $teacherRole = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'giang_vien', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $studentUserId = $this->createUser($studentRole, 'equal-id-student@example.test', 'Equal ID Student');
        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => 'EQUAL-ID-FACULTY', 'ten_khoa' => 'Equal ID Faculty',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId, 'ma_nganh' => 'EQUAL-ID-MAJOR', 'ten_nganh' => 'Equal ID Major',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $profileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $studentUserId, 'nganh_id' => $majorId,
            'ma_sinh_vien' => 'EQUAL-001', 'nam_trung_tuyen' => (int) now()->format('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $teacherId = $this->createUser($teacherRole, 'equal-id-teacher@example.test', 'Equal ID Teacher');
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'EQUAL-ID-CLASS', 'ten_lop' => 'Equal ID Class', 'giang_vien_id' => $teacherId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId, 'sinh_vien_id' => $studentUserId,
            'trang_thai' => 'dang_hoc', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $examId = $this->createExam($classId, $teacherId);

        $this->assertSame($studentUserId, $profileId);
        $login = $this->postJson('/api/dang-nhap', [
            'email' => 'equal-id-student@example.test', 'password' => 'test-password',
        ])->assertOk();
        $this->withToken($login->json('access_token'))->getJson('/api/sinh-vien/bai-tap')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $examId);
    }

    private function fixture(): array
    {
        $studentRole = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $teacherRole = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'giang_vien', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $teacherId = $this->createUser($teacherRole, 'identity-teacher@example.test', 'Identity Teacher');
        $studentUserId = $this->createUser($studentRole, 'identity-student@example.test', 'Identity Student');
        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => 'IDENTITY-PERF-FACULTY', 'ten_khoa' => 'Identity Faculty',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId, 'ma_nganh' => 'IDENTITY-PERF-MAJOR', 'ten_nganh' => 'Identity Major',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $profileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $studentUserId, 'nganh_id' => $majorId,
            'ma_sinh_vien' => 'IDENTITY-PERF-001', 'nam_trung_tuyen' => (int) now()->format('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'IDENTITY-PERF-CLASS', 'ten_lop' => 'Identity Perf Class', 'giang_vien_id' => $teacherId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId, 'sinh_vien_id' => $studentUserId,
            'trang_thai' => 'dang_hoc', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $examId = $this->createExam($classId, $teacherId);
        $questionId = DB::table('cau_hoi')->insertGetId([
            'bai_tap_id' => $examId, 'noi_dung' => 'Identity test question',
            'loai_cau_hoi' => 'trac_nghiem', 'diem' => 10, 'thu_tu' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $correctAnswerId = DB::table('dap_an')->insertGetId([
            'cau_hoi_id' => $questionId, 'noi_dung' => 'Correct answer', 'la_dap_an_dung' => true,
            'thu_tu' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('dap_an')->insert([
            'cau_hoi_id' => $questionId, 'noi_dung' => 'Wrong answer', 'la_dap_an_dung' => false,
            'thu_tu' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$teacherId, $studentUserId, $profileId, $classId, $examId, $questionId, $correctAnswerId];
    }

    private function createStudent(string $email, string $name): array
    {
        $roleId = DB::table('vai_tro')->where('ten_vai_tro', 'sinh_vien')->value('id');
        $userId = $this->createUser((int) $roleId, $email, $name);
        $majorId = DB::table('nganh')->value('id');
        $profileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $userId, 'nganh_id' => $majorId,
            'ma_sinh_vien' => 'OUTSIDE-' . $userId, 'nam_trung_tuyen' => (int) now()->format('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$userId, $profileId];
    }

    private function createUser(int $roleId, string $email, string $name): int
    {
        return DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => $name, 'email' => $email, 'password' => bcrypt('test-password'),
            'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createExam(int $classId, int $teacherId): int
    {
        $nowVN = Carbon::now('Asia/Ho_Chi_Minh');

        return DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacherId,
            'tieu_de' => 'Identity Exam', 'mo_ta' => 'Regression fixture',
            'thoi_gian_bat_dau' => $nowVN->copy()->subMinute(),
            'thoi_gian_ket_thuc' => $nowVN->copy()->addHours(2),
            'han_nop' => $nowVN->copy()->addHours(2), 'cho_phep_nop_tre' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
