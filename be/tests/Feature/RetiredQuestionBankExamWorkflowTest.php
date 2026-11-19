<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RetiredQuestionBankExamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_bank_api_routes_are_removed(): void
    {
        $routes = [
            ['GET', '/api/giang-vien/ngan-hang-cau-hoi'],
            ['POST', '/api/giang-vien/ngan-hang-cau-hoi'],
            ['POST', '/api/giang-vien/ngan-hang-cau-hoi/import-preview'],
            ['POST', '/api/giang-vien/ngan-hang-cau-hoi/import-save'],
            ['GET', '/api/giang-vien/ngan-hang-cau-hoi/1'],
            ['PUT', '/api/giang-vien/ngan-hang-cau-hoi/1'],
            ['POST', '/api/giang-vien/ngan-hang-cau-hoi/1/duplicate'],
            ['PATCH', '/api/giang-vien/ngan-hang-cau-hoi/1/deactivate'],
        ];

        foreach ($routes as [$method, $url]) {
            $this->call($method, $url)->assertNotFound();
        }
    }

    public function test_phase_three_routes_are_removed(): void
    {
        $teacherId = $this->user('giang_vien', 'teacher');
        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));

        $this->postJson('/api/giang-vien/ngan-hang-de/availability', [])->assertNotFound();
        $this->postJson('/api/giang-vien/ngan-hang-de/preview', [])->assertNotFound();
        $this->postJson('/api/giang-vien/ngan-hang-de/confirm', [])->assertNotFound();
        $this->getJson('/api/giang-vien/ngan-hang-de/1/draft')->assertNotFound();
        $this->postJson('/api/giang-vien/ngan-hang-de/1/publish', [])->assertNotFound();
    }

    public function test_exam_list_and_start_work_without_phase_three_tables(): void
    {
        [$teacherId, $classId] = $this->teacherAndClass();
        $studentUserId = $this->user('sinh_vien', 'student');
        $studentProfileId = $this->studentProfile($studentUserId);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId,
            'sinh_vien_id' => $studentUserId,
            'trang_thai' => 'dang_hoc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $start = Carbon::now('Asia/Ho_Chi_Minh')->addMinute();
        $end = $start->copy()->addHour();
        $examId = DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId,
            'nguoi_tao_id' => $teacherId,
            'tieu_de' => 'Manual exam without bank metadata',
            'mo_ta' => null,
            'thoi_gian_bat_dau' => $start->format('Y-m-d H:i:s'),
            'thoi_gian_ket_thuc' => $end->format('Y-m-d H:i:s'),
            'han_nop' => $end->format('Y-m-d H:i:s'),
            'cho_phep_nop_tre' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('cau_hoi_ngan_hang_snapshot');
        Schema::dropIfExists('ngan_hang_de_preview');
        Schema::dropIfExists('ngan_hang_de_tao');
        Schema::dropIfExists('bai_tap_cau_hinh_tron');
        Schema::enableForeignKeyConstraints();

        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));
        $this->getJson('/api/giang-vien/bai-tap')
            ->assertOk()
            ->assertJsonPath('data.0.id', $examId)
            ->assertJsonPath('data.0.trangThaiCongBo', 'da_cong_bo');
        $this->getJson("/api/giang-vien/bai-tap/{$examId}/cau-hinh-tron")
            ->assertOk()
            ->assertJsonPath('data.tron_cau_hoi', false)
            ->assertJsonPath('data.tron_dap_an', false)
            ->assertJsonPath('data.locked', true);
        $this->putJson("/api/giang-vien/bai-tap/{$examId}/cau-hinh-tron", [
            'tron_cau_hoi' => true,
            'tron_dap_an' => true,
        ])->assertStatus(503);

        $this->postJson("/api/giang-vien/bai-tap/{$examId}/cau-hoi", [
            'noi_dung' => 'Manually created question',
            'loai_cau_hoi' => 'trac_nghiem',
            'diem' => 1,
            'dap_an' => ['Correct', 'Incorrect'],
            'dap_an_dung_index' => 0,
        ])->assertCreated();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['loai_cau_hoi', 'noi_dung', 'a', 'b', 'dap_an_dung', 'diem'],
            ['trac_nghiem', 'Imported legacy question', 'Option A', 'Option B', 'A', 1],
        ]);
        $tempPath = tempnam(sys_get_temp_dir(), 'assignment-import-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $upload = new UploadedFile(
            $tempPath,
            'questions.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
        try {
            $preview = $this->postJson("/api/giang-vien/bai-tap/{$examId}/cau-hoi/import-preview", ['file' => $upload])
                ->assertOk()->assertJsonPath('data.0.noi_dung', 'Imported legacy question');
            $this->postJson("/api/giang-vien/bai-tap/{$examId}/cau-hoi/import-save", [
                'questions' => $preview->json('data'),
            ])->assertCreated()->assertJsonPath('data.count', 1);
        } finally {
            @unlink($tempPath);
            $spreadsheet->disconnectWorksheets();
        }
        $this->assertDatabaseCount('cau_hoi', 2);

        Sanctum::actingAs(NguoiDung::findOrFail($studentUserId));
        $this->getJson('/api/sinh-vien/bai-tap')
            ->assertOk()
            ->assertJsonPath('status', true);

        Carbon::setTestNow($start);
        try {
            $this->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", [
                'fullscreen' => true,
                'cameraReady' => true,
            ])->assertCreated();
            $this->assertDatabaseHas('bai_kiem_tra_luot_lam', [
                'bai_tap_id' => $examId,
                'sinh_vien_id' => $studentProfileId,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_legacy_manual_exam_import_still_previews_docx(): void
    {
        [$teacherId, $classId] = $this->teacherAndClass();
        $start = Carbon::now('Asia/Ho_Chi_Minh')->addHour();
        $examId = DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId,
            'nguoi_tao_id' => $teacherId,
            'tieu_de' => 'DOCX import test',
            'mo_ta' => null,
            'thoi_gian_bat_dau' => $start->format('Y-m-d H:i:s'),
            'thoi_gian_ket_thuc' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
            'han_nop' => $start->copy()->addHour()->format('Y-m-d H:i:s'),
            'cho_phep_nop_tre' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));

        $word = new PhpWord();
        $section = $word->addSection();
        foreach ([
            'Câu 1: DOCX imported question',
            'A. First answer',
            'B. Second answer',
            'Đáp án: A',
            'Điểm: 1',
        ] as $line) {
            $section->addText($line);
        }
        $path = tempnam(sys_get_temp_dir(), 'exam-import-') . '.docx';
        WordFactory::createWriter($word, 'Word2007')->save($path);
        $upload = new UploadedFile(
            $path,
            'questions.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        try {
            $this->postJson("/api/giang-vien/bai-tap/{$examId}/cau-hoi/import-preview", ['file' => $upload])
                ->assertOk()
                ->assertJsonPath('data.0.noi_dung', 'DOCX imported question')
                ->assertJsonPath('data.0.dap_an_dung_index', 0);
        } finally {
            @unlink($path);
        }
    }

    private function teacherAndClass(): array
    {
        $teacherId = $this->user('giang_vien', 'teacher');
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => uniqid('C'),
            'ten_lop' => 'Compatibility class',
            'giang_vien_id' => $teacherId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [(int) $teacherId, (int) $classId];
    }

    private function user(string $role, string $prefix): int
    {
        $roleId = DB::table('vai_tro')->where('ten_vai_tro', $role)->value('id');
        $roleId ??= DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => ucfirst($prefix),
            'email' => uniqid($prefix . '-') . '@example.test',
            'password' => bcrypt('password'),
            'vai_tro_id' => $roleId,
            'trang_thai' => 'hoat_dong',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function studentProfile(int $userId): int
    {
        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => uniqid('F'),
            'ten_khoa' => 'Faculty',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId,
            'ma_nganh' => uniqid('M'),
            'ten_nganh' => 'Major',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $userId,
            'nganh_id' => $majorId,
            'ma_sinh_vien' => uniqid('SV'),
            'nam_trung_tuyen' => (int) now()->format('Y'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
