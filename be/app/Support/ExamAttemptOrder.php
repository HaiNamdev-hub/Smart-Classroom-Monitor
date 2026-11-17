<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ExamAttemptOrder
{
    private ?\Closure $randomIndex;

    public function __construct(?callable $randomIndex = null)
    {
        $this->randomIndex = $randomIndex ? \Closure::fromCallable($randomIndex) : null;
    }

    /** Persist one immutable question/answer order for a newly-created attempt. */
    public function persist(int $attemptId, int $assignmentId, bool $shuffleQuestions, bool $shuffleAnswers): void
    {
        $questionIds = DB::table('cau_hoi')
            ->where('bai_tap_id', $assignmentId)
            ->orderBy('thu_tu')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($shuffleQuestions) {
            $questionIds = $this->shuffled($questionIds);
        }

        $now = now();
        foreach ($questionIds as $position => $questionId) {
            DB::table('bai_kiem_tra_luot_lam_cau_hoi')->insert([
                'luot_lam_id' => $attemptId,
                'cau_hoi_id' => $questionId,
                'thu_tu_hien_thi' => $position + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $answerIds = DB::table('dap_an')
                ->where('cau_hoi_id', $questionId)
                ->orderBy('thu_tu')->orderBy('id')->pluck('id')
                ->map(fn ($id) => (int) $id)->all();
            if ($shuffleAnswers) {
                $answerIds = $this->shuffled($answerIds);
            }

            foreach ($answerIds as $answerPosition => $answerId) {
                DB::table('bai_kiem_tra_luot_lam_dap_an')->insert([
                    'luot_lam_id' => $attemptId,
                    'cau_hoi_id' => $questionId,
                    'dap_an_id' => $answerId,
                    'thu_tu_hien_thi' => $answerPosition + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /** Fisher–Yates with a cryptographically unbiased random index. */
    public function shuffled(array $items): array
    {
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = $this->randomIndex
                ? ($this->randomIndex)($i)
                : random_int(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }
}
