<?php

namespace Tests\Feature;

use App\Support\ExamScoreAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamScoreAllocationTest extends TestCase
{
    use RefreshDatabase;

    private function assignment(): int
    {
        $now = now();
        $roleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'giang_vien', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $teacherId = DB::table('nguoi_dung')->insertGetId([
            'ho_ten' => 'Teacher', 'email' => 'scores@example.test', 'password' => 'x',
            'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $classId = DB::table('lop_hoc')->insertGetId([
            'ma_lop' => 'SCORE-001', 'ten_lop' => 'Scoring', 'giang_vien_id' => $teacherId,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        return DB::table('bai_tap')->insertGetId([
            'lop_hoc_id' => $classId, 'nguoi_tao_id' => $teacherId, 'tieu_de' => 'Score allocation',
            'thoi_gian_bat_dau' => $now->copy()->addDay(),
            'thoi_gian_ket_thuc' => $now->copy()->addDays(2), 'han_nop' => $now->copy()->addDays(2),
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function question(int $assignmentId, string $type, string $points, int $order): int
    {
        $now = now();
        return DB::table('cau_hoi')->insertGetId([
            'bai_tap_id' => $assignmentId, 'noi_dung' => "Question $order",
            'loai_cau_hoi' => $type, 'diem' => $points, 'thu_tu' => $order,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function savedScores(int $assignmentId, string $type): array
    {
        return DB::table('cau_hoi')->where('bai_tap_id', $assignmentId)
            ->where('loai_cau_hoi', $type)->orderBy('thu_tu')->pluck('diem')
            ->map(fn ($score) => ExamScoreAllocator::cents($score))->all();
    }

    public function test_mc_only_gets_exact_ten_points(): void
    {
        $id = $this->assignment();
        for ($i = 1; $i <= 3; $i++) $this->question($id, 'trac_nghiem', '1.00', $i);

        $summary = ExamScoreAllocator::rebalance($id);
        $this->assertSame([334, 333, 333], $this->savedScores($id, 'trac_nghiem'));
        $this->assertSame(1000, $summary['totalPoints']);
        ExamScoreAllocator::assertPublishable($id);
    }

    public function test_essay_only_must_sum_to_ten_before_publication(): void
    {
        $id = $this->assignment();
        $this->question($id, 'tu_luan', '6.25', 1);
        $second = $this->question($id, 'tu_luan', '3.74', 2);

        $this->assertSame(999, ExamScoreAllocator::rebalance($id)['totalPoints']);
        try {
            ExamScoreAllocator::assertPublishable($id);
            $this->fail('Essay-only exam below 10 must not be published.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        DB::table('cau_hoi')->where('id', $second)->update(['diem' => '3.75']);
        ExamScoreAllocator::assertPublishable($id);
    }

    public function test_mixed_scores_sum_to_ten_and_changing_essay_keeps_essay_points(): void
    {
        $id = $this->assignment();
        $essayId = $this->question($id, 'tu_luan', '2.00', 1);
        $this->question($id, 'tu_luan', '3.00', 2);
        for ($i = 3; $i <= 5; $i++) $this->question($id, 'trac_nghiem', '1.00', $i);

        ExamScoreAllocator::rebalance($id);
        $this->assertSame([167, 167, 166], $this->savedScores($id, 'trac_nghiem'));

        DB::table('cau_hoi')->where('id', $essayId)->update(['diem' => '5.50']);
        ExamScoreAllocator::rebalance($id);
        $this->assertSame(550, ExamScoreAllocator::cents(DB::table('cau_hoi')->where('id', $essayId)->value('diem')));
        $this->assertSame([50, 50, 50], $this->savedScores($id, 'trac_nghiem'));
        $this->assertSame(1000, array_sum($this->savedScores($id, 'tu_luan')) + array_sum($this->savedScores($id, 'trac_nghiem')));
    }

    public function test_importing_another_mc_question_rebalances_existing_and_new_questions(): void
    {
        $id = $this->assignment();
        $this->question($id, 'tu_luan', '4.50', 1);
        for ($i = 2; $i <= 4; $i++) $this->question($id, 'trac_nghiem', '1.00', $i);
        ExamScoreAllocator::rebalance($id);
        $this->question($id, 'trac_nghiem', '1.00', 5);

        ExamScoreAllocator::rebalance($id);
        $this->assertSame([138, 138, 137, 137], $this->savedScores($id, 'trac_nghiem'));
        $this->assertSame(1000, array_sum($this->savedScores($id, 'tu_luan')) + array_sum($this->savedScores($id, 'trac_nghiem')));
    }
}
