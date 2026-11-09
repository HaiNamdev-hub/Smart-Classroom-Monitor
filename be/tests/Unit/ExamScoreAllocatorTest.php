<?php

namespace Tests\Unit;

use App\Support\ExamScoreAllocator;
use PHPUnit\Framework\TestCase;

class ExamScoreAllocatorTest extends TestCase
{
    public function test_multiple_choice_only_distributes_the_full_ten_points(): void
    {
        $scores = ExamScoreAllocator::distribute(1000, 3);

        $this->assertSame([334, 333, 333], $scores);
        $this->assertSame(1000, array_sum($scores));
    }

    public function test_mixed_exam_distributes_remaining_hundredths_without_changing_essay_points(): void
    {
        $essayCents = 675;
        $scores = ExamScoreAllocator::distribute(1000 - $essayCents, 4);

        $this->assertSame([82, 81, 81, 81], $scores);
        $this->assertSame(1000, $essayCents + array_sum($scores));
    }

    public function test_essay_only_requires_exact_ten_points_before_publication(): void
    {
        $this->assertSame(1000, ExamScoreAllocator::cents('6.25') + ExamScoreAllocator::cents('3.75'));
        $this->assertNotSame(1000, ExamScoreAllocator::cents('6.25') + ExamScoreAllocator::cents('3.74'));
    }

    public function test_reallocation_after_essay_change_keeps_new_essay_score_and_rebalances_mc(): void
    {
        $essayCents = ExamScoreAllocator::cents('4.01');
        $scores = ExamScoreAllocator::distribute(1000 - $essayCents, 3);

        $this->assertSame([200, 200, 199], $scores);
        $this->assertSame(1000, $essayCents + array_sum($scores));
    }

    public function test_importing_another_question_rebalances_all_questions_in_the_same_hundredth_unit(): void
    {
        $essayCents = ExamScoreAllocator::cents('2.50');
        $scores = ExamScoreAllocator::distribute(1000 - $essayCents, 5);

        $this->assertSame([150, 150, 150, 150, 150], $scores);
        $this->assertSame(1000, $essayCents + array_sum($scores));
    }
}
