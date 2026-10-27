<?php

namespace App\Http\Controllers;

use App\Models\AnhManHinh;
use App\Models\BaiNop;
use App\Models\BaiTap;
use App\Models\CanhBao;
use App\Models\Diem;
use App\Models\HoatDong;
use App\Models\LopHoc;
use App\Models\PhienGiamSat;
use App\Models\ThanhVienLop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class GiangVienApiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA QUYỀN GIẢNG VIÊN
    |--------------------------------------------------------------------------
    */

    private function classIds(Request $request)
    {
        return LopHoc::where(
            'giang_vien_id',
            $request->user()->id
        )->select('id');
    }

    private function ownClass(
        Request $request,
        int $id
    ): LopHoc {
        return LopHoc::where(
            'giang_vien_id',
            $request->user()->id
        )->findOrFail($id);
    }

    private function ownAssignment(
        Request $request,
        int $id
    ): BaiTap {
        return BaiTap::whereIn(
            'lop_hoc_id',
            $this->classIds($request)
        )->findOrFail($id);
    }

    private function ownSession(
        Request $request,
        int $id
    ): PhienGiamSat {
        return PhienGiamSat::whereIn(
            'lop_hoc_id',
            $this->classIds($request)
        )->findOrFail($id);
    }

    private function listData($data): JsonResponse
    {
        return response()->json([
            'data' => $data
        ]);
    }

    private function dateValue($date): ?string
    {
        return $date?->toIso8601String();
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function dashboard(
        Request $request
    ): JsonResponse {
        $classes = $this->classIds($request);

        $students = ThanhVienLop::whereIn(
            'lop_hoc_id',
            $this->classIds($request)
        )
            ->where('trang_thai', 'dang_hoc')
            ->distinct()
            ->count('sinh_vien_id');

        $sessions = PhienGiamSat::whereIn(
            'lop_hoc_id',
            $this->classIds($request)
        )
            ->where('trang_thai', 'dang_dien_ra')
            ->count();

        $alerts = CanhBao::whereHas(
            'phien',
            function ($q) use ($request) {
                $q->whereIn(
                    'lop_hoc_id',
                    $this->classIds($request)
                );
            }
        )
            ->where('da_xu_ly', false)
            ->count();

        return response()->json([
            'data' => [
                'tongSoLop' => $classes->count(),
                'tongSinhVien' => $students,
                'sinhVienOnline' => null,
                'phienDangChay' => $sessions,
                'canhBaoMoi' => $alerts
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH LỚP HỌC
    |--------------------------------------------------------------------------
    */

    public function lopHoc(
        Request $request
    ): JsonResponse {
        $classes = LopHoc::where(
            'giang_vien_id',
            $request->user()->id
        )
            ->withCount([
                'thanhViens as si_so' => function ($q) {
                    $q->where('trang_thai', 'dang_hoc');
                }
            ])
            ->orderBy('id')
            ->get();

        return $this->listData(
            $classes->map(fn($c) => [
                'id' => $c->id,
                'tenLop' => $c->ten_lop,
                'maHocPhan' => $c->ma_lop,
                'siSo' => $c->si_so,
                'phongHoc' => null
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | THÀNH VIÊN LỚP
    |--------------------------------------------------------------------------
    */


    public function thanhVien(
        Request $request,
        int $lopId
    ): JsonResponse {

        // Kiểm tra lớp thuộc giảng viên đang đăng nhập
        $this->ownClass($request, $lopId);

        // Lấy thành viên cùng thông tin người dùng
        $members = ThanhVienLop::where(
            'lop_hoc_id',
            $lopId
        )
            ->with([
                'sinhVien:id,nguoi_dung_id,ma_sinh_vien',
                'sinhVien.nguoiDung:id,ho_ten,email'
            ])
            ->get();

        return $this->listData(
            $members->map(fn($m) => [
                'id' => $m->id,
                'ma_sinh_vien' =>
                $m->sinhVien?->ma_sinh_vien,
                'ho_ten' =>
                $m->sinhVien?->nguoiDung?->ho_ten,
                'email' =>
                $m->sinhVien?->nguoiDung?->email,
                'trang_thai' => $m->trang_thai,
                'trang_thai_agent' => null
            ])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH BÀI TẬP
    |--------------------------------------------------------------------------
    */

    public function baiTap(
        Request $request
    ): JsonResponse {
        $filters = $request->validate([
            'lopId' => ['nullable', 'integer']
        ]);

        if (!empty($filters['lopId'])) {
            $this->ownClass(
                $request,
                (int) $filters['lopId']
            );
        }

        $query = BaiTap::whereIn(
            'lop_hoc_id',
            $this->classIds($request)
        )->withCount('baiNops');

        if (!empty($filters['lopId'])) {
            $query->where(
                'lop_hoc_id',
                $filters['lopId']
            );
        }

        return $this->listData(
            $query->latest('id')
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'lopId' => $a->lop_hoc_id,
                    'tieuDe' => $a->tieu_de,
                    'moTa' => $a->mo_ta,
                    'hanNop' => $this->dateValue(
                        $a->han_nop
                    ),
                    'daNop' => $a->bai_nops_count
                ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TẠO BÀI TẬP
    |--------------------------------------------------------------------------
    */

    public function createBaiTap(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'lopId' => ['required', 'integer'],
            'tieuDe' => [
                'required',
                'string',
                'max:255'
            ],
            'moTa' => ['nullable', 'string'],
            'hanNop' => [
                'required',
                'date',
                'after:now'
            ]
        ]);

        $this->ownClass(
            $request,
            (int) $data['lopId']
        );

        $item = BaiTap::create([
            'lop_hoc_id' => $data['lopId'],
            'nguoi_tao_id' => $request->user()->id,
            'tieu_de' => $data['tieuDe'],
            'mo_ta' => $data['moTa'] ?? null,
            'thoi_gian_bat_dau' => now(),
            'thoi_gian_ket_thuc' => $data['hanNop'],
            'han_nop' => $data['hanNop'],
            'cho_phep_nop_tre' => false
        ]);

        return response()->json([
            'data' => [
                'id' => $item->id
            ],
            'message' => 'Đã tạo bài tập.'
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | BÀI NỘP SINH VIÊN
    |--------------------------------------------------------------------------
    */

    public function baiNop(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $assignment = $this->ownAssignment(
            $request,
            $baiTapId
        );

        $submissions = BaiNop::where(
            'bai_tap_id',
            $assignment->id
        )
            ->with('sinhVien:id,ho_ten,email')
            ->latest('id')
            ->get();

        return $this->listData(
            $submissions->map(fn($s) => [
                'id' => $s->id,
                'tenSinhVien' =>
                $s->sinhVien?->ho_ten,
                'tenBaiTap' =>
                $assignment->tieu_de,
                'thoiGianNop' =>
                $this->dateValue(
                    $s->thoi_gian_nop
                ),
                'trangThai' => $s->trang_thai
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUẢN LÝ ĐIỂM
    |--------------------------------------------------------------------------
    */

    public function diem(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'lopId' => ['required', 'integer']
        ]);

        $this->ownClass(
            $request,
            (int) $data['lopId']
        );

        $scores = Diem::whereHas(
            'baiNop.baiTap',
            function ($q) use ($data) {
                $q->where(
                    'lop_hoc_id',
                    $data['lopId']
                );
            }
        )
            ->with([
                'baiNop.sinhVien:id,ho_ten,email',
                'baiNop.baiTap:id,tieu_de'
            ])
            ->latest('id')
            ->get();

        return $this->listData(
            $scores->map(fn($g) => [
                'id' => $g->id,
                'hoTen' =>
                $g->baiNop?->sinhVien?->ho_ten,
                'tenBaiTap' =>
                $g->baiNop?->baiTap?->tieu_de,
                'diem' => $g->diem,
                'nhanXet' => $g->nhan_xet,
                'ketQua' => $g->ket_qua
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH PHIÊN GIÁM SÁT
    |--------------------------------------------------------------------------
    */

    public function phienGiamSat(
        Request $request
    ): JsonResponse {
        $sessions = PhienGiamSat::whereIn(
            'lop_hoc_id',
            $this->classIds($request)
        )
            ->with('lopHoc:id,ten_lop')
            ->latest('id')
            ->get();

        return $this->listData(
            $sessions->map(fn($s) => [
                'id' => $s->id,
                'tenPhien' => $s->ten_phien,
                'tenLop' => $s->lopHoc?->ten_lop,
                'lopId' => $s->lop_hoc_id,
                'thoigianBatDau' =>
                $this->dateValue(
                    $s->thoi_gian_bat_dau
                ),
                'trangThai' =>
                strtoupper($s->trang_thai)
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TẠO PHIÊN GIÁM SÁT
    |--------------------------------------------------------------------------
    */

    public function createPhien(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'lopId' => ['required', 'integer'],
            'tenPhien' => [
                'required',
                'string',
                'max:255'
            ],
            'thoigianBatDau' => [
                'required',
                'date'
            ]
        ]);

        $this->ownClass(
            $request,
            (int) $data['lopId']
        );

        $start =
            \Illuminate\Support\Carbon::parse(
                $data['thoigianBatDau']
            );

        $session = PhienGiamSat::create([
            'lop_hoc_id' => $data['lopId'],
            'nguoi_tao_id' => $request->user()->id,
            'bai_tap_id' => null,
            'ten_phien' => $data['tenPhien'],
            'thoi_gian_bat_dau' => $start,
            'thoi_gian_ket_thuc' => null,
            'trang_thai' => $start->isFuture()
                ? 'chua_bat_dau'
                : 'dang_dien_ra'
        ]);

        return response()->json([
            'data' => [
                'id' => $session->id
            ],
            'message' => 'Đã tạo phiên giám sát.'
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | KẾT THÚC PHIÊN GIÁM SÁT - CHỨC NĂNG MỚI
    |--------------------------------------------------------------------------
    */

    public function ketThucPhien(
        Request $request,
        int $id
    ): JsonResponse {

        // Kiểm tra phiên có thuộc lớp của Giảng viên
        $session = $this->ownSession($request, $id);

        // Chỉ kết thúc phiên đang diễn ra.
        // Cập nhật có điều kiện để ngăn thao tác hai lần.
        $updated = PhienGiamSat::whereKey(
            $session->id
        )
            ->where('trang_thai', 'dang_dien_ra')
            ->update([
                'trang_thai' => 'da_ket_thuc',
                'thoi_gian_ket_thuc' => now()
            ]);

        if ($updated === 0) {
            return response()->json([
                'message' =>
                'Phiên không ở trạng thái đang diễn ra ' .
                    'hoặc đã được kết thúc.'
            ], 409);
        }

        // Lấy dữ liệu mới nhất sau khi cập nhật
        $session->refresh();

        return response()->json([
            'data' => [
                'id' => $session->id,
                'trangThai' =>
                strtoupper($session->trang_thai),
                'thoigianKetThuc' =>
                $this->dateValue(
                    $session->thoi_gian_ket_thuc
                )
            ],
            'message' =>
            'Đã kết thúc phiên giám sát thành công.'
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | NHẬT KÝ HOẠT ĐỘNG
    |--------------------------------------------------------------------------
    */

    
public function hoatDong(
    Request $request
): JsonResponse {

    // Lấy hoạt động thuộc những lớp
    // mà giảng viên đang phụ trách.
    $activities = \Illuminate\Support\Facades\DB::table('hoat_dong')
        ->join(
            'phien_giam_sat',
            'hoat_dong.phien_id',
            '=',
            'phien_giam_sat.id'
        )
        ->join(
            'lop_hoc',
            'phien_giam_sat.lop_hoc_id',
            '=',
            'lop_hoc.id'
        )
        ->join(
            'sinh_vien',
            'hoat_dong.sinh_vien_id',
            '=',
            'sinh_vien.id'
        )
        ->join(
            'nguoi_dung',
            'sinh_vien.nguoi_dung_id',
            '=',
            'nguoi_dung.id'
        )
        ->where(
            'lop_hoc.giang_vien_id',
            $request->user()->id
        )
        ->select(
            'hoat_dong.id',
            'hoat_dong.phien_id',
            'hoat_dong.sinh_vien_id',
            'hoat_dong.loai_hoat_dong',
            'hoat_dong.ten_ung_dung',
            'hoat_dong.website_url',
            'hoat_dong.thoi_gian',
            'sinh_vien.ma_sinh_vien',
            'nguoi_dung.ho_ten'
        )
        ->orderByDesc('hoat_dong.thoi_gian')
        ->limit(500)
        ->get();

    return response()->json([
        'data' => $activities->map(function ($item) {
            return [
                'id' => $item->id,

                'phienId' => $item->phien_id,

                'sinhVienId' => $item->sinh_vien_id,

                'tenSinhVien' => $item->ho_ten,

                'mssv' => $item->ma_sinh_vien,

                'tenMay' => null,

                'hanhDong' => $item->loai_hoat_dong,

                'tenUngDung' => $item->ten_ung_dung,

                'websiteUrl' => $item->website_url,

                'thoiGian' => $item->thoi_gian
            ];
        })
    ]);
}


    /*
    |--------------------------------------------------------------------------
    | ẢNH MÀN HÌNH
    |--------------------------------------------------------------------------
    */


    public function anhManHinh(
        Request $request
    ): JsonResponse {

        // 1. Kiểm tra dữ liệu đầu vào
        $data = $request->validate([
            'phienId' => ['required', 'integer', 'min:1']
        ]);

        // 2. Kiểm tra phiên giám sát thuộc giảng viên
        $this->ownSession(
            $request,
            (int) $data['phienId']
        );

        // 3. Lấy danh sách ảnh và thông tin sinh viên
        $images = AnhManHinh::where(
            'phien_id',
            $data['phienId']
        )
            ->with([
                'sinhVien:id,nguoi_dung_id,ma_sinh_vien',
                'sinhVien.nguoiDung:id,ho_ten,email'
            ])
            ->latest('thoi_gian_chup')
            ->limit(200)
            ->get();

        // 4. Trả dữ liệu phù hợp Frontend
        return $this->listData(
            $images->map(function ($a) {

                $imageUrl = null;

                if (
                    $a->duong_dan &&
                    Storage::disk('public')->exists(
                        $a->duong_dan
                    )
                ) {
                    $imageUrl = url(
                        Storage::url($a->duong_dan)
                    );
                }

                return [
                    'id' => $a->id,

                    'sinhVienId' => $a->sinh_vien_id,

                    'tenSinhVien' =>
                    $a->sinhVien?->nguoiDung?->ho_ten,

                    'mssv' =>
                    $a->sinhVien?->ma_sinh_vien,

                    'tenMay' => null,

                    'trangThai' => null,

                    'imageUrl' => $imageUrl,

                    'thoiGianCapNhat' =>
                    $this->dateValue(
                        $a->thoi_gian_chup
                    )
                ];
            })
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH CẢNH BÁO HỆ THỐNG
    |--------------------------------------------------------------------------
    */

    
public function canhBao(
    Request $request
): JsonResponse {

    // Chỉ lấy cảnh báo trong các lớp
    // do giảng viên hiện tại phụ trách.
    $alerts = DB::table('canh_bao')
        ->join(
            'phien_giam_sat',
            'canh_bao.phien_id',
            '=',
            'phien_giam_sat.id'
        )
        ->join(
            'lop_hoc',
            'phien_giam_sat.lop_hoc_id',
            '=',
            'lop_hoc.id'
        )
        ->join(
            'sinh_vien',
            'canh_bao.sinh_vien_id',
            '=',
            'sinh_vien.id'
        )
        ->join(
            'nguoi_dung',
            'sinh_vien.nguoi_dung_id',
            '=',
            'nguoi_dung.id'
        )
        ->where(
            'lop_hoc.giang_vien_id',
            $request->user()->id
        )
        ->select(
            'canh_bao.id',
            'canh_bao.phien_id',
            'canh_bao.sinh_vien_id',
            'canh_bao.loai_canh_bao',
            'canh_bao.noi_dung',
            'canh_bao.thoi_gian',
            'canh_bao.da_xu_ly',
            'sinh_vien.ma_sinh_vien',
            'nguoi_dung.ho_ten'
        )
        ->orderByDesc('canh_bao.thoi_gian')
        ->limit(500)
        ->get();

    return response()->json([
        'data' => $alerts->map(function ($item) {
            return [
                'id' => $item->id,

                'phienId' => $item->phien_id,

                'sinhVienId' => $item->sinh_vien_id,

                'tenSinhVien' => $item->ho_ten,

                'mssv' => $item->ma_sinh_vien,

                'loaiCanhBao' => $item->loai_canh_bao,

                'chiTiet' => $item->noi_dung,

                'thoiGian' => $item->thoi_gian,

                'trangThai' => $item->da_xu_ly
                    ? 'DA_XU_LY'
                    : 'CHUA_XU_LY'
            ];
        })
    ]);
}


    /*
    |--------------------------------------------------------------------------
    | XỬ LÝ CẢNH BÁO
    |--------------------------------------------------------------------------
    */

    public function xuLyCanhBao(
        Request $request,
        int $id
    ): JsonResponse {
        $warning = CanhBao::whereHas(
            'phien',
            function ($q) use ($request) {
                $q->whereIn(
                    'lop_hoc_id',
                    $this->classIds($request)
                );
            }
        )->findOrFail($id);

        $warning->update([
            'da_xu_ly' => true
        ]);

        return response()->json([
            'data' => [
                'id' => $warning->id
            ],
            'message' => 'Đã xử lý cảnh báo.'
        ]);
    }
}
