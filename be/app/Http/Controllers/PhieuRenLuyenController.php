<?php

namespace App\Http\Controllers;

use App\Models\PhanQuyen;
use App\Models\PhieuRenLuyen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PhieuRenLuyenController extends Controller
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

    public function getPhieuChoDuyet()
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $data = $this->queryPhieu()
            ->whereIn('phieu_ren_luyen.trang_thai', [
                'da_gui',
                'lop_da_duyet',
                'co_van_da_duyet',
                'hoan_thanh',
            ])
            ->orderByDesc('phieu_ren_luyen.id')
            ->get();

        return response()->json([
            'status'   => true,
            'thong_ke' => $this->thongKe(),
            'data'     => $data
        ]);
    }

    public function chiTietPhieu(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $phieu = $this->queryPhieu()
            ->where('phieu_ren_luyen.id', $request->id)
            ->first();

        if (!$phieu) {
            return response()->json([
                'status'  => false,
                'message' => 'Phiếu không tồn tại!'
            ], 404);
        }

        $chiTiet = DB::table('chi_tiet_danh_gia')
            ->join('tieu_chi', 'chi_tiet_danh_gia.tieu_chi_id', '=', 'tieu_chi.id')
            ->leftJoin('tieu_chi_lua_chon', 'chi_tiet_danh_gia.lua_chon_id', '=', 'tieu_chi_lua_chon.id')
            ->where('chi_tiet_danh_gia.phieu_id', $request->id)
            ->select(
                'chi_tiet_danh_gia.id',
                'chi_tiet_danh_gia.phieu_id',
                'chi_tiet_danh_gia.tieu_chi_id',
                'chi_tiet_danh_gia.lua_chon_id',
                'chi_tiet_danh_gia.gia_tri_nhap',
                'chi_tiet_danh_gia.diem_sv',
                'chi_tiet_danh_gia.diem_lop',
                'chi_tiet_danh_gia.ghi_chu',
                'tieu_chi.ma_tieu_chi',
                'tieu_chi.ten_tieu_chi',
                'tieu_chi.diem_toi_da',
                'tieu_chi.loai_tieu_chi',
                'tieu_chi.loai_nhap',
                'tieu_chi.bat_buoc_minh_chung',
                'tieu_chi_lua_chon.noi_dung as lua_chon'
            )
            ->orderBy('tieu_chi.thu_tu', 'ASC')
            ->orderBy('tieu_chi.id', 'ASC')
            ->get();

        foreach ($chiTiet as $item) {
            $item->minh_chung = DB::table('minh_chung')
                ->where('chi_tiet_id', $item->id)
                ->select(
                    'id',
                    'chi_tiet_id',
                    'ten_file',
                    'duong_dan',
                    'trang_thai',
                    'created_at'
                )
                ->get()
                ->map(function ($file) {
                    if ($file->duong_dan && !str_starts_with($file->duong_dan, 'http')) {
                        $file->duong_dan = asset('storage/' . $file->duong_dan);
                    }

                    return $file;
                });
        }

        return response()->json([
            'status'   => true,
            'phieu'    => $phieu,
            'chi_tiet' => $chiTiet
        ]);
    }

    public function duyetPhieu(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $phieu = PhieuRenLuyen::find($request->id);

        if (!$phieu) {
            return response()->json([
                'status'  => false,
                'message' => 'Phiếu không tồn tại!'
            ], 404);
        }

        $tongDiemLop = DB::table('chi_tiet_danh_gia')
            ->where('phieu_id', $phieu->id)
            ->sum('diem_lop');

        if ($tongDiemLop <= 0) {
            $tongDiemLop = $phieu->tong_diem_sv;
        }

        $tongDiemLop = min($tongDiemLop, 100);

        $phieu->tong_diem_lop  = $tongDiemLop;
        $phieu->tong_diem_cuoi = $tongDiemLop;
        $phieu->xep_loai       = $this->xepLoai($tongDiemLop);
        $phieu->trang_thai     = 'co_van_da_duyet';
        $phieu->save();

        DB::table('lich_su_kiem_tra')->insert([
            'phieu_id'          => $phieu->id,
            'nguoi_kiem_tra_id' => Auth::id(),
            'vai_tro_kiem_tra'  => 'co_van',
            'hanh_dong'         => 'xac_nhan',
            'ghi_chu'           => $request->ghi_chu,
            'created_at'        => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Duyệt phiếu thành công!'
        ]);
    }

    public function traLaiPhieu(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $phieu = PhieuRenLuyen::find($request->id);

        if (!$phieu) {
            return response()->json([
                'status'  => false,
                'message' => 'Phiếu không tồn tại!'
            ], 404);
        }

        // Database hiện tại chưa có trạng thái "tra_lai",
        // nên tạm đưa phiếu về trạng thái "da_gui".
        $phieu->trang_thai = 'da_gui';
        $phieu->save();

        DB::table('lich_su_kiem_tra')->insert([
            'phieu_id'          => $phieu->id,
            'nguoi_kiem_tra_id' => Auth::id(),
            'vai_tro_kiem_tra'  => 'co_van',
            'hanh_dong'         => 'tra_lai',
            'ghi_chu'           => $request->ghi_chu,
            'created_at'        => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Trả lại phiếu thành công!'
        ]);
    }

    public function timkiem(Request $request)
    {
        if (!$this->kiemTraQuyen('quan_ly_tieu_chi')) {
            return $this->khongCoQuyen();
        }

        $noiDung = $request->noi_dung;

        $data = $this->queryPhieu()
            ->whereIn('phieu_ren_luyen.trang_thai', [
                'da_gui',
                'lop_da_duyet',
                'co_van_da_duyet',
                'hoan_thanh',
            ])
            ->where(function ($query) use ($noiDung) {
                $query->where('sinh_vien.ma_sinh_vien', 'like', '%' . $noiDung . '%')
                    ->orWhere('nguoi_dung.ho_ten', 'like', '%' . $noiDung . '%')
                    ->orWhere('lop.ten_lop', 'like', '%' . $noiDung . '%')
                    ->orWhere('nganh.ten_nganh', 'like', '%' . $noiDung . '%')
                    ->orWhere('dot_danh_gia.nam_hoc', 'like', '%' . $noiDung . '%')
                    ->orWhere('phieu_ren_luyen.trang_thai', 'like', '%' . $noiDung . '%');
            })
            ->orderByDesc('phieu_ren_luyen.id')
            ->get();

        return response()->json([
            'status'   => true,
            'thong_ke' => $this->thongKe(),
            'data'     => $data
        ]);
    }

    private function queryPhieu()
    {
        return PhieuRenLuyen::join('sinh_vien', 'phieu_ren_luyen.sinh_vien_id', '=', 'sinh_vien.id')
            ->join('nguoi_dung', 'sinh_vien.user_id', '=', 'nguoi_dung.id')
            ->join('lop', 'sinh_vien.lop_id', '=', 'lop.id')
            ->join('khoa', 'lop.khoa_id', '=', 'khoa.id')
            ->join('nganh', 'lop.nganh_id', '=', 'nganh.id')
            ->join('dot_danh_gia', 'phieu_ren_luyen.dot_danh_gia_id', '=', 'dot_danh_gia.id')
            ->select(
                'phieu_ren_luyen.id',
                'sinh_vien.ma_sinh_vien',
                'sinh_vien.nam_trung_tuyen',
                'nguoi_dung.ho_ten',
                'nguoi_dung.ngay_sinh',
                'nguoi_dung.so_dien_thoai',
                'khoa.ten_khoa as khoa',
                'lop.ten_lop',
                'nganh.ten_nganh',
                'dot_danh_gia.hoc_ky',
                'dot_danh_gia.nam_hoc',
                'phieu_ren_luyen.updated_at as ngay_nop',
                'phieu_ren_luyen.tong_diem_sv',
                'phieu_ren_luyen.tong_diem_lop',
                'phieu_ren_luyen.tong_diem_cuoi',
                'phieu_ren_luyen.xep_loai',
                'phieu_ren_luyen.trang_thai'
            );
    }

    private function thongKe()
    {
        return [
            'tong_phieu' => PhieuRenLuyen::whereIn('trang_thai', [
                'da_gui',
                'lop_da_duyet',
                'co_van_da_duyet',
                'hoan_thanh',
            ])->count(),

            'nhap'            => PhieuRenLuyen::where('trang_thai', 'nhap')->count(),
            'da_gui'          => PhieuRenLuyen::where('trang_thai', 'da_gui')->count(),
            'lop_da_duyet'    => PhieuRenLuyen::where('trang_thai', 'lop_da_duyet')->count(),
            'co_van_da_duyet' => PhieuRenLuyen::where('trang_thai', 'co_van_da_duyet')->count(),
            'hoan_thanh'      => PhieuRenLuyen::where('trang_thai', 'hoan_thanh')->count(),
        ];
    }

    private function xepLoai($score)
    {
        if ($score >= 90) {
            return 'xuat_sac';
        }

        if ($score >= 80) {
            return 'tot';
        }

        if ($score >= 65) {
            return 'kha';
        }

        if ($score >= 50) {
            return 'trung_binh';
        }

        if ($score >= 35) {
            return 'yeu';
        }

        return 'kem';
    }
}