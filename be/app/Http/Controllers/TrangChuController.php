<?php

namespace App\Http\Controllers;

use App\Models\DotDanhGia;
use App\Models\Khoa;
use App\Models\Lop;
use App\Models\NguoiDung;
use App\Models\PhanQuyen;
use App\Models\PhieuRenLuyen;
use App\Models\SinhVien;
use Illuminate\Support\Facades\Auth;

class TrangChuController extends Controller
{
    private function kiemTraQuyen($ma_chuc_nang)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return false;
        }

        if ($user->vai_tro_id == 1) {
            return true;
        }

        return PhanQuyen::join('chuc_nang', 'chuc_nang.id', '=', 'phan_quyen.chuc_nang_id')
            ->where('phan_quyen.vai_tro_id', $user->vai_tro_id)
            ->where('chuc_nang.ma_chuc_nang', $ma_chuc_nang)
            ->exists();
    }

    private function khongCoQuyen()
    {
        return response()->json([
            'status'  => false,
            'message' => 'Bạn không có quyền thực hiện chức năng này!'
        ], 403);
    }

    public function getData()
    {
        

        $tongSinhVien = SinhVien::count();
        $tongKhoa = Khoa::count();
        $tongLop = Lop::count();
        $tongDotDanhGia = DotDanhGia::count();

        $daNop = PhieuRenLuyen::whereIn('trang_thai', [
            'da_gui',
            'lop_da_danh_gia',
            'co_van_da_kiem_tra',
            'hoan_thanh',
        ])->count();

        $chuaNop = max($tongSinhVien - $daNop, 0);

        $daDuyet = PhieuRenLuyen::whereIn('trang_thai', [
            'co_van_da_kiem_tra',
            'hoan_thanh',
        ])->count();

        $choDuyet = PhieuRenLuyen::whereIn('trang_thai', [
            'da_gui',
            'lop_da_danh_gia',
        ])->count();

        $traLai = PhieuRenLuyen::where('trang_thai', 'tra_lai')->count();

        $sinhVienTheoKhoa = Khoa::leftJoin('lop', 'khoa.id', '=', 'lop.khoa_id')
            ->leftJoin('sinh_vien', 'lop.id', '=', 'sinh_vien.lop_id')
            ->selectRaw('khoa.ten_khoa, COUNT(sinh_vien.id) as so_luong')
            ->groupBy('khoa.id', 'khoa.ten_khoa')
            ->orderBy('khoa.id')
            ->get();

        $activities = PhieuRenLuyen::join('sinh_vien', 'phieu_ren_luyen.sinh_vien_id', '=', 'sinh_vien.id')
            ->join('nguoi_dung', 'sinh_vien.user_id', '=', 'nguoi_dung.id')
            ->select(
                'nguoi_dung.ho_ten',
                'phieu_ren_luyen.trang_thai',
                'phieu_ren_luyen.updated_at'
            )
            ->orderByDesc('phieu_ren_luyen.updated_at')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->ho_ten,
                    'initials' => mb_substr($item->ho_ten, 0, 1),
                    'action' => $this->formatAction($item->trang_thai),
                    'type' => $this->formatType($item->trang_thai),
                    'time' => $item->updated_at ? $item->updated_at->diffForHumans() : '',
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'top_stats' => [
                    'sinh_vien' => $tongSinhVien,
                    'khoa' => $tongKhoa,
                    'lop' => $tongLop,
                    'dot_danh_gia' => $tongDotDanhGia,
                ],
                'form_stats' => [
                    'da_nop' => $daNop,
                    'chua_nop' => $chuaNop,
                    'da_duyet' => $daDuyet,
                    'cho_duyet' => $choDuyet,
                    'tra_lai' => $traLai,
                ],
                'sinh_vien_theo_khoa' => $sinhVienTheoKhoa,
                'activities' => $activities,
                'notifications' => [
                    [
                        'title' => 'Có ' . $choDuyet . ' phiếu chờ duyệt',
                        'desc' => 'Cần cố vấn học tập xử lý',
                        'color' => '#f59e0b',
                    ],
                    [
                        'title' => 'Có ' . $chuaNop . ' sinh viên chưa nộp',
                        'desc' => 'Cần nhắc nhở sinh viên trước hạn',
                        'color' => '#ef4444',
                    ],
                    [
                        'title' => 'Hệ thống đang hoạt động',
                        'desc' => 'Dữ liệu dashboard đã được cập nhật',
                        'color' => '#3b82f6',
                    ],
                ],
            ],
        ]);
    }

    private function formatAction($trangThai)
    {
        return match ($trangThai) {
            'nhap' => 'Đang nhập',
            'da_gui' => 'Nộp phiếu',
            'lop_da_danh_gia' => 'Lớp đã đánh giá',
            'co_van_da_kiem_tra' => 'Cố vấn đã kiểm tra',
            'hoan_thanh' => 'Đã duyệt',
            'tra_lai' => 'Trả lại',
            default => 'Cập nhật',
        };
    }

    private function formatType($trangThai)
    {
        return match ($trangThai) {
            'hoan_thanh', 'co_van_da_kiem_tra' => 'green',
            'da_gui', 'lop_da_danh_gia' => 'blue',
            'tra_lai' => 'red',
            default => 'amber',
        };
    }
}