<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                'required',
                'numeric',
                'between:0,10',
            ],
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
            ->select('bn.id')
            ->first();

        abort_unless(
            $submission,
            404,
            'Không tìm thấy bài nộp thuộc lớp của bạn.'
        );

        $score = (float) $data['diem'];

        DB::transaction(function () use (
            $baiNopId,
            $user,
            $data,
            $score
        ) {
            DB::table('bai_nop')
                ->where('id', $baiNopId)
                ->lockForUpdate()
                ->first();

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
