<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExamModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_configure_both_modes_and_reads_the_camera_policy(): void
    {
        [$teacher, , $examId] = $this->examFixture();

        $this->actingAs($teacher, 'sanctum')->putJson($this->modeUrl($examId), [
            'hinh_thuc' => 'LAB_NO_CAMERA',
        ])->assertOk()->assertJsonPath('data.yeu_cau_camera', false)
            ->assertJsonPath('data.co_the_chinh_sua', true);

        $this->actingAs($teacher, 'sanctum')->getJson($this->modeUrl($examId))
            ->assertOk()->assertJsonPath('data.hinh_thuc', 'LAB_NO_CAMERA')
            ->assertJsonPath('data.ten_hinh_thuc', 'Thi tại phòng máy — Không yêu cầu camera');

        $this->actingAs($teacher, 'sanctum')->putJson($this->modeUrl($examId), [
            'hinh_thuc' => 'ONLINE_CAMERA_REQUIRED',
        ])->assertOk()->assertJsonPath('data.yeu_cau_camera', true);
    }

    public function test_students_and_other_teachers_cannot_manage_exam_mode(): void
    {
        [$teacher, $student, $examId] = $this->examFixture();
        $otherTeacher = $this->user('giang_vien', 'other-mode@example.test');

        $this->actingAs($student, 'sanctum')->putJson($this->modeUrl($examId), ['hinh_thuc' => 'LAB_NO_CAMERA'])
            ->assertForbidden();
        $this->actingAs($otherTeacher, 'sanctum')->getJson($this->modeUrl($examId))->assertNotFound();
        $this->actingAs($otherTeacher, 'sanctum')->putJson($this->modeUrl($examId), ['hinh_thuc' => 'LAB_NO_CAMERA'])
            ->assertNotFound();
        $this->actingAs($teacher, 'sanctum')->putJson($this->modeUrl($examId), ['hinh_thuc' => 'OPTIONAL_CAMERA'])
            ->assertUnprocessable();
    }

    public function test_legacy_exam_defaults_to_camera_required_and_attempt_locks_mode(): void
    {
        [$teacher, , $examId, $studentProfileId] = $this->examFixture();
        $this->actingAs($teacher, 'sanctum')->getJson($this->modeUrl($examId))
            ->assertOk()->assertJsonPath('data.hinh_thuc', 'ONLINE_CAMERA_REQUIRED')
            ->assertJsonPath('data.yeu_cau_camera', true);

        DB::table('bai_kiem_tra_luot_lam')->insert([
            'bai_tap_id' => $examId, 'sinh_vien_id' => $studentProfileId,
            'bat_dau_luc' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($teacher, 'sanctum')->putJson($this->modeUrl($examId), ['hinh_thuc' => 'LAB_NO_CAMERA'])
            ->assertStatus(409);
    }

    public function test_lab_mode_starts_without_camera_but_keeps_tab_protection_and_rejects_camera_alerts(): void
    {
        [, $student, $examId, $studentProfileId] = $this->examFixture();
        $this->actingAs($this->teacherForExam($examId), 'sanctum')->putJson($this->modeUrl($examId), ['hinh_thuc' => 'LAB_NO_CAMERA'])->assertOk();
        DB::table('bai_tap')->where('id', $examId)->update(['thoi_gian_bat_dau' => now('Asia/Ho_Chi_Minh')->subMinute(), 'thoi_gian_ket_thuc' => now('Asia/Ho_Chi_Minh')->addHour(), 'han_nop' => now('Asia/Ho_Chi_Minh')->addHour()]);

        $startResponse = $this->actingAs($student, 'sanctum')->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", [
            'fullscreen' => true,
        ]);
        $startResponse->assertCreated()->assertJsonPath('data.yeuCauCamera', false);

        $eventId = (string) Str::uuid();
        $this->actingAs($student, 'sanctum')->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/roi-tab", [
            'eventId' => $eventId,
        ])->assertOk();
        $this->assertDatabaseHas('vi_pham_kiem_tra', ['event_id' => $eventId, 'sinh_vien_id' => $studentProfileId]);

        $this->actingAs($student, 'sanctum')->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/canh-bao-camera", [
            'eventId' => (string) Str::uuid(), 'loaiCanhBao' => 'khong_co_khuon_mat',
        ])->assertStatus(409);

        $this->actingAs($student, 'sanctum')->getJson("/api/sinh-vien/bai-kiem-tra/{$examId}/trang-thai")
            ->assertOk()->assertJsonPath('data.biKhoa', false)
            ->assertJsonPath('data.hinhThucKiemTra', 'LAB_NO_CAMERA')
            ->assertJsonPath('data.yeuCauCamera', false);
    }

    public function test_online_mode_requires_camera_readiness_before_start(): void
    {
        [, $student, $examId] = $this->examFixture();
        $this->actingAs($student, 'sanctum')->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", [
            'fullscreen' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('cameraReady');

        DB::table('bai_tap')->where('id', $examId)->update(['thoi_gian_bat_dau' => now('Asia/Ho_Chi_Minh')->subMinute(), 'thoi_gian_ket_thuc' => now('Asia/Ho_Chi_Minh')->addHour(), 'han_nop' => now('Asia/Ho_Chi_Minh')->addHour()]);
        $this->actingAs($student, 'sanctum')->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", [
            'fullscreen' => true, 'cameraReady' => true,
        ])->assertCreated()->assertJsonPath('data.yeuCauCamera', true);
    }

    private function examFixture(): array
    {
        $teacher = $this->user('giang_vien', 'mode-teacher@example.test');
        $student = $this->user('sinh_vien', 'mode-student@example.test');
        DB::table('khoa')->insert(['ma_khoa' => 'MODE', 'ten_khoa' => 'Khoa kiểm tra', 'created_at' => now(), 'updated_at' => now()]);
        $departmentId = DB::table('nganh')->insertGetId(['khoa_id' => 1, 'ma_nganh' => 'MODE', 'ten_nganh' => 'Ngành kiểm tra', 'created_at' => now(), 'updated_at' => now()]);
        $studentProfileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $student->id, 'nganh_id' => $departmentId,
            'ma_sinh_vien' => 'MODE0001', 'nam_trung_tuyen' => 2025,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'MODE01', 'ten_lop' => 'Lớp kiểm tra mode', 'giang_vien_id' => $teacher->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId, 'sinh_vien_id' => $student->id, 'trang_thai' => 'dang_hoc',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $examId = DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacher->id, 'tieu_de' => 'Bài kiểm tra mode',
            'thoi_gian_bat_dau' => now('Asia/Ho_Chi_Minh')->addMinutes(5), 'thoi_gian_ket_thuc' => now('Asia/Ho_Chi_Minh')->addHour(),
            'han_nop' => now('Asia/Ho_Chi_Minh')->addHour(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $questionId = DB::table('cau_hoi')->insertGetId([
            'bai_tap_id' => $examId, 'noi_dung' => 'Câu hỏi kiểm thử', 'loai_cau_hoi' => 'trac_nghiem',
            'diem' => 10, 'thu_tu' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (range(1, 4) as $order) {
            DB::table('dap_an')->insert([
                'cau_hoi_id' => $questionId, 'noi_dung' => "Phương án {$order}", 'la_dap_an_dung' => $order === 1,
                'thu_tu' => $order, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return [$teacher, $student, $examId, $studentProfileId];
    }

    private function teacherForExam(int $examId): NguoiDung
    {
        $teacherId = DB::table('bai_tap')->where('id', $examId)->value('nguoi_tao_id');
        return NguoiDung::findOrFail($teacherId);
    }

    private function user(string $role, string $email): NguoiDung
    {
        $roleId = DB::table('vai_tro')->where('ten_vai_tro', $role)->value('id')
            ?? DB::table('vai_tro')->insertGetId(['ten_vai_tro' => $role, 'created_at' => now(), 'updated_at' => now()]);
        return NguoiDung::query()->create([
            'ho_ten' => $email, 'email' => $email, 'password' => 'password',
            'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong',
        ]);
    }

    private function modeUrl(int $examId): string
    {
        return "/api/giang-vien/bai-tap/{$examId}/hinh-thuc-kiem-tra";
    }
}
