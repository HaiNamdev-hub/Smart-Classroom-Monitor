<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_filter_duplicate_and_deactivate_owned_question(): void
    {
        [$teacherId] = $this->teacher();
        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));

        $payload = $this->multipleChoicePayload();
        $payload['nguoi_tao_id'] = 999999;
        $created = $this->postJson('/api/giang-vien/ngan-hang-cau-hoi', $payload)
            ->assertCreated()
            ->assertJsonPath('data.loai_cau_hoi', 'trac_nghiem')
            ->assertJsonPath('data.dap_an.0.la_dap_an_dung', true);
        $id = $created->json('data.id');
        $this->assertDatabaseHas('ngan_hang_cau_hoi', ['id' => $id, 'nguoi_tao_id' => $teacherId]);

        $this->getJson('/api/giang-vien/ngan-hang-cau-hoi?chu_de=Đại số&do_kho=de')
            ->assertOk()->assertJsonPath('meta.total', 1);

        $updated = $this->multipleChoicePayload();
        $updated['noi_dung'] = 'Updated question';
        $this->putJson("/api/giang-vien/ngan-hang-cau-hoi/{$id}", $updated)
            ->assertOk()->assertJsonPath('data.noi_dung', 'Updated question')->assertJsonPath('data.version', 2);

        $this->postJson("/api/giang-vien/ngan-hang-cau-hoi/{$id}/duplicate")
            ->assertCreated()->assertJsonPath('data.trang_thai', 'active');
        $this->assertDatabaseCount('ngan_hang_cau_hoi', 2);
        $this->assertDatabaseCount('ngan_hang_dap_an', 4);

        $this->patchJson("/api/giang-vien/ngan-hang-cau-hoi/{$id}/deactivate")
            ->assertOk();
        $this->assertDatabaseHas('ngan_hang_cau_hoi', ['id' => $id, 'trang_thai' => 'inactive']);
    }

    public function test_teacher_cannot_read_or_modify_another_teachers_question(): void
    {
        [$ownerId] = $this->teacher();
        $questionId = $this->insertQuestion($ownerId);
        [$otherTeacherId] = $this->teacher();
        Sanctum::actingAs(NguoiDung::findOrFail($otherTeacherId));

        $this->getJson("/api/giang-vien/ngan-hang-cau-hoi/{$questionId}")->assertNotFound();
        $this->putJson("/api/giang-vien/ngan-hang-cau-hoi/{$questionId}", $this->multipleChoicePayload())->assertNotFound();
        $this->postJson("/api/giang-vien/ngan-hang-cau-hoi/{$questionId}/duplicate")->assertNotFound();
        $this->patchJson("/api/giang-vien/ngan-hang-cau-hoi/{$questionId}/deactivate")->assertNotFound();
    }

    public function test_student_role_cannot_access_question_bank(): void
    {
        $roleId = DB::table('vai_tro')->insertGetId(['ten_vai_tro' => 'sinh_vien', 'created_at' => now(), 'updated_at' => now()]);
        $userId = $this->insertUser($roleId, 'student');
        Sanctum::actingAs(NguoiDung::findOrFail($userId));
        $this->getJson('/api/giang-vien/ngan-hang-cau-hoi')->assertForbidden();
    }

    public function test_question_validation_enforces_one_correct_option_and_essay_metadata(): void
    {
        [$teacherId] = $this->teacher();
        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));

        $payload = $this->multipleChoicePayload();
        $payload['dap_an_dung_index'] = 7;
        $this->postJson('/api/giang-vien/ngan-hang-cau-hoi', $payload)->assertUnprocessable();

        $this->postJson('/api/giang-vien/ngan-hang-cau-hoi', [
            'noi_dung' => 'Essay question', 'loai_cau_hoi' => 'tu_luan', 'do_kho' => 'kho',
            'diem_tu_luan' => 2.333, 'dap_an_mau' => 'Rubric',
        ])->assertUnprocessable();

        $this->postJson('/api/giang-vien/ngan-hang-cau-hoi', [
            'noi_dung' => 'Essay question', 'loai_cau_hoi' => 'tu_luan', 'do_kho' => 'kho',
            'diem_tu_luan' => 2.25, 'dap_an_mau' => 'Rubric',
        ])->assertCreated()->assertJsonPath('data.diem_tu_luan', '2.25');
    }

    public function test_bank_import_preview_reuses_existing_xlsx_parser_and_save_is_atomic(): void
    {
        [$teacherId] = $this->teacher();
        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['loai_cau_hoi', 'noi_dung', 'a', 'b', 'dap_an_dung', 'diem'],
            ['trac_nghiem', 'Imported question', 'First', 'Second', 'B', 1],
        ]);
        $tempPath = tempnam(sys_get_temp_dir(), 'bank-import-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $upload = new UploadedFile($tempPath, 'questions.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        try {
            $preview = $this->postJson('/api/giang-vien/ngan-hang-cau-hoi/import-preview', ['file' => $upload])
                ->assertOk()->assertJsonPath('data.0.noi_dung', 'Imported question');
            $rows = $preview->json('data');
            $this->postJson('/api/giang-vien/ngan-hang-cau-hoi/import-save', [
                'questions' => $rows, 'chu_de' => 'Imported', 'do_kho' => 'trung_binh',
            ])->assertCreated()->assertJsonPath('data.count', 1);
            $this->assertDatabaseHas('ngan_hang_cau_hoi', ['nguoi_tao_id' => $teacherId, 'noi_dung' => 'Imported question']);
            $this->assertDatabaseHas('ngan_hang_dap_an', ['noi_dung' => 'Second', 'la_dap_an_dung' => true]);
        } finally {
            @unlink($tempPath);
            $spreadsheet->disconnectWorksheets();
        }
    }

    public function test_invalid_import_does_not_save_any_questions(): void
    {
        [$teacherId] = $this->teacher();
        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));
        $this->postJson('/api/giang-vien/ngan-hang-cau-hoi/import-save', [
            'questions' => [
                ['noi_dung' => 'Good MC', 'loai_cau_hoi' => 'trac_nghiem', 'dap_an' => ['A', 'B'], 'dap_an_dung_index' => 0],
                ['noi_dung' => '', 'loai_cau_hoi' => 'unknown'],
            ],
            'do_kho' => 'de',
        ])->assertUnprocessable();
        $this->assertDatabaseCount('ngan_hang_cau_hoi', 0);
    }

    private function teacher(): array
    {
        $roleId = DB::table('vai_tro')->where('ten_vai_tro', 'giang_vien')->value('id');
        $roleId ??= DB::table('vai_tro')->insertGetId(['ten_vai_tro' => 'giang_vien', 'created_at' => now(), 'updated_at' => now()]);
        return [$this->insertUser($roleId, 'teacher-' . uniqid())];
    }

    private function insertUser(int $roleId, string $name): int
    {
        return DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => $name,
            'email' => $name . '@example.test',
            'password' => bcrypt('password'),
            'vai_tro_id' => $roleId,
            'trang_thai' => 'hoat_dong',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertQuestion(int $ownerId): int
    {
        $id = DB::table('ngan_hang_cau_hoi')->insertGetId([
            'nguoi_tao_id' => $ownerId, 'noi_dung' => 'Owned question', 'loai_cau_hoi' => 'trac_nghiem',
            'chu_de' => 'Đại số', 'do_kho' => 'de', 'trang_thai' => 'active', 'version' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('ngan_hang_dap_an')->insert([
            ['ngan_hang_cau_hoi_id' => $id, 'noi_dung' => 'A', 'la_dap_an_dung' => true, 'thu_tu' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['ngan_hang_cau_hoi_id' => $id, 'noi_dung' => 'B', 'la_dap_an_dung' => false, 'thu_tu' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        return $id;
    }

    private function multipleChoicePayload(): array
    {
        return [
            'noi_dung' => 'What is 2 + 2?', 'loai_cau_hoi' => 'trac_nghiem',
            'chu_de' => 'Đại số', 'do_kho' => 'de', 'dap_an' => ['4', '3'], 'dap_an_dung_index' => 0,
        ];
    }
}
