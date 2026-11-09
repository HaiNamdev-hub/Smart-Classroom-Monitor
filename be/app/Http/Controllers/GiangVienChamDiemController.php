<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\ExamScoreAllocator;
use Illuminate\Validation\ValidationException;

class GiangVienChamDiemController extends Controller
{
    public function update(
        Request $request,
        int $baiNopId
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user,
            401,
            'Bạn chưa đăng nhập.'
        );

        $data = $request->validate([
            'diem' => [
                'nullable',
                'numeric',
                'between:0,10',
            ],
            'essayScores' => ['sometimes', 'array'],
            'essayScores.*' => ['required', 'numeric', 'between:0,10'],
            'nhanXet' => [
                'nullable',
                'string',
            ],
            'ketQua' => [
                'nullable',
                Rule::in(['dat', 'khong_dat']),
            ],
        ]);

        // Kiểm tra bài nộp thuộc lớp Giảng viên phụ trách
        $submission = DB::table('bai_nop as bn')
            ->join(
                'bai_tap as bt',
                'bn.bai_tap_id',
                '=',
                'bt.id'
            )
            ->join(
                'lop_hoc as lh',
                'bt.lop_hoc_id',
                '=',
                'lh.id'
            )
            ->where('bn.id', $baiNopId)
            ->where(
                'lh.giang_vien_id',
                $user->id
            )
            ->select('bn.id', 'bn.bai_tap_id', 'bn.loai_file')
            ->first();

        abort_unless(
            $submission,
            404,
            'Không tìm thấy bài nộp thuộc lớp của bạn.'
        );

        $essayGrades = [];
        $score = (float) ($data['diem'] ?? 0);
        if ($submission->loai_file === 'online_exam') {
            $questions = DB::table('cau_hoi')
                ->where('bai_tap_id', $submission->bai_tap_id)
                ->orderBy('thu_tu')->orderBy('id')->get();
            $essays = $questions->where('loai_cau_hoi', 'tu_luan');
            abort_if($essays->isEmpty(), 409, 'Bài trắc nghiệm được hệ thống chấm tự động.');
            abort_unless(isset($data['essayScores']), 422, 'Cần nhập điểm cho từng câu tự luận.');

            if ($questions->sum(fn ($question) => ExamScoreAllocator::cents($question->diem)) !== 1000) {
                throw ValidationException::withMessages([
                    'diem' => 'Đề thi có tổng điểm không hợp lệ; không thể chấm bài.',
                ]);
            }

            $essayTotalCents = 0;
            foreach ($essays as $question) {
                $questionId = (string) $question->id;
                if (!array_key_exists($questionId, $data['essayScores'])) {
                    throw ValidationException::withMessages([
                        "essayScores.$questionId" => 'Vui lòng nhập điểm cho từng câu tự luận.',
                    ]);
                }
                $value = $data['essayScores'][$questionId];
                if ((float) $value < 0 || (float) $value > (float) $question->diem ||
                    !ExamScoreAllocator::hasTwoDecimals($value)) {
                    throw ValidationException::withMessages([
                        "essayScores.$questionId" => "Điểm phải từ 0 đến {$question->diem}, tối đa 2 chữ số thập phân.",
                    ]);
                }
                $essayGrades[(int) $question->id] = ExamScoreAllocator::cents($value);
                $essayTotalCents += ExamScoreAllocator::cents($value);
            }

            $multipleChoiceCents = ExamScoreAllocator::cents(
                DB::table('cau_tra_loi as ctl')
                    ->join('cau_hoi as ch', 'ch.id', '=', 'ctl.cau_hoi_id')
                    ->join('dap_an as da', 'da.id', '=', 'ctl.dap_an_id')
                    ->where('ctl.bai_nop_id', $baiNopId)
                    ->where('ch.bai_tap_id', $submission->bai_tap_id)
                    ->where('ch.loai_cau_hoi', 'trac_nghiem')
                    ->where('da.la_dap_an_dung', true)
                    ->sum('ch.diem')
            );
            $score = ($essayTotalCents + $multipleChoiceCents) / 100;
            if ($score > 10) {
                throw ValidationException::withMessages([
                    'essayScores' => 'Tổng điểm bài không được vượt quá 10.',
                ]);
            }
        }

        if ($submission->loai_file !== 'online_exam' && !array_key_exists('diem', $data)) {
            throw ValidationException::withMessages(['diem' => 'Vui lòng nhập điểm.']);
        }

        DB::transaction(function () use (
            $baiNopId,
            $user,
            $data,
            $score,
            $essayGrades
        ) {
            DB::table('bai_nop')
                ->where('id', $baiNopId)
                ->lockForUpdate()
                ->first();

            foreach ($essayGrades as $questionId => $points) {
                $answer = DB::table('cau_tra_loi')
                    ->where('bai_nop_id', $baiNopId)
                    ->where('cau_hoi_id', $questionId)
                    ->first();
                if ($answer) {
                    DB::table('cau_tra_loi')->where('id', $answer->id)->update([
                        'diem' => number_format($points / 100, 2, '.', ''),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('cau_tra_loi')->insert([
                        'bai_nop_id' => $baiNopId,
                        'cau_hoi_id' => $questionId,
                        'dap_an_id' => null,
                        'noi_dung_tra_loi' => null,
                        'diem' => number_format($points / 100, 2, '.', ''),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('diem')->updateOrInsert(
                ['bai_nop_id' => $baiNopId],
                [
                    'nguoi_cham_id' => $user->id,
                    'diem' => $score,
                    'nhan_xet' => $data['nhanXet'] ?? null,
                    'ket_qua' => $data['ketQua']
                        ?? ($score >= 5 ? 'dat' : 'khong_dat'),
                    'thoi_gian_cham' => now(),
                    'updated_at' => now(),
                ]
            );
        });

        return response()->json([
            'status' => true,
            'message' => 'Chấm điểm thành công.',
            'data' => [
                'baiNopId' => $baiNopId,
                'diem' => $score,
            ],
        ]);
    }
}
