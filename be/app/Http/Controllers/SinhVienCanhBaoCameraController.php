<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class SinhVienCanhBaoCameraController extends Controller
{
    public function store(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $data = $request->validate([
            'eventId' => ['required', 'uuid'],
            'loaiCanhBao' => [
                'required',
                Rule::in([
                    'khong_co_khuon_mat',
                    'nhieu_khuon_mat',
                    'camera_ngat',
                ]),
            ],
        ]);

        // Sinh viên lấy từ tài khoản đăng nhập
        $sinhVienId = DB::table('sinh_vien')
            ->where('nguoi_dung_id', $request->user()->id)
            ->value('id');

        abort_unless(
            $sinhVienId,
            403,
            'Tài khoản không phải sinh viên.'
        );

        // Phải thuộc lớp của bài kiểm tra
        $baiTap = DB::table('bai_tap as bt')
            ->join(
                'thanh_vien_lop as tv',
                'tv.lop_hoc_id',
                '=',
                'bt.lop_hoc_id'
            )
            ->where('bt.id', $baiTapId)
            ->where('tv.sinh_vien_id', $sinhVienId)
            ->where('tv.trang_thai', 'dang_hoc')
            ->select('bt.*')
            ->first();

        abort_unless(
            $baiTap,
            403,
            'Bạn không thuộc lớp của bài kiểm tra.'
        );

        return DB::transaction(function () use (
            $data,
            $baiTap,
            $baiTapId,
            $sinhVienId
        ) {
            // Khóa hàng bài tập để tránh ghi đồng thời vượt ngưỡng
            DB::table('bai_tap')
                ->where('id', $baiTapId)
                ->lockForUpdate()
                ->first();

            $cameraQuery = DB::table('canh_bao_camera')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $sinhVienId);

            $cameraCount = (clone $cameraQuery)->count();

            $tabCount = DB::table('vi_pham_kiem_tra')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $sinhVienId)
                ->count();

            $daNop = DB::table('bai_nop')
                ->where('bai_tap_id', $baiTapId)
                ->where('sinh_vien_id', $sinhVienId)
                ->exists();

            // Không ghi thêm sau khi nộp hoặc đã khóa
            if (
                $daNop ||
                $cameraCount >= 5 ||
                $tabCount >= 3
            ) {
                return $this->responseState(
                    $cameraCount,
                    $tabCount,
                    $daNop
                );
            }

            // Kiểm tra thời gian thi
            $now = Carbon::now('Asia/Ho_Chi_Minh');

            $start = Carbon::parse(
                $baiTap->thoi_gian_bat_dau,
                'Asia/Ho_Chi_Minh'
            );

            $deadline = Carbon::parse(
                $baiTap->thoi_gian_ket_thuc
                    ?: $baiTap->han_nop,
                'Asia/Ho_Chi_Minh'
            );

            if (!empty($baiTap->han_nop)) {
                $hanNop = Carbon::parse(
                    $baiTap->han_nop,
                    'Asia/Ho_Chi_Minh'
                );

                if ($hanNop->lt($deadline)) {
                    $deadline = $hanNop;
                }
            }

            abort_if(
                $now->lt($start),
                409,
                'Bài kiểm tra chưa bắt đầu.'
            );

            abort_if(
                $now->gt($deadline),
                409,
                'Bài kiểm tra đã hết thời gian.'
            );

            // Gửi lại eventId cũ không được tính thêm
            $existed = (clone $cameraQuery)
                ->where('event_id', $data['eventId'])
                ->exists();

            if (!$existed) {
                DB::table('canh_bao_camera')->insert([
                    'bai_tap_id' => $baiTapId,
                    'sinh_vien_id' => $sinhVienId,
                    'event_id' => $data['eventId'],
                    'loai_canh_bao' => $data['loaiCanhBao'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $cameraCount++;
            }

            return $this->responseState(
                $cameraCount,
                $tabCount,
                false
            );
        });
    }

    private function responseState(
        int $cameraCount,
        int $tabCount,
        bool $daNop
    ): JsonResponse {
        $biKhoa = $cameraCount >= 5 || $tabCount >= 3;

        return response()->json([
            'status' => true,
            'data' => [
                'soLanCamera' => $cameraCount,
                'soLanViPham' => $tabCount,
                'soLanConLai' => max(0, 5 - $cameraCount),
                'biKhoa' => $biKhoa,
                'daNop' => $daNop,
                'lyDoKhoa' => $cameraCount >= 5
                    ? 'camera'
                    : ($tabCount >= 3 ? 'roi_tab' : null),
            ],
        ]);
    }
}
