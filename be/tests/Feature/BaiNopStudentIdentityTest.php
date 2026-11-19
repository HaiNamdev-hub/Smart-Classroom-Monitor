<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use App\Models\SinhVien;
use App\Models\ThanhVienLop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BaiNopStudentIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_file_submission_uses_authenticated_user_id_for_membership_and_submission_foreign_keys(): void
    {
        Storage::fake('local');
        [$teacherId, $studentUserId, $studentProfileId, $classId, $assignmentId] = $this->fixture();
        $this->assertNotSame($studentUserId, $studentProfileId, 'Fixture must exercise distinct user and profile IDs.');
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        $this->postJson("/api/sinh-vien/bai-tap/{$assignmentId}/nop-bai", [
            'file' => UploadedFile::fake()->createWithContent('work.txt', 'Student submission body'),
        ])->assertCreated()
            ->assertJsonPath('data.sinh_vien_id', $studentUserId);

        $submission = DB::table('bai_nop')->where('bai_tap_id', $assignmentId)->first();
        $this->assertSame($studentUserId, (int) $submission->sinh_vien_id);
        $this->assertDatabaseHas('thanh_vien_lop', [
            'lop_hoc_id' => $classId,
            'sinh_vien_id' => $studentUserId,
        ]);
        Storage::disk('local')->assertExists($submission->duong_dan);

        $profile = SinhVien::query()->findOrFail($studentProfileId);
        $this->assertTrue($profile->thanhVienLops()->where('lop_hoc_id', $classId)->exists());
        $this->assertTrue($profile->baiNops()->where('bai_tap_id', $assignmentId)->exists());
        $this->assertSame($studentProfileId, (int) ThanhVienLop::query()
            ->where('lop_hoc_id', $classId)
            ->firstOrFail()
            ->sinhVien
            ->id);

        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));
        $this->getJson("/api/giang-vien/bai-tap/{$assignmentId}/bai-nop")
            ->assertOk()
            ->assertJsonPath('data.0.maSinhVien', 'IDENTITY-001')
            ->assertJsonPath('data.0.tenSinhVien', 'Identity Owner')
            ->assertJsonPath('data.0.nguoiDungId', $studentUserId)
            ->assertJsonPath('data.0.sinhVienProfileId', $studentProfileId);
    }

    public function test_invalid_file_type_mime_mismatch_and_oversized_upload_are_rejected(): void
    {
        Storage::fake('local');
        [, $studentUserId, , , $assignmentId] = $this->fixture();
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $url = "/api/sinh-vien/bai-tap/{$assignmentId}/nop-bai";

        $this->postJson($url, ['file' => UploadedFile::fake()->create('payload.php', 2, 'text/plain')])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson($url, ['file' => UploadedFile::fake()->createWithContent('fake.pdf', 'plain text payload')])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson($url, ['file' => UploadedFile::fake()->create('large.txt', 10241, 'text/plain')])
            ->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('bai_nop', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('bai-nop'));
    }

    public function test_submission_file_download_is_private_and_authorized(): void
    {
        Storage::fake('local');
        [$teacherId, $studentUserId, , , $assignmentId] = $this->fixture();
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $this->postJson("/api/sinh-vien/bai-tap/{$assignmentId}/nop-bai", [
            'file' => UploadedFile::fake()->createWithContent('work.txt', 'Student submission body'),
        ])->assertCreated();
        $submission = DB::table('bai_nop')->where('bai_tap_id', $assignmentId)->firstOrFail();
        $fileUrl = "/api/bai-nop/{$submission->id}/file";

        $this->get($fileUrl)->assertOk()->assertHeader('Content-Disposition');
        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));
        $this->get($fileUrl)->assertOk();
        $otherStudent = NguoiDung::query()->create([
            'ho_ten' => 'Other student', 'email' => 'other-download@example.test',
            'password' => 'password', 'vai_tro_id' => DB::table('vai_tro')->where('ten_vai_tro', 'sinh_vien')->value('id'),
            'trang_thai' => 'hoat_dong',
        ]);
        Sanctum::actingAs($otherStudent);
        $this->get($fileUrl)->assertNotFound();
        $this->get('/api/bai-nop/999999/file')->assertNotFound();

        DB::table('bai_nop')->where('id', $submission->id)->update(['duong_dan' => '../outside.txt']);
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $this->get($fileUrl)->assertNotFound();
    }

    public function test_online_exam_submission_and_grade_views_use_the_correct_student_id_type(): void
    {
        [$teacherId, $studentUserId, $studentProfileId, $classId, $assignmentId] = $this->fixture();
        $this->assertNotSame($studentUserId, $studentProfileId);

        $now = now();
        $questionId = DB::table('cau_hoi')->insertGetId([
            'bai_tap_id' => $assignmentId,
            'noi_dung' => 'Identity online test question',
            'loai_cau_hoi' => 'trac_nghiem',
            'diem' => 10,
            'thu_tu' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $correctAnswerId = DB::table('dap_an')->insertGetId([
            'cau_hoi_id' => $questionId, 'noi_dung' => 'Correct', 'la_dap_an_dung' => true,
            'thu_tu' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('dap_an')->insert([
            'cau_hoi_id' => $questionId, 'noi_dung' => 'Incorrect', 'la_dap_an_dung' => false,
            'thu_tu' => 2, 'created_at' => $now, 'updated_at' => $now,
        ]);

        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/bat-dau", [
            'fullscreen' => true,
            'cameraReady' => true,
        ])->assertCreated()->assertJsonPath('data.trangThaiLamBai', 'IN_PROGRESS');

        $this->postJson("/api/sinh-vien/bai-kiem-tra/{$assignmentId}/nop", [
            'answers' => [(string) $questionId => $correctAnswerId],
        ])->assertCreated()->assertJsonPath('data.daNop', true);

        $submission = DB::table('bai_nop')->where('bai_tap_id', $assignmentId)->firstOrFail();
        $this->assertSame($studentUserId, (int) $submission->sinh_vien_id);
        $this->assertDatabaseHas('bai_kiem_tra_luot_lam', [
            'bai_tap_id' => $assignmentId,
            'sinh_vien_id' => $studentProfileId,
        ]);
        $this->assertDatabaseHas('cau_tra_loi', [
            'bai_nop_id' => $submission->id,
            'cau_hoi_id' => $questionId,
            'dap_an_id' => $correctAnswerId,
        ]);
        $this->assertDatabaseHas('diem', ['bai_nop_id' => $submission->id, 'diem' => 10]);

        Sanctum::actingAs(NguoiDung::query()->findOrFail($teacherId));
        $this->getJson("/api/giang-vien/bai-tap/{$assignmentId}/bai-nop")
            ->assertOk()
            ->assertJsonPath('data.0.maSinhVien', 'IDENTITY-001')
            ->assertJsonPath('data.0.tenSinhVien', 'Identity Owner')
            ->assertJsonPath('data.0.diem', 10);
        $this->getJson("/api/giang-vien/diem?lopId={$classId}")
            ->assertOk()
            ->assertJsonPath('data.0.maSinhVien', 'IDENTITY-001')
            ->assertJsonPath('data.0.hoTen', 'Identity Owner');

        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));
        $this->getJson('/api/sinh-vien/diem')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.diem', '10.00');
    }

    private function fixture(): array
    {
        $now = now();
        $teacherRoleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'giang_vien', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $studentRoleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $teacherId = $this->user($teacherRoleId, 'teacher-identity@example.test');
        $studentUserId = $this->user($studentRoleId, 'student-identity@example.test', 'Identity Owner');
        $collisionUserId = $this->user($studentRoleId, 'collision-identity@example.test', 'Wrong Join Student');

        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => 'IDENTITY-FACULTY', 'ten_khoa' => 'Identity Faculty',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId, 'ma_nganh' => 'IDENTITY-MAJOR', 'ten_nganh' => 'Identity Major',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $studentProfileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $studentUserId, 'nganh_id' => $majorId,
            'ma_sinh_vien' => 'IDENTITY-001', 'nam_trung_tuyen' => (int) $now->format('Y'),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $collisionProfileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $collisionUserId, 'nganh_id' => $majorId,
            'ma_sinh_vien' => 'IDENTITY-002', 'nam_trung_tuyen' => (int) $now->format('Y'),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->assertSame($studentUserId, $collisionProfileId, 'Fixture must reproduce user-ID/profile-ID collision in the bad join.');
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'IDENTITY-CLASS', 'ten_lop' => 'Identity Class',
            'giang_vien_id' => $teacherId, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId, 'sinh_vien_id' => $studentUserId,
            'trang_thai' => 'dang_hoc', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId, 'sinh_vien_id' => $collisionUserId,
            'trang_thai' => 'dang_hoc', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $assignmentId = DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacherId,
            'tieu_de' => 'Identity Assignment', 'thoi_gian_bat_dau' => $now->copy()->subMinute(),
            'thoi_gian_ket_thuc' => $now->copy()->addDay(), 'han_nop' => $now->copy()->addDay(),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return [$teacherId, $studentUserId, $studentProfileId, $classId, $assignmentId];
    }

    private function user(int $roleId, string $email, string $name = 'Identity Test User'): int
    {
        return DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => $name, 'email' => $email, 'password' => bcrypt('password'),
            'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
