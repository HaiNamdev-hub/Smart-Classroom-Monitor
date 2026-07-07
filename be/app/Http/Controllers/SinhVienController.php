<?php

namespace App\Http\Controllers;

use App\Models\ChiTietDanhGia;
use App\Models\DotDanhGia;
use App\Models\MinhChung;
use App\Models\PhieuRenLuyen;
use App\Models\SinhVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SinhVienController extends Controller
{
    private function laySinhVienTheoTaiKhoan($user)
    {
        // Trường hợp 1: tài khoản có dòng trong bảng sinh_vien
        $sinhVien = SinhVien::join('nguoi_dung', 'sinh_vien.user_id', '=', 'nguoi_dung.id')
            ->join('lop', 'sinh_vien.lop_id', '=', 'lop.id')
            ->join('khoa', 'lop.khoa_id', '=', 'khoa.id')
            ->join('nganh', 'lop.nganh_id', '=', 'nganh.id')
            ->where('sinh_vien.user_id', $user->id)
            ->select(
                'sinh_vien.*',
                'nguoi_dung.ho_ten',
                'nguoi_dung.email',
                'nguoi_dung.so_dien_thoai',
                'nguoi_dung.ngay_sinh',
                'nguoi_dung.vai_tro_id',
                'nguoi_dung.trang_thai as trang_thai_user',
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

        if ($sinhVien) {
            return $sinhVien;
        }

        // Trường hợp 2: lớp trưởng/bí thư chỉ nằm trong bảng nguoi_dung
        // Và lop_truong_id / bi_thu_id đang lưu theo nguoi_dung.id
        $lop = DB::table('lop')
            ->join('khoa', 'lop.khoa_id', '=', 'khoa.id')
            ->join('nganh', 'lop.nganh_id', '=', 'nganh.id')
            ->where(function ($query) use ($user) {
                $query->where('lop.lop_truong_id', $user->id)
                    ->orWhere('lop.bi_thu_id', $user->id);
            })
            ->select(
                'lop.id as lop_id',
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

        if (!$lop) {
            return null;
        }

        return (object) [
            'id' => null,
            'ma_sinh_vien' => null,
            'nam_trung_tuyen' => null,
            'user_id' => $user->id,
            'lop_id' => $lop->lop_id,

            'ho_ten' => $user->ho_ten,
            'email' => $user->email,
            'so_dien_thoai' => $user->so_dien_thoai,
            'ngay_sinh' => $user->ngay_sinh,
            'vai_tro_id' => $user->vai_tro_id,
            'trang_thai_user' => $user->trang_thai,

            'ma_lop' => $lop->ma_lop,
            'ten_lop' => $lop->ten_lop,
            'lop_truong_id' => $lop->lop_truong_id,
            'bi_thu_id' => $lop->bi_thu_id,

            'ma_khoa' => $lop->ma_khoa,
            'ten_khoa' => $lop->ten_khoa,
            'ma_nganh' => $lop->ma_nganh,
            'ten_nganh' => $lop->ten_nganh,
        ];
    }
    public function getData(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Bạn chưa đăng nhập!'
            ], 401);
        }

        // Dùng hàm đã viết để lấy sinh viên hoặc cán sự lớp
        $sinhVien = $this->laySinhVienTheoTaiKhoan($user);

        if (!$sinhVien) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy thông tin sinh viên hoặc lớp quản lý của tài khoản này!'
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

        $phieu = null;
        $rows = [];

        // Nếu tài khoản có dòng trong bảng sinh_vien thì tạo/lấy phiếu cá nhân
        if ($sinhVien->id) {
            $phieu = PhieuRenLuyen::firstOrCreate(
                [
                    'sinh_vien_id' => $sinhVien->id,
                    'dot_danh_gia_id' => $dotDanhGia->id,
                ],
                [
                    'tong_diem_sv' => 0,
                    'tong_diem_lop' => 0,
                    'tong_diem_cuoi' => 0,
                    'xep_loai' => null,
                    'trang_thai' => 'nhap',
                ]
            );

            $rows = $this->buildEvaluationRows($phieu->id);
        } else {
            // Trường hợp lớp trưởng/bí thư chỉ có tài khoản nguoi_dung, chưa có dòng sinh_vien
            $phieu = [
                'id' => null,
                'sinh_vien_id' => null,
                'dot_danh_gia_id' => $dotDanhGia->id,
                'tong_diem_sv' => 0,
                'tong_diem_lop' => 0,
                'tong_diem_cuoi' => 0,
                'xep_loai' => null,
                'trang_thai' => 'can_su_lop',
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        $currentSinhVien = [
            'id' => $sinhVien->id,
            'ma_sinh_vien' => $sinhVien->ma_sinh_vien,
            'nam_trung_tuyen' => $sinhVien->nam_trung_tuyen,
            'user_id' => $sinhVien->user_id,
            'lop_id' => $sinhVien->lop_id,
            'nguoi_dung' => [
                'id' => $user->id,
                'ho_ten' => $sinhVien->ho_ten,
                'ngay_sinh' => $sinhVien->ngay_sinh,
                'so_dien_thoai' => $sinhVien->so_dien_thoai,
            ],
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
        ];

        return response()->json([
            'status' => true,
            'data' => [
                'current_user' => [
                    'id' => $user->id,
                    'ho_ten' => $sinhVien->ho_ten,
                    'email' => $sinhVien->email,
                    'so_dien_thoai' => $sinhVien->so_dien_thoai,
                    'ngay_sinh' => $sinhVien->ngay_sinh,
                    'vai_tro_id' => $sinhVien->vai_tro_id,
                    'trang_thai' => $sinhVien->trang_thai_user,
                ],

                // Trả cả 2 key để frontend nào gọi cũng không lỗi
                'sinh_vien' => $currentSinhVien,
                'current_sinh_vien' => $currentSinhVien,

                'dot_danh_gia' => $dotDanhGia,

                // Trả cả 2 key để khớp với trang phiếu và trang chủ
                'phieu' => $phieu,
                'phieu_hien_tai' => $phieu,

                'evaluation_rows' => $rows,

                // Cho trang chủ sinh viên không bị undefined
                'class_stats' => [
                    'total' => 0,
                    'waiting' => 0,
                    'done' => 0,
                ],

                'recent_activities' => [],
            ],
        ]);
    }
    public function luuNhap(Request $request)
    {
        return $this->savePhieu($request, 'nhap');
    }

    public function guiPhieu(Request $request)
    {
        return $this->savePhieu($request, 'da_gui');
    }

    private function savePhieu(Request $request, $trangThai)
    {
        $phieu = PhieuRenLuyen::find($request->phieu_id);

        if (!$phieu) {
            return response()->json([
                'status' => false,
                'message' => 'Phiếu không tồn tại!'
            ], 404);
        }

        if (!in_array($phieu->trang_thai, ['nhap', 'tra_lai'])) {
            return response()->json([
                'status' => false,
                'message' => 'Phiếu đã gửi, không thể chỉnh sửa!'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $tongDiem = 0;

            foreach ($request->chi_tiet_danh_gia as $item) {
                $diemSv = (int) ($item['diem_sv'] ?? 0);
                $tongDiem += $diemSv;

                ChiTietDanhGia::updateOrCreate(
                    [
                        'phieu_id' => $phieu->id,
                        'tieu_chi_id' => $item['tieu_chi_id'],
                    ],
                    [
                        'lua_chon_id' => $item['lua_chon_id'] ?? null,
                        'gia_tri_nhap' => $item['gia_tri_nhap'] ?? null,
                        'diem_sv' => $diemSv,
                        'diem_lop' => $item['diem_lop'] ?? 0,
                        'ghi_chu' => $item['ghi_chu'] ?? null,
                    ]
                );
            }

            $phieu->tong_diem_sv = min($tongDiem, 100);
            $phieu->tong_diem_cuoi = min($tongDiem, 100);
            $phieu->xep_loai = $this->xepLoai($phieu->tong_diem_sv);
            $phieu->trang_thai = $trangThai;
            $phieu->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => $trangThai === 'da_gui' ? 'Gửi phiếu thành công!' : 'Lưu nháp thành công!',
                'data' => $phieu,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Lỗi lưu phiếu!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadMinhChung(Request $request)
    {
        $request->validate([
            'chi_tiet_id' => 'required|exists:chi_tiet_danh_gia,id',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:10240',
        ]);

        $user = $request->user();
        $file = $request->file('file');

        $path = $file->store('minh-chung', 'public');

        $data = MinhChung::create([
            'chi_tiet_id' => $request->chi_tiet_id,
            'nguoi_tai_id' => $user->id,
            'ten_file' => $file->getClientOriginalName(),
            'duong_dan' => asset('storage/' . $path),
            'trang_thai' => 'cho_duyet',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Upload minh chứng thành công!',
            'data' => $data,
        ]);
    }

    public function xoaMinhChung(Request $request)
    {
        $data = MinhChung::find($request->id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Minh chứng không tồn tại!'
            ], 404);
        }

        $data->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa minh chứng thành công!'
        ]);
    }

    private function buildEvaluationRows($phieuId)
    {
        $groups = DB::table('nhom_tieu_chi')
            ->orderBy('thu_tu')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($groups as $group) {
            $rows[] = [
                'row_key' => 'nhom-' . $group->id,
                'row_type' => 'section',
                'nhom_id' => $group->id,
                'tt' => $group->ma_nhom,
                'ten_hien_thi' => $group->ten_nhom,
                'diem_toi_da_text' => 'Từ 0÷' . $group->diem_toi_da . ' điểm',
                'editable' => false,
            ];

            $criteria = DB::table('tieu_chi')
                ->where('nhom_id', $group->id)
                ->orderBy('thu_tu')
                ->orderBy('id')
                ->get();

            foreach ($criteria as $tc) {
                $detail = ChiTietDanhGia::where('phieu_id', $phieuId)
                    ->where('tieu_chi_id', $tc->id)
                    ->first();

                if (!$detail) {
                    $detail = ChiTietDanhGia::create([
                        'phieu_id' => $phieuId,
                        'tieu_chi_id' => $tc->id,
                        'diem_sv' => 0,
                        'diem_lop' => 0,
                    ]);
                }

                $luaChon = DB::table('tieu_chi_lua_chon')
                    ->where('tieu_chi_id', $tc->id)
                    ->orderBy('thu_tu')
                    ->orderBy('id')
                    ->get();

                $minhChung = MinhChung::where('chi_tiet_id', $detail->id)->get();

                $rows[] = [
                    'row_key' => 'ct-' . $tc->id,
                    'row_type' => 'criteria',
                    'chi_tiet_id' => $detail->id,
                    'phieu_id' => $phieuId,
                    'tieu_chi_id' => $tc->id,
                    'nhom_id' => $tc->nhom_id,
                    'parent_id' => $tc->parent_id,
                    'ma_tieu_chi' => $tc->ma_tieu_chi,
                    'tt' => $tc->ma_tieu_chi,
                    'ten_hien_thi' => $tc->ten_tieu_chi,
                    'diem_toi_da' => $tc->diem_toi_da,
                    'diem_toi_da_text' => (string) $tc->diem_toi_da,
                    'loai_tieu_chi' => $tc->loai_tieu_chi,
                    'loai_nhap' => $tc->loai_nhap,
                    'bat_buoc_minh_chung' => (bool) $tc->bat_buoc_minh_chung,
                    'lua_chon_id' => $detail->lua_chon_id,
                    'gia_tri_nhap' => $detail->gia_tri_nhap,
                    'diem_sv' => $detail->diem_sv,
                    'diem_lop' => $detail->diem_lop,
                    'ghi_chu' => $detail->ghi_chu,
                    'editable' => true,
                    'lua_chon' => $luaChon,
                    'minh_chung' => $minhChung,
                ];
            }
        }

        return $rows;
    }
    public function getKetQuaRenLuyen()
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Bạn chưa đăng nhập!'
            ], 401);
        }

        $sinhVien = SinhVien::with([
            'user',
            'lop.khoa',
            'lop.nganh',
        ])
            ->where('user_id', $user->id)
            ->first();

        if (!$sinhVien) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy thông tin sinh viên!'
            ], 404);
        }

        $ketQua = PhieuRenLuyen::with([
            'dotDanhGia'
        ])
            ->where('sinh_vien_id', $sinhVien->id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                $diemCuoi = $item->tong_diem_cuoi;

                if ($diemCuoi === null || $diemCuoi == 0) {
                    if (in_array($item->trang_thai, [
                        'lop_da_duyet',
                        'co_van_da_duyet',
                        'hoan_thanh'
                    ])) {
                        $diemCuoi = $item->tong_diem_lop;
                    } else {
                        $diemCuoi = $item->tong_diem_sv;
                    }
                }

                return [
                    'id'              => $item->id,
                    'sinh_vien_id'    => $item->sinh_vien_id,
                    'dot_danh_gia_id' => $item->dot_danh_gia_id,

                    'tong_diem_sv'    => $item->tong_diem_sv,
                    'tong_diem_lop'   => $item->tong_diem_lop,
                    'tong_diem_cuoi'  => $diemCuoi,

                    'xep_loai'        => $item->xep_loai ?: $this->xepLoai($diemCuoi),
                    'trang_thai'      => $item->trang_thai,

                    'created_at'      => $item->created_at,
                    'updated_at'      => $item->updated_at,

                    'dot_danh_gia'    => $item->dotDanhGia,
                ];
            });

        $latestResult = $ketQua->first();

        $completedCount = $ketQua
            ->where('trang_thai', 'hoan_thanh')
            ->count();

        $highestScore = $ketQua->max('tong_diem_cuoi');

        return response()->json([
            'status' => true,
            'message' => 'Lấy kết quả rèn luyện thành công!',

            'current_user' => [
                'id'             => $user->id,
                'ho_ten'         => $user->ho_ten,
                'email'          => $user->email,
                'so_dien_thoai'  => $user->so_dien_thoai,
                'ngay_sinh'      => $user->ngay_sinh,
                'vai_tro_id'     => $user->vai_tro_id,
                'trang_thai'     => $user->trang_thai,
            ],

            'current_sinh_vien' => $sinhVien,

            'ket_qua' => $ketQua,

            'thong_ke' => [
                'diem_hien_tai'        => $latestResult['tong_diem_cuoi'] ?? null,
                'xep_loai_hien_tai'    => $latestResult['xep_loai'] ?? null,
                'so_phieu_hoan_thanh'  => $completedCount,
                'diem_cao_nhat'        => $highestScore,
            ],
        ]);
    }
    private function layThongTinCanSuLop()
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return [
                'status'  => false,
                'message' => 'Bạn chưa đăng nhập!'
            ];
        }

        // Trường hợp 1: tài khoản có liên kết với bảng sinh_vien
        $sinhVien = DB::table('sinh_vien')
            ->join('lop', 'sinh_vien.lop_id', '=', 'lop.id')
            ->join('khoa', 'lop.khoa_id', '=', 'khoa.id')
            ->join('nganh', 'lop.nganh_id', '=', 'nganh.id')
            ->where('sinh_vien.user_id', $user->id)
            ->select(
                'sinh_vien.id',
                'sinh_vien.ma_sinh_vien',
                'sinh_vien.user_id',
                'sinh_vien.lop_id',

                'lop.id as id_lop',
                'lop.ma_lop',
                'lop.ten_lop',
                'lop.lop_truong_id',
                'lop.bi_thu_id',

                'khoa.id as khoa_id',
                'khoa.ma_khoa',
                'khoa.ten_khoa',

                'nganh.id as nganh_id',
                'nganh.ma_nganh',
                'nganh.ten_nganh'
            )
            ->first();

        if ($sinhVien) {
            $laLopTruong = (int) $sinhVien->lop_truong_id === (int) $user->id;
            $laBiThu     = (int) $sinhVien->bi_thu_id === (int) $user->id;

            if (!$laLopTruong && !$laBiThu) {
                return [
                    'status'  => false,
                    'message' => 'Bạn không phải lớp trưởng hoặc bí thư của lớp này!'
                ];
            }

            return [
                'status'         => true,
                'sinh_vien'      => $sinhVien,
                'lop_id'         => $sinhVien->lop_id,
                'sinh_vien_id'   => $sinhVien->id,
                'vai_tro_can_su' => $laLopTruong ? 'lop_truong' : 'bi_thu'
            ];
        }

        // Trường hợp 2: tài khoản lớp trưởng/bí thư chỉ nằm ở bảng nguoi_dung
        // và lop_truong_id / bi_thu_id đang lưu theo nguoi_dung.id
        $lop = DB::table('lop')
            ->join('khoa', 'lop.khoa_id', '=', 'khoa.id')
            ->join('nganh', 'lop.nganh_id', '=', 'nganh.id')
            ->where(function ($query) use ($user) {
                $query->where('lop.lop_truong_id', $user->id)
                    ->orWhere('lop.bi_thu_id', $user->id);
            })
            ->select(
                'lop.id as id_lop',
                'lop.ma_lop',
                'lop.ten_lop',
                'lop.lop_truong_id',
                'lop.bi_thu_id',

                'khoa.id as khoa_id',
                'khoa.ma_khoa',
                'khoa.ten_khoa',

                'nganh.id as nganh_id',
                'nganh.ma_nganh',
                'nganh.ten_nganh'
            )
            ->first();

        if (!$lop) {
            return [
                'status'  => false,
                'message' => 'Không tìm thấy lớp quản lý của tài khoản này!'
            ];
        }

        $sinhVienGiaLap = (object) [
            'id'             => null,
            'ma_sinh_vien'   => null,
            'user_id'        => $user->id,
            'lop_id'         => $lop->id_lop,

            'id_lop'         => $lop->id_lop,
            'ma_lop'         => $lop->ma_lop,
            'ten_lop'        => $lop->ten_lop,
            'lop_truong_id'  => $lop->lop_truong_id,
            'bi_thu_id'      => $lop->bi_thu_id,

            'khoa_id'        => $lop->khoa_id,
            'ma_khoa'        => $lop->ma_khoa,
            'ten_khoa'       => $lop->ten_khoa,

            'nganh_id'       => $lop->nganh_id,
            'ma_nganh'       => $lop->ma_nganh,
            'ten_nganh'      => $lop->ten_nganh,
        ];

        return [
            'status'         => true,
            'sinh_vien'      => $sinhVienGiaLap,
            'lop_id'         => $lop->id_lop,
            'sinh_vien_id'   => null,
            'vai_tro_can_su' => (int) $lop->lop_truong_id === (int) $user->id ? 'lop_truong' : 'bi_thu'
        ];
    }

    public function getPhieuCanSuLop()
    {
        $check = $this->layThongTinCanSuLop();

        if (!$check['status']) {
            return response()->json([
                'status'  => false,
                'message' => $check['message']
            ], 403);
        }

        $data = DB::table('phieu_ren_luyen')
            ->join('sinh_vien', 'phieu_ren_luyen.sinh_vien_id', '=', 'sinh_vien.id')
            ->join('nguoi_dung', 'sinh_vien.user_id', '=', 'nguoi_dung.id')
            ->join('lop', 'sinh_vien.lop_id', '=', 'lop.id')
            ->join('khoa', 'lop.khoa_id', '=', 'khoa.id')
            ->join('nganh', 'lop.nganh_id', '=', 'nganh.id')
            ->join('dot_danh_gia', 'phieu_ren_luyen.dot_danh_gia_id', '=', 'dot_danh_gia.id')
            ->where('sinh_vien.lop_id', $check['lop_id'])
            ->when($check['sinh_vien_id'], function ($query) use ($check) {
                $query->where('sinh_vien.id', '!=', $check['sinh_vien_id']);
            })
            ->whereIn('phieu_ren_luyen.trang_thai', [
                'da_gui',
                'lop_da_duyet',
                'co_van_da_duyet',
                'hoan_thanh',
            ])
            ->select(
                'phieu_ren_luyen.id',
                'phieu_ren_luyen.sinh_vien_id',
                'phieu_ren_luyen.dot_danh_gia_id',
                'phieu_ren_luyen.tong_diem_sv',
                'phieu_ren_luyen.tong_diem_lop',
                'phieu_ren_luyen.tong_diem_cuoi',
                'phieu_ren_luyen.xep_loai',
                'phieu_ren_luyen.trang_thai',
                'phieu_ren_luyen.created_at',
                'phieu_ren_luyen.updated_at',

                'sinh_vien.ma_sinh_vien',
                'sinh_vien.nam_trung_tuyen',

                'nguoi_dung.ho_ten',
                'nguoi_dung.email',
                'nguoi_dung.ngay_sinh',
                'nguoi_dung.so_dien_thoai',

                'lop.ma_lop',
                'lop.ten_lop',

                'khoa.ten_khoa',
                'nganh.ten_nganh',

                'dot_danh_gia.nam_hoc',
                'dot_danh_gia.hoc_ky',
                'dot_danh_gia.ngay_bat_dau',
                'dot_danh_gia.ngay_ket_thuc'
            )
            ->orderByDesc('phieu_ren_luyen.id')
            ->get();

        return response()->json([
            'status'         => true,
            'message'        => 'Lấy danh sách phiếu sinh viên trong lớp thành công!',
            'vai_tro_can_su' => $check['vai_tro_can_su'],
            'lop' => [
                'id'      => $check['sinh_vien']->id_lop,
                'ma_lop'  => $check['sinh_vien']->ma_lop,
                'ten_lop' => $check['sinh_vien']->ten_lop,

                'khoa' => [
                    'id'       => $check['sinh_vien']->khoa_id,
                    'ma_khoa'  => $check['sinh_vien']->ma_khoa,
                    'ten_khoa' => $check['sinh_vien']->ten_khoa,
                ],

                'nganh' => [
                    'id'        => $check['sinh_vien']->nganh_id,
                    'ma_nganh'  => $check['sinh_vien']->ma_nganh,
                    'ten_nganh' => $check['sinh_vien']->ten_nganh,
                ],
            ],
            'thong_ke'       => [
                'tong_phieu'      => $data->count(),
                'da_gui'          => $data->where('trang_thai', 'da_gui')->count(),
                'lop_da_duyet'    => $data->where('trang_thai', 'lop_da_duyet')->count(),
                'co_van_da_duyet' => $data->where('trang_thai', 'co_van_da_duyet')->count(),
                'hoan_thanh'      => $data->where('trang_thai', 'hoan_thanh')->count(),
            ],
            'data' => $data
        ]);
    }

    public function chiTietPhieuCanSuLop(Request $request)
    {
        $check = $this->layThongTinCanSuLop();

        if (!$check['status']) {
            return response()->json([
                'status'  => false,
                'message' => $check['message']
            ], 403);
        }

        $phieu = DB::table('phieu_ren_luyen')
            ->join('sinh_vien', 'phieu_ren_luyen.sinh_vien_id', '=', 'sinh_vien.id')
            ->join('nguoi_dung', 'sinh_vien.user_id', '=', 'nguoi_dung.id')
            ->join('lop', 'sinh_vien.lop_id', '=', 'lop.id')
            ->join('khoa', 'lop.khoa_id', '=', 'khoa.id')
            ->join('nganh', 'lop.nganh_id', '=', 'nganh.id')
            ->join('dot_danh_gia', 'phieu_ren_luyen.dot_danh_gia_id', '=', 'dot_danh_gia.id')
            ->where('phieu_ren_luyen.id', $request->id)
            ->where('sinh_vien.lop_id', $check['lop_id'])
            ->when($check['sinh_vien_id'], function ($query) use ($check) {
                $query->where('sinh_vien.id', '!=', $check['sinh_vien_id']);
            })
            ->select(
                'phieu_ren_luyen.id',
                'phieu_ren_luyen.sinh_vien_id',
                'phieu_ren_luyen.dot_danh_gia_id',
                'phieu_ren_luyen.tong_diem_sv',
                'phieu_ren_luyen.tong_diem_lop',
                'phieu_ren_luyen.tong_diem_cuoi',
                'phieu_ren_luyen.xep_loai',
                'phieu_ren_luyen.trang_thai',

                'sinh_vien.ma_sinh_vien',
                'sinh_vien.nam_trung_tuyen',

                'nguoi_dung.ho_ten',
                'nguoi_dung.email',
                'nguoi_dung.ngay_sinh',
                'nguoi_dung.so_dien_thoai',

                'lop.ma_lop',
                'lop.ten_lop',

                'khoa.ten_khoa',
                'nganh.ten_nganh',

                'dot_danh_gia.nam_hoc',
                'dot_danh_gia.hoc_ky',
                'dot_danh_gia.ngay_bat_dau',
                'dot_danh_gia.ngay_ket_thuc'
            )
            ->first();

        if (!$phieu) {
            return response()->json([
                'status'  => false,
                'message' => 'Phiếu không tồn tại hoặc không thuộc lớp của bạn!'
            ], 404);
        }

        $chiTiet = DB::table('chi_tiet_danh_gia')
            ->join('tieu_chi', 'chi_tiet_danh_gia.tieu_chi_id', '=', 'tieu_chi.id')
            ->leftJoin('tieu_chi_lua_chon', 'chi_tiet_danh_gia.lua_chon_id', '=', 'tieu_chi_lua_chon.id')
            ->where('chi_tiet_danh_gia.phieu_id', $phieu->id)
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
            'status'         => true,
            'message'        => 'Lấy chi tiết phiếu thành công!',
            'vai_tro_can_su' => $check['vai_tro_can_su'],
            'phieu'          => $phieu,
            'chi_tiet'       => $chiTiet
        ]);
    }

    public function danhGiaPhieuCanSuLop(Request $request)
    {
        return response()->json([
            'status'  => false,
            'message' => 'Lớp trưởng/Bí thư chỉ được xem điểm lớp, không được đánh giá hoặc sửa điểm.'
        ], 403);
    }

    private function xepLoai($score)
    {
        if ($score >= 90) return 'xuat_sac';
        if ($score >= 80) return 'tot';
        if ($score >= 65) return 'kha';
        if ($score >= 50) return 'trung_binh';
        if ($score >= 35) return 'yeu';

        return 'kem';
    }
}
