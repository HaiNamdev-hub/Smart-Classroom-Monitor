<?php

namespace App\Http\Controllers;

use App\Events\WebRtcSignalEvent;
use App\Models\PhienGiamSat;
use App\Models\SinhVien;
use App\Models\ThanhVienLop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebRtcSignalController extends Controller
{
    /**
     * Gửi tín hiệu WebRTC signaling an toàn qua Sanctum.
     *
     * POST /api/webrtc/signal
     */
    public function signal(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        $validated = $request->validate([
            'phienId' => ['required', 'integer'],
            'sinhVienId' => ['required', 'integer'],
            'type' => [
                'required',
                'string',
                Rule::in([
                    'offer',
                    'answer',
                    'candidate',
                    'camera_ready',
                    'camera_stopped',
                    'request_offer',
                ]),
            ],
            'data' => ['nullable'],
        ]);

        $phienId = (int) $validated['phienId'];
        $sinhVienId = (int) $validated['sinhVienId'];
        $type = $validated['type'];
        $data = $validated['data'] ?? null;

        // 1. Kiểm tra phiên giám sát
        $phien = PhienGiamSat::query()
            ->with('lopHoc')
            ->find($phienId);

        if (!$phien) {
            return response()->json([
                'status' => false,
                'message' => 'Phiên giám sát không tồn tại.',
            ], 404);
        }

        if ($phien->trang_thai !== 'dang_dien_ra') {
            return response()->json([
                'status' => false,
                'message' => 'Phiên giám sát không đang diễn ra.',
            ], 403);
        }

        // `sinhVienId` trên signaling là sinh_vien.id. Bảng thành viên
        // lại lưu nguoi_dung.id, nên đổi qua đúng quan hệ trước khi kiểm tra.
        $targetStudent = SinhVien::query()->find($sinhVienId);
        if (!$targetStudent) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy hồ sơ Sinh viên.',
            ], 404);
        }

        // 2. Kiểm tra Sinh viên mục tiêu có thuộc lớp của phiên không
        $thuocLop = ThanhVienLop::query()
            ->where('lop_hoc_id', $phien->lop_hoc_id)
            ->where('sinh_vien_id', $targetStudent->nguoi_dung_id)
            ->exists();

        if (!$thuocLop) {
            return response()->json([
                'status' => false,
                'message' => 'Sinh viên không thuộc lớp của phiên này.',
            ], 403);
        }

        // 3. Xác minh vai trò người gửi (không tin cậy role từ client)
        $isTeacher = $phien->lopHoc
            && (int) $phien->lopHoc->giang_vien_id === (int) $user->id;

        $isSelfStudent = (int) $targetStudent->nguoi_dung_id === (int) $user->id;

        if (!$isTeacher && !$isSelfStudent) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn không có quyền gửi tín hiệu trong phiên giám sát này.',
            ], 403);
        }

        $allowedTypes = $isTeacher
            ? ['answer', 'candidate', 'request_offer']
            : ['offer', 'candidate', 'camera_ready', 'camera_stopped'];

        if (!in_array($type, $allowedTypes, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Loại tín hiệu không hợp lệ với vai trò người gửi.',
            ], 403);
        }

        $senderRole = $isTeacher ? 'teacher' : 'student';

        // 4. Phát tín hiệu qua Reverb WebSocket (loại trừ chính socket người gửi)
        broadcast(new WebRtcSignalEvent(
            phienId: $phienId,
            sinhVienId: $sinhVienId,
            senderRole: $senderRole,
            type: $type,
            data: $data
        ))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Đã gửi tín hiệu WebRTC thành công.',
        ]);
    }
}
