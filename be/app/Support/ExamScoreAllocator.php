<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamScoreAllocator
{
    public static function cents(mixed $score): int
    {
        return (int) round(((float) $score) * 100);
    }

    public static function hasTwoDecimals(mixed $score): bool
    {
        $scaled = ((float) $score) * 100;
        return abs($scaled - round($scaled)) < 0.000001;
    }

    public static function distribute(int $remainingCents, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $base = intdiv($remainingCents, $count);
        $remainder = $remainingCents % $count;
        return array_map(
            fn ($index) => $base + ($index < $remainder ? 1 : 0),
            range(0, $count - 1)
        );
    }

    /** Reassign MC points without changing essay points. Call inside a transaction. */
    public static function rebalance(int $baiTapId): array
    {
        $questions = DB::table('cau_hoi')
            ->where('bai_tap_id', $baiTapId)
            ->orderBy('thu_tu')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $essayQuestions = $questions->where('loai_cau_hoi', 'tu_luan');
        foreach ($essayQuestions as $question) {
            $points = self::cents($question->diem);
            if ($points <= 0 || $points > 1000 || !self::hasTwoDecimals($question->diem)) {
                throw ValidationException::withMessages([
                    'diem' => 'Mỗi câu tự luận phải có điểm lớn hơn 0, tối đa 10 và tối đa 2 chữ số thập phân.',
                ]);
            }
        }
        $essayPoints = $essayQuestions
            ->sum(fn ($question) => self::cents($question->diem));

        if ($essayPoints > 1000) {
            throw ValidationException::withMessages([
                'diem' => 'Tổng điểm tự luận không được vượt quá 10.',
            ]);
        }

        $multipleChoice = $questions->where('loai_cau_hoi', 'trac_nghiem')->values();
        $remaining = 1000 - $essayPoints;
        $allocations = self::distribute($remaining, $multipleChoice->count());

        foreach ($multipleChoice as $index => $question) {
            $points = $allocations[$index];
            DB::table('cau_hoi')
                ->where('id', $question->id)
                ->update(['diem' => number_format($points / 100, 2, '.', '')]);
        }

        $essayOnly = $multipleChoice->isEmpty() && $questions->isNotEmpty();
        $total = $essayPoints + ($multipleChoice->isEmpty() ? 0 : $remaining);

        return [
            'essayPoints' => $essayPoints,
            'multipleChoicePoints' => $remaining,
            'totalPoints' => $total,
            'remainingPoints' => $essayOnly ? 1000 - $essayPoints : 0,
            'questionCount' => $questions->count(),
            'essayOnly' => $essayOnly,
        ];
    }

    public static function assertPublishable(int $baiTapId): void
    {
        $summary = self::rebalance($baiTapId);

        if ($summary['questionCount'] === 0) {
            throw ValidationException::withMessages([
                'questions' => 'Vui lòng tạo ít nhất một câu hỏi trước khi sử dụng đề thi.',
            ]);
        }

        if ($summary['totalPoints'] !== 1000) {
            throw ValidationException::withMessages([
                'diem' => 'Tổng điểm của đề thi phải bằng 10 trước khi sử dụng.',
            ]);
        }
    }
}
