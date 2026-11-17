<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GiangVienCauHinhTronDeController extends Controller
{
    public function show(Request $request, int $baiTapId): JsonResponse
    {
        $assignment = $this->ownedAssignment($request, $baiTapId);
        $settingsTableExists = Schema::hasTable('bai_tap_cau_hinh_tron');
        $settings = $settingsTableExists
            ? DB::table('bai_tap_cau_hinh_tron')->where('bai_tap_id', $baiTapId)->first()
            : null;
        $lock = $this->lockReason($assignment, $baiTapId);
        if (!$settingsTableExists) {
            $lock = 'Cấu hình trộn chưa được kích hoạt trên máy chủ; hãy áp dụng migration staging trước khi bật tính năng.';
        }

        return response()->json([
            'status' => true,
            'data' => [
                'bai_tap_id' => $baiTapId,
                'tron_cau_hoi' => (bool) ($settings->tron_cau_hoi ?? false),
                'tron_dap_an' => (bool) ($settings->tron_dap_an ?? false),
                'locked' => $lock !== null,
                'locked_reason' => $lock,
            ],
        ]);
    }

    public function update(Request $request, int $baiTapId): JsonResponse
    {
        $data = $request->validate([
            'tron_cau_hoi' => ['required', 'boolean'],
            'tron_dap_an' => ['required', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $baiTapId, $data) {
            abort_unless(
                Schema::hasTable('bai_tap_cau_hinh_tron'),
                503,
                'Cấu hình trộn chưa được kích hoạt trên máy chủ; hãy áp dụng migration staging trước khi lưu.'
            );

            $assignment = DB::table('bai_tap')
                ->where('id', $baiTapId)
                ->where('nguoi_tao_id', $request->user()->id)
                ->lockForUpdate()
                ->first();
            abort_unless($assignment, 404, 'Không tìm thấy bài kiểm tra của bạn.');

            $lock = $this->lockReason($assignment, $baiTapId);
            abort_if($lock !== null, 409, $lock);

            $now = now();
            $values = [
                'tron_cau_hoi' => (bool) $data['tron_cau_hoi'],
                'tron_dap_an' => (bool) $data['tron_dap_an'],
                'updated_at' => $now,
            ];
            $exists = DB::table('bai_tap_cau_hinh_tron')->where('bai_tap_id', $baiTapId)->exists();
            if ($exists) {
                DB::table('bai_tap_cau_hinh_tron')->where('bai_tap_id', $baiTapId)->update($values);
            } else {
                DB::table('bai_tap_cau_hinh_tron')->insert($values + [
                    'bai_tap_id' => $baiTapId,
                    'created_at' => $now,
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Đã lưu cấu hình trộn đề.',
                'data' => [
                    'bai_tap_id' => $baiTapId,
                    'tron_cau_hoi' => (bool) $data['tron_cau_hoi'],
                    'tron_dap_an' => (bool) $data['tron_dap_an'],
                    'locked' => false,
                    'locked_reason' => null,
                ],
            ]);
        }, 3);
    }

    private function ownedAssignment(Request $request, int $baiTapId): object
    {
        $assignment = DB::table('bai_tap')
            ->where('id', $baiTapId)
            ->where('nguoi_tao_id', $request->user()->id)
            ->first();
        abort_unless($assignment, 404, 'Không tìm thấy bài kiểm tra của bạn.');

        return $assignment;
    }

    private function lockReason(object $assignment, int $baiTapId): ?string
    {
        if (DB::table('bai_kiem_tra_luot_lam')->where('bai_tap_id', $baiTapId)->exists()) {
            return 'Đã có Sinh viên bắt đầu làm bài; cấu hình trộn được khóa để giữ nguyên đề đã giao.';
        }

        if (DB::table('bai_nop')->where('bai_tap_id', $baiTapId)->exists()) {
            return 'Bài kiểm tra đã có bài nộp; không thể thay đổi cấu hình trộn.';
        }

        if (DB::table('phien_giam_sat')->where('bai_tap_id', $baiTapId)->where('trang_thai', 'dang_dien_ra')->exists()) {
            return 'Phiên giám sát cho bài kiểm tra đã bắt đầu; cấu hình trộn được khóa.';
        }

        if (Schema::hasTable('ngan_hang_de_tao')) {
            $bankExam = DB::table('ngan_hang_de_tao')->where('bai_tap_id', $baiTapId)->first(['trang_thai']);
            if ($bankExam?->trang_thai === 'da_cong_bo') {
                return 'Đề đã công bố; cấu hình trộn được khóa để không thay đổi đề đã giao.';
            }
        }

        if (!empty($assignment->thoi_gian_bat_dau)) {
            $start = Carbon::parse($assignment->thoi_gian_bat_dau);
            if ($start->lessThanOrEqualTo(now())) {
                return 'Bài kiểm tra đã đến giờ mở; cấu hình trộn không thể thay đổi.';
            }
        }

        return null;
    }
}
