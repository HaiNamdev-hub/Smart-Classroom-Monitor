<?php

use App\Models\PhienGiamSat;
use App\Models\ThanhVienLop;
use App\Models\SinhVien;
use Illuminate\Support\Facades\Broadcast;

// Giữ kênh mặc định của Laravel.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Kênh tín hiệu WebRTC riêng cho mỗi Sinh viên trong phiên thi.
Broadcast::channel(
    'exam-monitor.{phienId}.student.{sinhVienId}',
    function ($user, $phienId, $sinhVienId) {

        $phien = PhienGiamSat::query()
            ->with('lopHoc')
            ->find($phienId);

        // Phiên không tồn tại hoặc không đang diễn ra.
        if (
            !$phien ||
            $phien->trang_thai !== 'dang_dien_ra'
        ) {
            return false;
        }

        // Channel key dùng sinh_vien.id; membership lưu nguoi_dung.id.
        $student = SinhVien::query()->find($sinhVienId);
        if (!$student) {
            return false;
        }

        // Sinh viên phải thuộc lớp của phiên thi.
        $thuocLop = ThanhVienLop::query()
            ->where('lop_hoc_id', $phien->lop_hoc_id)
            ->where('sinh_vien_id', $student->nguoi_dung_id)
            ->exists();

        if (!$thuocLop) {
            return false;
        }

        // Giảng viên phụ trách lớp được giám sát.
        $laGiangVien = $phien->lopHoc
            && (int) $phien->lopHoc->giang_vien_id === (int) $user->id;

        if ($laGiangVien) {
            return true;
        }

        // Sinh viên chỉ được vào kênh của chính mình.
        if ((int) $student->nguoi_dung_id === (int) $user->id) {
            return true;
        }

        return false;
    }
);
