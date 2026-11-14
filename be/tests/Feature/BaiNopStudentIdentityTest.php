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
        Storage::fake('public');
        [$teacherId, $studentUserId, $studentProfileId, $classId, $assignmentId] = $this->fixture();
        $this->assertNotSame($studentUserId, $studentProfileId, 'Fixture must exercise distinct user and profile IDs.');
        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        $this->post("/api/sinh-vien/bai-tap/{$assignmentId}/nop-bai", [
            'file' => UploadedFile::fake()->create('work.txt', 2, 'text/plain'),
        ])->assertCreated()
            ->assertJsonPath('data.sinh_vien_id', $studentUserId);

        $submission = DB::table('bai_nop')->where('bai_tap_id', $assignmentId)->first();
        $this->assertSame($studentUserId, (int) $submission->sinh_vien_id);
        $this->assertDatabaseHas('thanh_vien_lop', [
            'lop_hoc_id' => $classId,
            'sinh_vien_id' => $studentUserId,
        ]);
        Storage::disk('public')->assertExists($submission->duong_dan);

        $profile = SinhVien::query()->findOrFail($studentProfileId);
        $this->assertTrue($profile->thanhVienLops()->where('lop_hoc_id', $classId)->exists());
        $this->assertTrue($profile->baiNops()->where('bai_tap_id', $assignmentId)->exists());
        $this->assertSame($studentProfileId, (int) ThanhVienLop::query()
            ->where('lop_hoc_id', $classId)
            ->firstOrFail()
            ->sinhVien
            ->id);
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
        $studentUserId = $this->user($studentRoleId, 'student-identity@example.test');

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
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'IDENTITY-CLASS', 'ten_lop' => 'Identity Class',
            'giang_vien_id' => $teacherId, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId, 'sinh_vien_id' => $studentUserId,
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

    private function user(int $roleId, string $email): int
    {
        return DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Identity Test User', 'email' => $email, 'password' => bcrypt('password'),
            'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
