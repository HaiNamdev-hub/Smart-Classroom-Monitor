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

        if ($questionIds === []) {
            return;
        }

        $answerIdsByQuestion = DB::table('dap_an')
            ->whereIn('cau_hoi_id', $questionIds)
            ->orderBy('cau_hoi_id')->orderBy('thu_tu')->orderBy('id')
            ->get(['cau_hoi_id', 'id'])
            ->groupBy('cau_hoi_id')
            ->map(fn ($answers) => $answers->pluck('id')->map(fn ($id) => (int) $id)->all());

        $now = now();
        $questionRows = [];
        $answerRows = [];
        foreach ($questionIds as $position => $questionId) {
            $questionRows[] = [
                'luot_lam_id' => $attemptId,
                'cau_hoi_id' => $questionId,
                'thu_tu_hien_thi' => $position + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $answerIds = $answerIdsByQuestion->get($questionId, []);
            if ($shuffleAnswers) {
                $answerIds = $this->shuffled($answerIds);
            }

            foreach ($answerIds as $answerPosition => $answerId) {
                $answerRows[] = [
                    'luot_lam_id' => $attemptId,
                    'cau_hoi_id' => $questionId,
                    'dap_an_id' => $answerId,
                    'thu_tu_hien_thi' => $answerPosition + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // SQLite builds can retain a 999-bind limit; batches of 100 rows stay
        // below that even for the six-column answer mapping.
        foreach (array_chunk($questionRows, 100) as $batch) {
            DB::table('bai_kiem_tra_luot_lam_cau_hoi')->insert($batch);
        }
        foreach (array_chunk($answerRows, 100) as $batch) {
            DB::table('bai_kiem_tra_luot_lam_dap_an')->insert($batch);
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
