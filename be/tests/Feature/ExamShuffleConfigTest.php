<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use App\Support\ExamAttemptOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamShuffleConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_exam_defaults_off_and_only_owner_can_read_or_update_settings(): void
    {
        [$teacherId, $classId] = $this->teacherAndClass();
        $examId = $this->assignment($teacherId, $classId, now()->addHour());
        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));

        $this->getJson("/api/giang-vien/bai-tap/{$examId}/cau-hinh-tron")
            ->assertOk()->assertJsonPath('data.tron_cau_hoi', false)->assertJsonPath('data.tron_dap_an', false)->assertJsonPath('data.locked', false);
        $this->putJson("/api/giang-vien/bai-tap/{$examId}/cau-hinh-tron", ['tron_cau_hoi' => true, 'tron_dap_an' => true])
            ->assertOk()->assertJsonPath('data.tron_cau_hoi', true)->assertJsonPath('data.tron_dap_an', true);

        [$otherTeacherId] = $this->teacherAndClass();
        Sanctum::actingAs(NguoiDung::findOrFail($otherTeacherId));
        $this->getJson("/api/giang-vien/bai-tap/{$examId}/cau-hinh-tron")->assertNotFound();
        $this->putJson("/api/giang-vien/bai-tap/{$examId}/cau-hinh-tron", ['tron_cau_hoi' => false, 'tron_dap_an' => false])->assertNotFound();
    }

    public function test_published_bank_exam_and_started_exam_configuration_are_locked(): void
    {
        [$teacherId, $classId] = $this->teacherAndClass();
        $publishedId = $this->assignment($teacherId, $classId, now()->addDay());
        DB::table('ngan_hang_de_tao')->insert([
            'bai_tap_id' => $publishedId, 'nguoi_tao_id' => $teacherId, 'thoi_luong_phut' => 30,
            'trang_thai' => 'da_cong_bo', 'tron_cau_hoi' => true, 'tron_dap_an' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('bai_tap_cau_hinh_tron')->insert([
            'bai_tap_id' => $publishedId, 'tron_cau_hoi' => true, 'tron_dap_an' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Sanctum::actingAs(NguoiDung::findOrFail($teacherId));
        $this->getJson("/api/giang-vien/bai-tap/{$publishedId}/cau-hinh-tron")
            ->assertOk()->assertJsonPath('data.locked', true);
        $this->putJson("/api/giang-vien/bai-tap/{$publishedId}/cau-hinh-tron", ['tron_cau_hoi' => false, 'tron_dap_an' => false])->assertStatus(409);

        $startedId = $this->assignment($teacherId, $classId, now()->addDay());
        $student = $this->student($classId, 'locked');
        $attemptAt = now();
        DB::table('bai_kiem_tra_luot_lam')->insert([
            'bai_tap_id' => $startedId, 'sinh_vien_id' => $student['profileId'], 'bat_dau_luc' => $attemptAt,
            'created_at' => $attemptAt, 'updated_at' => $attemptAt,
        ]);
        $this->getJson("/api/giang-vien/bai-tap/{$startedId}/cau-hinh-tron")
            ->assertOk()->assertJsonPath('data.locked', true);
        $this->putJson("/api/giang-vien/bai-tap/{$startedId}/cau-hinh-tron", ['tron_cau_hoi' => true, 'tron_dap_an' => false])->assertStatus(409);
    }

    public function test_shuffle_is_per_attempt_reload_stable_and_grades_by_answer_id(): void
    {
        [$teacherId, $classId] = $this->teacherAndClass();
        $start = Carbon::now('Asia/Ho_Chi_Minh')->addMinutes(2);
        $examId = $this->assignment($teacherId, $classId, $start);
        $questionIds = $this->multipleChoiceQuestions($examId, 4);
        DB::table('bai_tap_cau_hinh_tron')->insert([
            'bai_tap_id' => $examId, 'tron_cau_hoi' => true, 'tron_dap_an' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $first = $this->student($classId, 'first');
        $second = $this->student($classId, 'second');

        // Deterministic Fisher–Yates choices make the test assert distinct orders without flaky randomness.
        $randomCalls = 0;
        app()->instance(ExamAttemptOrder::class, new ExamAttemptOrder(
            function (int $max) use (&$randomCalls): int {
                $randomCalls++;
                return $randomCalls <= 7 ? $max : 0;
            }
        ));

        Carbon::setTestNow($start);
        try {
            Sanctum::actingAs(NguoiDung::findOrFail($first['userId']));
            $firstStart = $this->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", ['fullscreen' => true, 'cameraReady' => true])->assertCreated();
            $firstOrder = array_column($firstStart->json('data.questions'), 'id');
            $firstAnswerOrder = $this->answerOrder($firstStart->json('data.questions'));
            $firstAttempt = DB::table('bai_kiem_tra_luot_lam')->where('bai_tap_id', $examId)->where('sinh_vien_id', $first['profileId'])->value('id');
            $this->assertSame(4, DB::table('bai_kiem_tra_luot_lam_cau_hoi')->where('luot_lam_id', $firstAttempt)->count());
            $this->assertSame(8, DB::table('bai_kiem_tra_luot_lam_dap_an')->where('luot_lam_id', $firstAttempt)->count());
            $repeat = $this->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", ['fullscreen' => true, 'cameraReady' => true])->assertOk();
            $this->assertTrue($repeat->json('data.duplicate'));
            $this->assertSame($firstOrder, array_column($repeat->json('data.questions'), 'id'));
            $this->assertSame($firstAnswerOrder, $this->answerOrder($repeat->json('data.questions')));
            $refreshedQuestions = $this->getJson("/api/sinh-vien/bai-kiem-tra/{$examId}")->assertOk()->json('data.questions');
            $this->assertSame($firstOrder, array_column($refreshedQuestions, 'id'));
            $this->assertSame($firstAnswerOrder, $this->answerOrder($refreshedQuestions));

            Sanctum::actingAs(NguoiDung::findOrFail($second['userId']));
            $secondStart = $this->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", ['fullscreen' => true, 'cameraReady' => true])->assertCreated();
            $secondOrder = array_column($secondStart->json('data.questions'), 'id');
            $secondAnswerOrder = $this->answerOrder($secondStart->json('data.questions'));
            $this->assertNotSame($firstOrder, $secondOrder);
            foreach ($firstOrder as $questionId) {
                $this->assertNotSame($firstAnswerOrder[$questionId], $secondAnswerOrder[$questionId]);
            }
            $this->assertSame($firstOrder, array_column($this->asStudent($first['userId'], fn () => $this->getJson("/api/sinh-vien/bai-kiem-tra/{$examId}")->assertOk()->json('data.questions')), 'id'));
            Sanctum::actingAs(NguoiDung::findOrFail($second['userId']));

            $answers = [];
            foreach ($secondOrder as $questionId) {
                $answers[(string) $questionId] = (int) DB::table('dap_an')->where('cau_hoi_id', $questionId)->where('la_dap_an_dung', true)->value('id');
            }
            $submission = $this->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/nop", ['answers' => $answers])->assertCreated();
            $submissionId = (int) $submission->json('data.id');
            $this->assertSame(1000, (int) round(DB::table('diem')->where('bai_nop_id', $submissionId)->value('diem') * 100));
            $attemptedStudentId = DB::table('sinh_vien')->where('nguoi_dung_id', $second['userId'])->value('id');
            $this->assertSame($secondOrder, DB::table('bai_kiem_tra_luot_lam_cau_hoi')->where('luot_lam_id', DB::table('bai_kiem_tra_luot_lam')->where('sinh_vien_id', $attemptedStudentId)->value('id'))->orderBy('thu_tu_hien_thi')->pluck('cau_hoi_id')->all());
            $this->assertSame($second['userId'], (int) DB::table('bai_nop')->where('id', $submissionId)->value('sinh_vien_id'));

            Sanctum::actingAs(NguoiDung::findOrFail($teacherId));
            $detail = $this->getJson("/api/giang-vien/bai-nop/{$submissionId}/chi-tiet")->assertOk();
            $this->assertTrue($detail->json('data.laBaiTrucTuyen'));
            $this->assertSame($secondOrder, array_column($detail->json('data.cauHoi'), 'id'));
            $submissions = $this->getJson("/api/giang-vien/bai-tap/{$examId}/bai-nop")
                ->assertOk()->json('data');
            $this->assertCount(1, $submissions);
            $this->assertSame('Student second', $submissions[0]['tenSinhVien']);
            $this->assertSame($second['studentNo'], $submissions[0]['maSinhVien']);
            $this->assertSame('Shuffle test', $submissions[0]['tenBaiTap']);
            $this->assertEquals(10.0, (float) $submissions[0]['diem']);
            $this->assertNotEmpty($submissions[0]['thoiGianNop']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_configuration_off_and_legacy_attempt_preserve_original_order(): void
    {
        [$teacherId, $classId] = $this->teacherAndClass();
        $start = Carbon::now('Asia/Ho_Chi_Minh')->addMinutes(2);
        $examId = $this->assignment($teacherId, $classId, $start);
        $this->multipleChoiceQuestions($examId, 4);
        $student = $this->student($classId, 'legacy');
        $timestamp = $start->format('Y-m-d H:i:s');
        $attemptId = DB::table('bai_kiem_tra_luot_lam')->insertGetId([
            'bai_tap_id' => $examId, 'sinh_vien_id' => $student['profileId'], 'bat_dau_luc' => $timestamp,
            'created_at' => $timestamp, 'updated_at' => $timestamp,
        ]);
        $originalOrder = DB::table('cau_hoi')->where('bai_tap_id', $examId)->orderBy('thu_tu')->orderBy('id')->pluck('id')->all();

        Carbon::setTestNow($start);
        try {
            Sanctum::actingAs(NguoiDung::findOrFail($student['userId']));
            $response = $this->postJson("/api/sinh-vien/bai-kiem-tra/{$examId}/bat-dau", ['fullscreen' => true, 'cameraReady' => true])->assertOk();
            $this->assertSame($originalOrder, array_column($response->json('data.questions'), 'id'));
            $this->assertDatabaseCount('bai_kiem_tra_luot_lam_cau_hoi', 0);
            $this->assertDatabaseCount('bai_kiem_tra_luot_lam_dap_an', 0);
            $this->assertDatabaseHas('bai_kiem_tra_luot_lam', ['id' => $attemptId, 'sinh_vien_id' => $student['profileId']]);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function asStudent(int $userId, callable $callback): mixed
    {
        Sanctum::actingAs(NguoiDung::findOrFail($userId));
        return $callback();
    }

    private function teacherAndClass(): array
    {
        $roleId = $this->role('giang_vien');
        $teacherId = DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Teacher', 'email' => uniqid('teacher-') . '@example.test', 'password' => bcrypt('password'),
            'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => uniqid('C'), 'ten_lop' => 'Class', 'giang_vien_id' => $teacherId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [(int) $teacherId, (int) $classId];
    }

    private function assignment(int $teacherId, int $classId, Carbon $start): int
    {
        $end = $start->copy()->addHour();
        return (int) DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacherId, 'tieu_de' => 'Shuffle test', 'mo_ta' => null,
            'thoi_gian_bat_dau' => $start->format('Y-m-d H:i:s'), 'thoi_gian_ket_thuc' => $end->format('Y-m-d H:i:s'),
            'han_nop' => $end->format('Y-m-d H:i:s'), 'cho_phep_nop_tre' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function multipleChoiceQuestions(int $examId, int $count): array
    {
        $ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $questionId = DB::table('cau_hoi')->insertGetId([
                'bai_tap_id' => $examId, 'noi_dung' => "Question {$i}", 'loai_cau_hoi' => 'trac_nghiem',
                'diem' => number_format(10 / $count, 2, '.', ''), 'thu_tu' => $i,
                'dap_an_mau' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('dap_an')->insert([
                ['cau_hoi_id' => $questionId, 'noi_dung' => 'Correct', 'la_dap_an_dung' => true, 'thu_tu' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['cau_hoi_id' => $questionId, 'noi_dung' => 'Wrong', 'la_dap_an_dung' => false, 'thu_tu' => 2, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $ids[] = (int) $questionId;
        }
        return $ids;
    }

    private function student(int $classId, string $suffix): array
    {
        $studentNo = uniqid('S');
        $userId = DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => "Student {$suffix}", 'email' => uniqid("{$suffix}-") . '@example.test', 'password' => bcrypt('password'),
            'vai_tro_id' => $this->role('sinh_vien'), 'trang_thai' => 'hoat_dong', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $facultyId = DB::table('khoa')->insertGetId(['ma_khoa' => uniqid('F'), 'ten_khoa' => 'Faculty', 'created_at' => now(), 'updated_at' => now()]);
        $majorId = DB::table('nganh')->insertGetId(['khoa_id' => $facultyId, 'ma_nganh' => uniqid('M'), 'ten_nganh' => 'Major', 'created_at' => now(), 'updated_at' => now()]);
        $profileId = DB::table('sinh_vien')->insertGetId([
            'nguoi_dung_id' => $userId, 'nganh_id' => $majorId, 'ma_sinh_vien' => $studentNo,
            'nam_trung_tuyen' => (int) now()->format('Y'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('thanh_vien_lop')->insert([
            'lop_hoc_id' => $classId, 'sinh_vien_id' => $userId, 'trang_thai' => 'dang_hoc', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return ['userId' => (int) $userId, 'profileId' => (int) $profileId, 'studentNo' => $studentNo];
    }

    private function answerOrder(array $questions): array
    {
        $order = [];
        foreach ($questions as $question) {
            $order[$question['id']] = array_column($question['dap_an'], 'id');
        }

        ksort($order);
        return $order;
    }

    private function role(string $name): int
    {
        $id = DB::table('vai_tro')->where('ten_vai_tro', $name)->value('id');
        return (int) ($id ?? DB::table('vai_tro')->insertGetId(['ten_vai_tro' => $name, 'created_at' => now(), 'updated_at' => now()]));
    }
}
