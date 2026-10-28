<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GiangVienLichKiemTraController extends Controller
{
    private function assignment(Request $request, int $baiTapId)
    {
        $assignment = DB::table('bai_tap')
            ->where('id', $baiTapId)
            ->where('nguoi_tao_id', $request->user()->id)
            ->first();

        abort_unless(
            $assignment,
            404,
            'Không tìm thấy bài kiểm tra của bạn.'
        );

        return $assignment;
    }

    public function show(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $assignment = $this->assignment($request, $baiTapId);

        return response()->json([
            'status' => true,
            'data' => [
                'bai_tap_id' => $assignment->id,
                'tieu_de' => $assignment->tieu_de,
                'thoi_gian_bat_dau' =>
                    $assignment->thoi_gian_bat_dau,
                'thoi_gian_ket_thuc' =>
                    $assignment->thoi_gian_ket_thuc,
                'han_nop' => $assignment->han_nop,
                'trang_thai' => $this->status($assignment),
            ],
        ]);
    }

    public function update(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $data = Validator::make($request->all(), [
            'thoi_gian_bat_dau' => [
                'required',
                'date_format:Y-m-d H:i:s',
            ],
            'thoi_gian_ket_thuc' => [
                'required',
                'date_format:Y-m-d H:i:s',
                'after:thoi_gian_bat_dau',
            ],
        ])->validate();

        $timezone = config('app.timezone');

        $start = Carbon::createFromFormat(
            '!Y-m-d H:i:s',
            $data['thoi_gian_bat_dau'],
            $timezone
        );

        $end = Carbon::createFromFormat(
            '!Y-m-d H:i:s',
            $data['thoi_gian_ket_thuc'],
            $timezone
        );

        if ($start->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'thoi_gian_bat_dau' =>
                    'Thời gian bắt đầu phải ở tương lai.',
            ]);
        }

        DB::transaction(function () use (
            $request,
            $baiTapId,
            $start,
            $end
        ) {
            $assignment = DB::table('bai_tap')
                ->where('id', $baiTapId)
                ->lockForUpdate()
                ->first();

            abort_unless(
                $assignment &&
                (int) $assignment->nguoi_tao_id ===
                (int) $request->user()->id,
                404,
                'Không tìm thấy bài kiểm tra.'
            );

            if (
                Carbon::parse($assignment->thoi_gian_bat_dau)
                    ->lessThanOrEqualTo(now())
            ) {
                abort(
                    409,
                    'Bài kiểm tra đã bắt đầu, không thể đổi lịch.'
                );
            }

            $hasSubmission = DB::table('bai_nop')
                ->where('bai_tap_id', $baiTapId)
                ->exists();

            abort_if(
                $hasSubmission,
                409,
                'Bài kiểm tra đã có bài nộp.'
            );

            $questionCount = DB::table('cau_hoi')
                ->where('bai_tap_id', $baiTapId)
                ->count();

            abort_if(
                $questionCount === 0,
                422,
                'Vui lòng tạo câu hỏi trước khi đặt lịch.'
            );

            DB::table('bai_tap')
                ->where('id', $baiTapId)
                ->update([
                    'thoi_gian_bat_dau' =>
                        $start->format('Y-m-d H:i:s'),
                    'thoi_gian_ket_thuc' =>
                        $end->format('Y-m-d H:i:s'),
                    'han_nop' =>
                        $end->format('Y-m-d H:i:s'),
                ]);
        });

        return response()->json([
            'status' => true,
            'message' =>
                'Đã lưu lịch. Bài thi sẽ tự mở đúng giờ.',
            'data' => [
                'thoi_gian_bat_dau' =>
                    $start->format('Y-m-d H:i:s'),
                'thoi_gian_ket_thuc' =>
                    $end->format('Y-m-d H:i:s'),
                'trang_thai' => 'sap_dien_ra',
            ],
        ]);
    }

    private function status($assignment): string
    {
        $now = now();
        $start = Carbon::parse($assignment->thoi_gian_bat_dau);
        $end = Carbon::parse($assignment->thoi_gian_ket_thuc);

        if ($now->lessThan($start)) {
            return 'sap_dien_ra';
        }

        if ($now->greaterThanOrEqualTo($end)) {
            return 'da_ket_thuc';
        }

        return 'dang_dien_ra';
    }
}
