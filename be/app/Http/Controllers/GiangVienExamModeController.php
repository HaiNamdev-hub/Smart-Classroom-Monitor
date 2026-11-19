<?php

namespace App\Http\Controllers;

use App\Support\ExamMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class GiangVienExamModeController extends Controller
{
    public function show(Request $request, int $baiTapId): JsonResponse
    {
        $assignment = $this->ownedAssignment($request, $baiTapId);
        $mode = ExamMode::forAssignment($baiTapId);
        $lockReason = $this->lockReason($baiTapId);

        if (!Schema::hasTable('bai_tap_hinh_thuc_kiem_tra')) {
            $lockReason = 'Cấu hình hình thức thi chưa được kích hoạt trên máy chủ.';
        }

        return response()->json(['status' => true, 'data' => [
            'bai_tap_id' => $assignment->id,
            'hinh_thuc' => $mode,
            'ten_hinh_thuc' => $this->label($mode),
            'yeu_cau_camera' => $mode === ExamMode::ONLINE_CAMERA_REQUIRED,
            'co_the_chinh_sua' => $lockReason === null,
            'ly_do_khoa' => $lockReason,
        ]]);
    }

    public function update(Request $request, int $baiTapId): JsonResponse
    {
        $data = $request->validate([
            'hinh_thuc' => ['required', 'string', Rule::in([ExamMode::ONLINE_CAMERA_REQUIRED, ExamMode::LAB_NO_CAMERA])],
        ]);

        if (!Schema::hasTable('bai_tap_hinh_thuc_kiem_tra')) {
            return response()->json(['status' => false, 'message' => 'Migration cấu hình hình thức thi chưa được áp dụng.'], 503);
        }

        $result = DB::transaction(function () use ($request, $baiTapId, $data) {
            $assignment = DB::table('bai_tap')->where('id', $baiTapId)->lockForUpdate()->first();
            abort_unless($assignment && (int) $assignment->nguoi_tao_id === (int) $request->user()->id, 404, 'Không tìm thấy bài kiểm tra của bạn.');
            $lockReason = $this->lockReason($baiTapId);
            abort_if($lockReason !== null, 409, $lockReason);

            $settings = DB::table('bai_tap_hinh_thuc_kiem_tra')->where('bai_tap_id', $baiTapId)->first();
            if ($settings) {
                DB::table('bai_tap_hinh_thuc_kiem_tra')->where('bai_tap_id', $baiTapId)->update([
                    'hinh_thuc' => $data['hinh_thuc'], 'updated_at' => now(),
                ]);
            } else {
                DB::table('bai_tap_hinh_thuc_kiem_tra')->insert([
                    'bai_tap_id' => $baiTapId, 'hinh_thuc' => $data['hinh_thuc'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $data['hinh_thuc'];
        }, 3);

        return response()->json(['status' => true, 'data' => [
            'bai_tap_id' => $baiTapId,
            'hinh_thuc' => $result,
            'ten_hinh_thuc' => $this->label($result),
            'yeu_cau_camera' => $result === ExamMode::ONLINE_CAMERA_REQUIRED,
            'co_the_chinh_sua' => true,
            'ly_do_khoa' => null,
        ]]);
    }

    private function ownedAssignment(Request $request, int $baiTapId): object
    {
        $assignment = DB::table('bai_tap')->where('id', $baiTapId)
            ->where('nguoi_tao_id', $request->user()->id)->first();
        abort_unless($assignment, 404, 'Không tìm thấy bài kiểm tra của bạn.');
        return $assignment;
    }

    private function lockReason(int $baiTapId): ?string
    {
        $startsAt = DB::table('bai_tap')->where('id', $baiTapId)->value('thoi_gian_bat_dau');
        // DB datetime values are timezone-naive; interpret them in the app's configured timezone.
        if ($startsAt && Carbon::parse($startsAt, config('app.timezone'))->lessThanOrEqualTo(now(config('app.timezone')))) {
            return 'Không thể đổi hình thức sau khi kỳ thi đã bắt đầu theo lịch.';
        }
        if (DB::table('bai_kiem_tra_luot_lam')->where('bai_tap_id', $baiTapId)->exists()) {
            return 'Không thể đổi hình thức sau khi đã có Sinh viên bắt đầu làm bài.';
        }
        if (DB::table('bai_nop')->where('bai_tap_id', $baiTapId)->exists()) {
            return 'Không thể đổi hình thức sau khi bài kiểm tra đã có bài nộp.';
        }
        return null;
    }

    private function label(string $mode): string
    {
        return $mode === ExamMode::LAB_NO_CAMERA
            ? 'Thi tại phòng máy — Không yêu cầu camera'
            : 'Thi trực tuyến — Camera AI bắt buộc';
    }
}
