<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaiLamNhapSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy các bài kiểm tra có câu hỏi
        // và có sinh viên đang học trong lớp.
        $assignments = DB::table('bai_tap')
            ->join(
                'cau_hoi',
                'cau_hoi.bai_tap_id',
                '=',
                'bai_tap.id'
            )
            ->join(
                'thanh_vien_lop',
                'thanh_vien_lop.lop_hoc_id',
                '=',
                'bai_tap.lop_hoc_id'
            )
            ->where(
                'thanh_vien_lop.trang_thai',
                'dang_hoc'
            )
            ->select(
                'bai_tap.id as bai_tap_id',
                'thanh_vien_lop.sinh_vien_id'
            )
            ->distinct()
            ->limit(10)
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($assignments as $assignment) {
            // Không tạo bài nháp nếu sinh viên
            // đã nộp bài kiểm tra này.
            $submitted = DB::table('bai_nop')
                ->where(
                    'bai_tap_id',
                    $assignment->bai_tap_id
                )
                ->where(
                    'sinh_vien_id',
                    $assignment->sinh_vien_id
                )
                ->exists();

            if ($submitted) {
                $skipped++;
                continue;
            }

            $questions = DB::table('cau_hoi')
                ->where(
                    'bai_tap_id',
                    $assignment->bai_tap_id
                )
                ->orderBy('id')
                ->limit(3)
                ->get();

            $answers = [];

            foreach ($questions as $question) {
                if (
                    $question->loai_cau_hoi === 'trac_nghiem'
                ) {
                    // Chọn một đáp án hiện có,
                    // không cần biết đáp án đúng.
                    $answerId = DB::table('dap_an')
                        ->where(
                            'cau_hoi_id',
                            $question->id
                        )
                        ->orderBy('id')
                        ->value('id');

                    if ($answerId !== null) {
                        $answers[(string) $question->id] =
                            (int) $answerId;
                    }
                } elseif (
                    $question->loai_cau_hoi === 'tu_luan'
                ) {
                    $answers[(string) $question->id] =
                        'Đây là câu trả lời tự luận '
                        . 'đang được lưu nháp để kiểm thử.';
                }
            }

            if (empty($answers)) {
                $skipped++;
                continue;
            }

            // Không ghi đè bài nháp đã tồn tại.
            $exists = DB::table('bai_lam_nhap')
                ->where(
                    'bai_tap_id',
                    $assignment->bai_tap_id
                )
                ->where(
                    'sinh_vien_id',
                    $assignment->sinh_vien_id
                )
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            DB::table('bai_lam_nhap')->insert([
                'bai_tap_id' =>
                    $assignment->bai_tap_id,

                'sinh_vien_id' =>
                    $assignment->sinh_vien_id,

                'cau_tra_loi' => json_encode(
                    $answers,
                    JSON_UNESCAPED_UNICODE
                ),

                'created_at' => now(),
                'updated_at' => now()
            ]);

            $created++;
        }

        $this->command?->info(
            "Đã tạo {$created} bài làm nháp. "
            . "Bỏ qua {$skipped} bản ghi."
        );
    }
}
