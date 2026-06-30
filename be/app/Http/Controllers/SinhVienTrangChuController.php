<?php

namespace App\Http\Controllers;

use App\Models\DotDanhGia;
use App\Models\Lop;
use App\Models\PhieuRenLuyen;
use App\Models\SinhVien;
use Illuminate\Http\Request;

class SinhVienTrangChuController extends Controller
{
    public function getData(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn!'
            ], 401);
        }

        $sinhVien = SinhVien::join('lop', 'sinh_vien.lop_id', '=', 'lop.id')
            ->join('khoa', 'lop.khoa_id', '=', 'khoa.id')
            ->join('nganh', 'lop.nganh_id', '=', 'nganh.id')
            ->where('sinh_vien.user_id', $user->id)
            ->select(
                'sinh_vien.*',
                'lop.ma_lop',
                'lop.ten_lop',
                'lop.lop_truong_id',
                'lop.bi_thu_id',
                'khoa.ma_khoa',
                'khoa.ten_khoa',
                'nganh.ma_nganh',
                'nganh.ten_nganh'
            )
            ->first();

        if (!$sinhVien) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy thông tin sinh viên!'
            ], 404);
        }

        $dotDanhGia = DotDanhGia::where('trang_thai', 'dang_mo')
            ->orderByDesc('id')
            ->first();

        if (!$dotDanhGia) {
            return response()->json([
                'status' => false,
                'message' => 'Hiện chưa có đợt đánh giá đang mở!'
            ]);
        }

        $phieu = PhieuRenLuyen::where('sinh_vien_id', $sinhVien->id)
            ->where('dot_danh_gia_id', $dotDanhGia->id)
            ->first();

        if (!$phieu) {
            $phieu = PhieuRenLuyen::create([
                'sinh_vien_id'     => $sinhVien->id,
                'dot_danh_gia_id'  => $dotDanhGia->id,
                'tong_diem_sv'     => 0,
                'tong_diem_lop'    => 0,
                'tong_diem_cuoi'   => 0,
                'xep_loai'         => null,
                'trang_thai'       => 'nhap',
            ]);
        }

        $classStats = [
            'total' => 0,
            'waiting' => 0,
            'done' => 0,
        ];

        $isOfficer = in_array($user->vai_tro_id, [4, 5])
            && (
                $sinhVien->id == $sinhVien->lop_truong_id ||
                $sinhVien->id == $sinhVien->bi_thu_id
            );

        if ($isOfficer) {
            $sinhVienIds = SinhVien::where('lop_id', $sinhVien->lop_id)->pluck('id');

            $classStats = [
                'total' => PhieuRenLuyen::whereIn('sinh_vien_id', $sinhVienIds)
                    ->where('dot_danh_gia_id', $dotDanhGia->id)
                    ->count(),

                'waiting' => PhieuRenLuyen::whereIn('sinh_vien_id', $sinhVienIds)
                    ->where('dot_danh_gia_id', $dotDanhGia->id)
                    ->where('trang_thai', 'da_gui')
                    ->count(),

                'done' => PhieuRenLuyen::whereIn('sinh_vien_id', $sinhVienIds)
                    ->where('dot_danh_gia_id', $dotDanhGia->id)
                    ->whereIn('trang_thai', [
                        'lop_da_danh_gia',
                        'co_van_da_kiem_tra',
                        'hoan_thanh'
                    ])
                    ->count(),
            ];
        }

        $recentActivities = PhieuRenLuyen::where('sinh_vien_id', $sinhVien->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $this->activityType($item->trang_thai),
                    'icon' => $this->activityIcon($item->trang_thai),
                    'title' => $this->activityTitle($item->trang_thai),
                    'time' => $item->updated_at
                        ? $item->updated_at->format('d/m/Y')
                        : '',
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'current_user' => [
                    'id' => $user->id,
                    'ho_ten' => $user->ho_ten,
                    'email' => $user->email,
                    'so_dien_thoai' => $user->so_dien_thoai,
                    'ngay_sinh' => $user->ngay_sinh,
                    'vai_tro_id' => $user->vai_tro_id,
                    'trang_thai' => $user->trang_thai,
                ],

                'current_sinh_vien' => [
                    'id' => $sinhVien->id,
                    'ma_sinh_vien' => $sinhVien->ma_sinh_vien,
                    'nam_trung_tuyen' => $sinhVien->nam_trung_tuyen,
                    'user_id' => $sinhVien->user_id,
                    'lop_id' => $sinhVien->lop_id,
                    'lop' => [
                        'id' => $sinhVien->lop_id,
                        'ma_lop' => $sinhVien->ma_lop,
                        'ten_lop' => $sinhVien->ten_lop,
                        'lop_truong_id' => $sinhVien->lop_truong_id,
                        'bi_thu_id' => $sinhVien->bi_thu_id,
                        'khoa' => [
                            'ma_khoa' => $sinhVien->ma_khoa,
                            'ten_khoa' => $sinhVien->ten_khoa,
                        ],
                        'nganh' => [
                            'ma_nganh' => $sinhVien->ma_nganh,
                            'ten_nganh' => $sinhVien->ten_nganh,
                        ],
                    ],
                ],

                'dot_danh_gia' => $dotDanhGia,

                'phieu_hien_tai' => $phieu,

                'class_stats' => $classStats,

                'recent_activities' => $recentActivities,
            ],
        ]);
    }

    private function activityTitle($trangThai)
    {
        return match ($trangThai) {
            'nhap' => 'Bạn đang nhập phiếu rèn luyện',
            'da_gui' => 'Bạn đã gửi phiếu tự đánh giá',
            'lop_da_danh_gia' => 'Lớp đã đánh giá phiếu của bạn',
            'co_van_da_kiem_tra' => 'Cố vấn đã kiểm tra phiếu',
            'hoan_thanh' => 'Phiếu rèn luyện đã hoàn thành',
            'tra_lai' => 'Phiếu bị trả lại cần chỉnh sửa',
            default => 'Phiếu rèn luyện được cập nhật',
        };
    }

    private function activityType($trangThai)
    {
        return match ($trangThai) {
            'hoan_thanh', 'co_van_da_kiem_tra' => 'green',
            'da_gui', 'lop_da_danh_gia' => 'blue',
            'tra_lai' => 'red',
            default => 'amber',
        };
    }

    private function activityIcon($trangThai)
    {
        return match ($trangThai) {
            'nhap' => 'fa-solid fa-file-pen',
            'da_gui' => 'fa-solid fa-paper-plane',
            'lop_da_danh_gia' => 'fa-solid fa-users',
            'co_van_da_kiem_tra' => 'fa-solid fa-user-check',
            'hoan_thanh' => 'fa-solid fa-circle-check',
            'tra_lai' => 'fa-solid fa-rotate-left',
            default => 'fa-solid fa-clock',
        };
    }
}
