<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnlineExamSubmitBatchTest extends TestCase
{
    use RefreshDatabase;

    private function createExamFixture(): array
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $roleTeacher = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'giang_vien',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleStudent = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $teacherId = DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Teacher One',
            'email' => 'teacher-batch@example.test',
            'password' => bcrypt('password'),
            'vai_tro_id' => $roleTeacher,
            'trang_thai' => 'hoat_dong',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $studentUserId = DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Student Batch',
            'email' => 'student-batch@example.test',
            'password' => bcrypt('password'),
            'vai_tro_id' => $roleStudent,
            'trang_thai' => 'hoat_dong',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $facultyId = DB::table('khoa')->insertGetId([
            'ma_khoa' => 'IT',
            'ten_khoa' => 'Information Tech',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $majorId = DB::table('nganh')->insertGetId([
            'khoa_id' => $facultyId,
            'ma_nganh' => 'CS',
            'ten_nganh' => 'Computer Science',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $studentProfileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $studentUserId,
            'nganh_id' => $majorId,
            'ma_sinh_vien' => 'BATCH-001',
            'nam_trung_tuyen' => (int) $now->format('Y'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'BATCH-CLASS',
            'ten_lop' => 'Batch Insert Class',
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

        $examId = DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId,
            'nguoi_tao_id' => $teacherId,
            'tieu_de' => 'Batch Insert Exam',
            'thoi_gian_bat_dau' => $now->copy()->subMinutes(10)->format('Y-m-d H:i:s'),
            'thoi_gian_ket_thuc' => $now->copy()->addMinutes(50)->format('Y-m-d H:i:s'),
            'han_nop' => $now->copy()->addMinutes(50)->format('Y-m-d H:i:s'),
            'cho_phep_nop_tre' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Tạo 4 câu trắc nghiệm (2.0 điểm/câu) và 1 câu tự luận (2.0 điểm) = Tổng 10.0
        $questions = [];
        for ($i = 1; $i <= 4; $i++) {
            $qId = DB::table('cau_hoi')->insertGetId([
                'bai_tap_id' => $examId,
                'noi_dung' => "Câu hỏi trắc nghiệm $i",
                'loai_cau_hoi' => 'trac_nghiem',
                'diem' => '2.00',
                'thu_tu' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $correctAnsId = DB::table('dap_an')->insertGetId([
                'cau_hoi_id' => $qId,
                'noi_dung' => "Đáp án đúng $i",
                'la_dap_an_dung' => true,
                'thu_tu' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $wrongAnsId = DB::table('dap_an')->insertGetId([
                'cau_hoi_id' => $qId,
                'noi_dung' => "Đáp án sai $i",
                'la_dap_an_dung' => false,
                'thu_tu' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $questions[$i] = [
                'id' => $qId,
                'correct' => $correctAnsId,
                'wrong' => $wrongAnsId,
            ];
        }

        $essayId = DB::table('cau_hoi')->insertGetId([
            'bai_tap_id' => $examId,
            'noi_dung' => 'Câu hỏi tự luận 5',
            'loai_cau_hoi' => 'tu_luan',
            'diem' => '2.00',
            'thu_tu' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $questions[5] = ['id' => $essayId];

        // Tạo attempt ban đầu
        DB::table('bai_kiem_tra_luot_lam')->insert([
            'bai_tap_id' => $examId,
            'sinh_vien_id' => $studentProfileId,
            'bat_dau_luc' => $now->copy()->subMinutes(5),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$studentUserId, $studentProfileId, $examId, $questions];
    }

    public function test_submit_saves_answers_in_batch_chunk_instead_of_n_individual_inserts(): void
    {
        [$studentUserId, $studentProfileId, $examId, $questions] = $this->createExamFixture();

        Sanctum::actingAs(NguoiDung::query()->findOrFail($studentUserId));

        // Chuẩn bị câu trả lời:
        // Câu 1: đúng
        // Câu 2: sai
        // Câu 3: null (chưa trả lời -> không lưu)
        // Câu 4: chuỗi rỗng (không lưu)
        // Câu 5: tự luận có nội dung
        $answers = [
            (string) $questions[1]['id'] => $questions[1]['correct'],
            (string) $questions[2]['id'] => $questions[2]['wrong'],
            (string) $questions[3]['id'] => null,
            (string) $questions[4]['id'] => '',
            (string) $questions[5]['id'] => 'Nội dung bài làm tự luận của sinh viên',
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/nop", [
            'answers' => $answers,
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', true);

        $queryLog = DB::getQueryLog();
        $cauTraLoiInserts = array_filter($queryLog, function ($entry) {
            $sql = strtolower($entry['query']);
            return str_contains($sql, 'insert into') && str_contains($sql, 'cau_tra_loi');
        });

        // Xác nhận câu lệnh INSERT vào cau_tra_loi được gộp đúng 1 batch INSERT duy nhất
        $this->assertCount(1, $cauTraLoiInserts, 'Phải thực hiện đúng 1 câu lệnh batch insert vào cau_tra_loi thay vì 3 câu lệnh riêng lẻ.');

        // Kiểm tra đúng 3 câu trả lời được lưu (câu 1, 2, 5)
        $savedAnswers = DB::table('cau_tra_loi')->get();
        $this->assertCount(3, $savedAnswers, 'Chỉ 3 câu có đáp án hợp lệ được lưu, bỏ qua null và chuỗi rỗng.');

        // Kiểm tra chi tiết từng câu
        $q1Answer = $savedAnswers->firstWhere('cau_hoi_id', $questions[1]['id']);
        $this->assertNotNull($q1Answer);
        $this->assertSame($questions[1]['correct'], (int) $q1Answer->dap_an_id);

        $q5Essay = $savedAnswers->firstWhere('cau_hoi_id', $questions[5]['id']);
        $this->assertNotNull($q5Essay);
        $this->assertSame('Nội dung bài làm tự luận của sinh viên', $q5Essay->noi_dung_tra_loi);

        // Kiểm tra tính Idempotency: Không được nộp lại lần 2
        $secondSubmit = $this->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/nop", [
            'answers' => $answers,
        ]);
        $secondSubmit->assertStatus(422)->assertJsonValidationErrors('answers');
        $this->assertSame(1, DB::table('bai_nop')->where('bai_tap_id', $examId)->count());
    }
}
