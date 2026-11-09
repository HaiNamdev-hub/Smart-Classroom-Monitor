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
use App\Models\SinhVien;
use App\Models\ThanhVienLop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;


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
        if (!$date) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($date)
            ->format('Y-m-d H:i:s');
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
            'lopId' => [
                'nullable',
                'integer'
            ]
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

        $baiTaps = $query
            ->latest('id')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,

                    'lopId' => $a->lop_hoc_id,

                    'tieuDe' => $a->tieu_de,

                    'moTa' => $a->mo_ta,

                    'hanNop' => $this->dateValue(
                        $a->han_nop
                    ),

                    'thoiGianBatDau' =>
                    $this->dateValue(
                        $a->thoi_gian_bat_dau
                    ),

                    'thoiGianKetThuc' =>
                    $this->dateValue(
                        $a->thoi_gian_ket_thuc
                    ),

                    'daNop' => $a->bai_nops_count
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $baiTaps
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | TẠO BÀI TẬP
    |--------------------------------------------------------------------------
    */


    public function createBaiTap(
        Request $request
    ): JsonResponse {

        // Kiểm tra dữ liệu do Frontend gửi lên
        $data = $request->validate([
            'lopId' => [
                'required',
                'integer'
            ],

            'tieuDe' => [
                'required',
                'string',
                'max:255'
            ],

            'moTa' => [
                'nullable',
                'string'
            ],

            'thoiGianBatDau' => [
                'required',
                'date_format:Y-m-d H:i:s'
            ],

            'thoiGianKetThuc' => [
                'required',
                'date_format:Y-m-d H:i:s'
            ]
        ]);

        // Kiểm tra Giảng viên có quản lý lớp này không
        $this->ownClass(
            $request,
            (int) $data['lopId']
        );

        // Múi giờ của hệ thống
        $timezone = 'Asia/Ho_Chi_Minh';

        // Chuyển chuỗi thời gian thành Carbon
        $start = Carbon::createFromFormat(
            '!Y-m-d H:i:s',
            $data['thoiGianBatDau'],
            $timezone
        );

        $end = Carbon::createFromFormat(
            '!Y-m-d H:i:s',
            $data['thoiGianKetThuc'],
            $timezone
        );

        // Kiểm tra giờ bắt đầu
        if (
            $start->lessThanOrEqualTo(
                Carbon::now($timezone)
            )
        ) {
            return response()->json([
                'status' => false,
                'message' =>
                'Thời gian bắt đầu phải ở tương lai.',
                'errors' => [
                    'thoiGianBatDau' => [
                        'Vui lòng chọn giờ bắt đầu trong tương lai.'
                    ]
                ]
            ], 422);
        }

        // Kiểm tra giờ kết thúc
        if ($end->lessThanOrEqualTo($start)) {
            return response()->json([
                'status' => false,
                'message' =>
                'Thời gian kết thúc phải sau thời gian bắt đầu.',
                'errors' => [
                    'thoiGianKetThuc' => [
                        'Giờ kết thúc phải lớn hơn giờ bắt đầu.'
                    ]
                ]
            ], 422);
        }

        // Tạo bài tập / bài kiểm tra
        $item = BaiTap::create([
            'lop_hoc_id' =>
            $data['lopId'],

            'nguoi_tao_id' =>
            $request->user()->id,

            'tieu_de' =>
            trim($data['tieuDe']),

            'mo_ta' =>
            $data['moTa'] ?? null,

            'thoi_gian_bat_dau' =>
            $start->format('Y-m-d H:i:s'),

            'thoi_gian_ket_thuc' =>
            $end->format('Y-m-d H:i:s'),

            'han_nop' =>
            $end->format('Y-m-d H:i:s'),

            'cho_phep_nop_tre' =>
            false
        ]);

        return response()->json([
            'status' => true,

            'message' =>
            'Đã tạo bài tập và đặt lịch thành công.',

            'data' => [
                'id' => $item->id,

                'tieuDe' =>
                $item->tieu_de,

                'thoiGianBatDau' =>
                $start->toIso8601String(),

                'thoiGianKetThuc' =>
                $end->toIso8601String()
            ]
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
        // Kiểm tra quyền
        $assignment = $this->ownAssignment(
            $request,
            $baiTapId
        );

        // Lấy bài nộp kèm điểm và thông tin Sinh viên
        $submissions = DB::table('bai_nop as bn')
            ->leftJoin(
                'sinh_vien as sv',
                'bn.sinh_vien_id',
                '=',
                'sv.id'
            )
            ->leftJoin(
                'nguoi_dung as nd',
                'sv.nguoi_dung_id',
                '=',
                'nd.id'
            )
            ->leftJoin(
                'diem as d',
                'bn.id',
                '=',
                'd.bai_nop_id'
            )
            ->where(
                'bn.bai_tap_id',
                $assignment->id
            )
            ->select([
                'bn.id',
                'bn.sinh_vien_id',
                'bn.thoi_gian_nop',
                'bn.trang_thai',
                'bn.loai_file',

                'sv.ma_sinh_vien',

                'nd.ho_ten',
                'nd.email',

                'd.id as diem_id',
                'd.diem',
                'd.nhan_xet',
                'd.ket_qua',
            ])
            ->orderByDesc('bn.id')
            ->get();

        $result = $submissions->map(
            function ($item) use ($assignment) {
                return [
                    'id' => $item->id,

                    'sinhVienId' =>
                    $item->sinh_vien_id,

                    'maSinhVien' =>
                    $item->ma_sinh_vien,

                    'tenSinhVien' =>
                    $item->ho_ten,

                    'email' =>
                    $item->email,

                    'tenBaiTap' =>
                    $assignment->tieu_de,

                    'thoiGianNop' =>
                    $this->dateValue(
                        $item->thoi_gian_nop
                    ),

                    'trangThai' =>
                    $item->trang_thai,

                    'loaiFile' =>
                    $item->loai_file,

                    'diemId' =>
                    $item->diem_id,

                    'diem' =>
                    $item->diem !== null
                        ? (float) $item->diem
                        : null,

                    'nhanXet' =>
                    $item->nhan_xet,

                    'ketQua' =>
                    $item->ket_qua,

                    'daCham' =>
                    $item->diem_id !== null,
                ];
            }
        );

        return response()->json([
            'status' => true,
            'data' => $result,
        ]);
    }

    public function chiTietBaiNop(
        Request $request,
        int $baiNopId
    ): JsonResponse {
        // Tìm bài nộp và bài tập tương ứng
        $baiNop = DB::table('bai_nop as bn')
            ->join(
                'bai_tap as bt',
                'bn.bai_tap_id',
                '=',
                'bt.id'
            )
            ->join(
                'lop_hoc as lh',
                'bt.lop_hoc_id',
                '=',
                'lh.id'
            )
            ->where('bn.id', $baiNopId)
            ->where(
                'lh.giang_vien_id',
                $request->user()->id
            )
            ->select([
                'bn.id',
                'bn.bai_tap_id',
                'bn.sinh_vien_id',
                'bn.loai_file',
                'bn.thoi_gian_nop',
                'bt.tieu_de',
            ])
            ->first();

        if (!$baiNop) {
            return response()->json([
                'status' => false,
                'message' =>
                'Không tìm thấy bài nộp hoặc bạn không có quyền xem.',
            ], 404);
        }

        $laBaiTrucTuyen =
            $baiNop->loai_file === 'online_exam';

        // Lấy câu hỏi của bài kiểm tra
        $questions = DB::table('cau_hoi')
            ->where(
                'bai_tap_id',
                $baiNop->bai_tap_id
            )
            ->orderBy('thu_tu')
            ->orderBy('id')
            ->get();

        // Lấy đáp án của từng câu
        $answerOptions = DB::table('dap_an')
            ->whereIn(
                'cau_hoi_id',
                $questions->pluck('id')
            )
            ->orderBy('thu_tu')
            ->orderBy('id')
            ->get()
            ->groupBy('cau_hoi_id');

        // Lấy câu trả lời của Sinh viên
        $studentAnswers = DB::table('cau_tra_loi')
            ->where(
                'bai_nop_id',
                $baiNopId
            )
            ->get()
            ->keyBy('cau_hoi_id');

        $earnedPoints = 0.0;

        $coTuLuan = false;
        $soCauDung = 0;
        $soCauTracNghiem = 0;

        $result = $questions->map(
            function ($question) use (
                $answerOptions,
                $studentAnswers,
                &$earnedPoints,
                &$coTuLuan,
                &$soCauDung,
                &$soCauTracNghiem
            ) {
                $studentAnswer =
                    $studentAnswers->get($question->id);

                $selectedId = $studentAnswer
                    ? $studentAnswer->dap_an_id
                    : null;

                $options = $answerOptions->get(
                    $question->id,
                    collect()
                );

                $isMultipleChoice =
                    $question->loai_cau_hoi === 'trac_nghiem';

                $isCorrect = false;

                if ($isMultipleChoice) {
                    $soCauTracNghiem++;

                    $correctOptions = $options->filter(
                        fn($option) =>
                        (bool) $option->la_dap_an_dung
                    );

                    // Theo logic đề một đáp án đúng
                    if (
                        $correctOptions->count() === 1 &&
                        $selectedId !== null &&
                        (int) $selectedId ===
                        (int) $correctOptions->first()->id
                    ) {
                        $isCorrect = true;
                        $soCauDung++;

                        $earnedPoints +=
                            (float) $question->diem;
                    }
                } elseif (
                    $question->loai_cau_hoi === 'tu_luan'
                ) {
                    $coTuLuan = true;
                }

                return [
                    'id' => $question->id,

                    'noiDung' => $question->noi_dung,

                    'loaiCauHoi' =>
                    $question->loai_cau_hoi,

                    'diem' =>
                    (float) $question->diem,

                    'dung' => $isCorrect,

                    'traLoiTuLuan' =>
                    $studentAnswer
                        ? $studentAnswer->noi_dung_tra_loi
                        : null,

                    'diemDatDuoc' =>
                    $studentAnswer && $studentAnswer->diem !== null
                        ? (float) $studentAnswer->diem
                        : null,

                    'dapAn' => $options->map(
                        function ($option) use ($selectedId) {
                            return [
                                'id' => $option->id,

                                'noiDung' =>
                                $option->noi_dung,

                                'dung' =>
                                (bool) $option->la_dap_an_dung,

                                'daChon' =>
                                $selectedId !== null &&
                                    (int) $selectedId ===
                                    (int) $option->id,
                            ];
                        }
                    )->values()->all(),
                ];
            }
        )->values()->all();

        // Điểm câu trắc nghiệm đã được phân bổ theo thang điểm 10.
        $diemTracNghiem = round($earnedPoints, 2);

        // Lấy điểm chính thức nếu đã có
        $diemDaLuu = DB::table('diem')
            ->where(
                'bai_nop_id',
                $baiNopId
            )
            ->first();

        return response()->json([
            'status' => true,

            'data' => [
                'baiNopId' => $baiNop->id,

                'baiTapId' => $baiNop->bai_tap_id,

                'tieuDe' => $baiNop->tieu_de,

                'laBaiTrucTuyen' => $laBaiTrucTuyen,

                'coTuLuan' => $coTuLuan,

                'soCauTracNghiem' => $soCauTracNghiem,

                'soCauDung' => $soCauDung,

                'diemTracNghiem' =>
                $diemTracNghiem,

                'diemChinhThuc' =>
                $diemDaLuu
                    ? (float) $diemDaLuu->diem
                    : null,

                'daCham' =>
                $diemDaLuu !== null,

                'cauHoi' => $result,
            ],
        ]);
    }




    /*
    |--------------------------------------------------------------------------
    | QUẢN LÝ ĐIỂM
    |--------------------------------------------------------------------------
    */



    public function diem(
        Request $request
    ): JsonResponse {
        // 1. Validate lớp học
        $data = $request->validate([
            'lopId' => [
                'required',
                'integer',
                'exists:lop_hoc,id',
            ],
        ]);

        $lopId = (int) $data['lopId'];

        // 2. Kiểm tra quyền của Giảng viên
        $this->ownClass($request, $lopId);

        // 3. Lấy các bài đã có điểm
        $scores = DB::table('diem as d')
            ->join(
                'bai_nop as bn',
                'd.bai_nop_id',
                '=',
                'bn.id'
            )
            ->join(
                'bai_tap as bt',
                'bn.bai_tap_id',
                '=',
                'bt.id'
            )
            ->join(
                'sinh_vien as sv',
                'bn.sinh_vien_id',
                '=',
                'sv.id'
            )
            ->join(
                'nguoi_dung as nd',
                'sv.nguoi_dung_id',
                '=',
                'nd.id'
            )
            ->where(
                'bt.lop_hoc_id',
                $lopId
            )
            ->select([
                'd.id',
                'd.bai_nop_id',
                'd.diem',
                'd.nhan_xet',
                'd.ket_qua',
                'd.thoi_gian_cham',

                'bn.sinh_vien_id',
                'bn.thoi_gian_nop',

                'bt.id as bai_tap_id',
                'bt.tieu_de',

                'sv.ma_sinh_vien',

                'nd.ho_ten',
                'nd.email',
            ])
            ->orderByDesc('d.id')
            ->get();

        // 4. Chuẩn hóa cho Frontend
        $result = $scores->map(function ($score) {
            return [
                'id' => $score->id,

                'baiNopId' => $score->bai_nop_id,

                'baiTapId' => $score->bai_tap_id,

                'sinhVienId' => $score->sinh_vien_id,

                'maSinhVien' => $score->ma_sinh_vien,

                'hoTen' => $score->ho_ten,

                'email' => $score->email,

                'tenBaiTap' => $score->tieu_de,

                'diem' => (float) $score->diem,

                'nhanXet' => $score->nhan_xet,

                'ketQua' => $score->ket_qua,

                'thoiGianCham' => $this->dateValue(
                    $score->thoi_gian_cham
                ),

                'thoiGianNop' => $this->dateValue(
                    $score->thoi_gian_nop
                ),
            ];
        });

        return response()->json([
            'status' => true,

            'message' =>
            'Lấy danh sách điểm thành công.',

            'data' => $result,
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH PHIÊN GIÁM SÁT
    |--------------------------------------------------------------------------
    */


    /*
|--------------------------------------------------------------------------
| DANH SÁCH PHIÊN GIÁM SÁT
|--------------------------------------------------------------------------
*/
    public function phienGiamSat(Request $request): JsonResponse
    {
        $sessions = PhienGiamSat::whereIn('lop_hoc_id', $this->classIds($request))
            ->with(['lopHoc:id,ten_lop'])
            ->latest('id')->get();

        $titles = BaiTap::whereIn('id', $sessions->pluck('bai_tap_id')->filter()->unique())
            ->pluck('tieu_de', 'id');

        return $this->listData($sessions->map(fn($s) => [
            'id' => $s->id,
            'tenPhien' => $s->ten_phien,
            'tenLop' => $s->lopHoc?->ten_lop,
            'lopId' => $s->lop_hoc_id,
            'baiTapId' => $s->bai_tap_id,
            'tenBaiKiemTra' => $titles[$s->bai_tap_id] ?? null,
            'thoigianBatDau' => $this->dateValue($s->thoi_gian_bat_dau),
            'thoigianKetThuc' => $this->dateValue($s->thoi_gian_ket_thuc),
            'trangThai' => strtoupper($s->trang_thai),
        ]));
    }

    public function createPhien(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lopId' => ['required', 'integer'],
            'baiTapId' => ['required', 'integer'],
            'tenPhien' => ['required', 'string', 'max:255'],
            'thoigianBatDau' => ['required', 'date'],
        ]);

        $lop = $this->ownClass($request, (int) $data['lopId']);
        $assignment = $this->ownAssignment($request, (int) $data['baiTapId']);
        abort_unless(
            (int) $assignment->lop_hoc_id === (int) $lop->id,
            422,
            'Bài kiểm tra không thuộc lớp đã chọn.'
        );

        $start = \Illuminate\Support\Carbon::parse($data['thoigianBatDau']);
        $session = PhienGiamSat::create([
            'lop_hoc_id' => $lop->id,
            'nguoi_tao_id' => $request->user()->id,
            'bai_tap_id' => $assignment->id,
            'ten_phien' => $data['tenPhien'],
            'thoi_gian_bat_dau' => $start,
            'thoi_gian_ket_thuc' => null,
            // Tạo ở trạng thái chờ; giảng viên chủ động nhấn Bắt đầu.
            'trang_thai' => 'chua_bat_dau',
        ]);

        return response()->json([
            'status' => true,
            'data' => ['id' => $session->id],
            'message' => 'Đã tạo phiên thi. Hãy nhấn Bắt đầu khi sẵn sàng.',
        ], 201);
    }

    public function batDauPhien(Request $request, string $id): JsonResponse
    {
        abort_unless(ctype_digit($id) && (int) $id > 0, 422, 'Mã phiên không hợp lệ.');
        $session = $this->ownSession($request, (int) $id);
        abort_unless(
            $session->bai_tap_id !== null,
            422,
            'Phiên chưa liên kết bài kiểm tra.'
        );

        // Cập nhật có điều kiện để tránh bắt đầu hai lần.
        $updated = PhienGiamSat::whereKey($session->id)
            ->where('trang_thai', 'chua_bat_dau')
            ->update(['trang_thai' => 'dang_dien_ra']);

        if (!$updated) {
            return response()->json([
                'status' => false,
                'message' => 'Phiên không ở trạng thái chờ bắt đầu.'
            ], 409);
        }

        return response()->json([
            'status' => true,
            'data' => ['id' => $session->id, 'trangThai' => 'DANG_DIEN_RA'],
            'message' => 'Đã bắt đầu phiên giám sát thi.',
        ]);
    }


    /*
|--------------------------------------------------------------------------
| KẾT THÚC PHIÊN GIÁM SÁT
|--------------------------------------------------------------------------
*/
    
public function ketThucPhien(
    Request $request,
    string $id
): JsonResponse {

    abort_unless(
        ctype_digit($id) && (int) $id > 0,
        422,
        'Mã phiên giám sát không hợp lệ.'
    );

    $session = $this->ownSession(
        $request,
        (int) $id
    );

    $now = Carbon::now('Asia/Ho_Chi_Minh')
        ->format('Y-m-d H:i:s');

    $updated = PhienGiamSat::query()
        ->whereKey($session->id)
        ->where('trang_thai', 'dang_dien_ra')
        ->update([
            'trang_thai' => 'da_ket_thuc',
            'thoi_gian_ket_thuc' => $now
        ]);

    if (!$updated) {
        return response()->json([
            'status' => false,
            'message' =>
                'Chỉ có thể kết thúc phiên đang diễn ra.'
        ], 409);
    }

    return response()->json([
        'status' => true,
        'message' =>
            'Đã kết thúc phiên giám sát thi.',
        'data' => [
            'id' => $session->id,
            'trangThai' => 'DA_KET_THUC',
            'thoigianKetThuc' => $now
        ]
    ]);
}

    /**
     * Chi tiết phiên giám sát và danh sách Sinh viên phục vụ giám sát trực tiếp.
     *
     * GET /api/giang-vien/phien-giam-sat/{id}/chi-tiet
     */
    public function chiTietPhien(Request $request, int $id): JsonResponse
    {
        $session = $this->ownSession($request, $id);
        $session->load(['lopHoc', 'baiTap']);

        $members = ThanhVienLop::query()
            ->where('lop_hoc_id', $session->lop_hoc_id)
            ->where('trang_thai', 'dang_hoc')
            ->get();

        // ThanhVienLop.sinh_vien_id hiện tham chiếu nguoi_dung.id;
        // WebRTC cần sinh_vien.id. Giữ riêng hai ID để không trộn khóa.
        $memberUserIds = $members->pluck('sinh_vien_id')->filter()->unique()->values();
        $studentProfilesByUserId = SinhVien::query()
            ->with('nguoiDung:id,ho_ten,email')
            ->whereIn('nguoi_dung_id', $memberUserIds)
            ->get()
            ->keyBy('nguoi_dung_id');
        $studentProfilesById = $studentProfilesByUserId->keyBy('id');
        $studentProfileIds = $studentProfilesByUserId->pluck('id')->filter()->unique()->values();

        // 1. Bản đồ bài nộp
        $submittedMap = [];
        if ($session->bai_tap_id && $memberUserIds->isNotEmpty()) {
            $submittedMap = DB::table('bai_nop')
                ->where('bai_tap_id', $session->bai_tap_id)
                ->whereIn('sinh_vien_id', $memberUserIds)
                ->pluck('created_at', 'sinh_vien_id')
                ->toArray();
        }

        // 2. Vi phạm rời tab
        $tabViolations = [];
        if ($session->bai_tap_id && $studentProfileIds->isNotEmpty()) {
            $tabViolations = DB::table('vi_pham_kiem_tra')
                ->where('bai_tap_id', $session->bai_tap_id)
                ->whereIn('sinh_vien_id', $studentProfileIds)
                ->select('sinh_vien_id', DB::raw('count(*) as count'))
                ->groupBy('sinh_vien_id')
                ->pluck('count', 'sinh_vien_id')
                ->toArray();
        }

        // 3. Cảnh báo camera
        $cameraRecords = collect();
        if ($session->bai_tap_id && $studentProfileIds->isNotEmpty()) {
            $cameraRecords = DB::table('canh_bao_camera')
                ->where('bai_tap_id', $session->bai_tap_id)
                ->whereIn('sinh_vien_id', $studentProfileIds)
                ->orderByDesc('id')
                ->get(['sinh_vien_id', 'loai_canh_bao', 'created_at']);
        }

        $cameraGrouped = $cameraRecords->groupBy('sinh_vien_id');

        $tenLoaiCanhBao = [
            'khong_co_khuon_mat' => 'Không có khuôn mặt',
            'nhieu_khuon_mat' => 'Nhiều khuôn mặt',
            'camera_ngat' => 'Camera bị ngắt',
            'phat_hien_dien_thoai' => 'Phát hiện điện thoại',
            'roi_tab' => 'Rời tab bài thi',
        ];

        // 4. Danh sách sinh viên tổng hợp
        $students = $members->map(function ($m) use (
            $submittedMap,
            $tabViolations,
            $cameraGrouped,
            $tenLoaiCanhBao,
            $studentProfilesByUserId
        ) {
            $memberUserId = $m->sinh_vien_id;
            $profile = $studentProfilesByUserId->get($memberUserId);
            $profileId = $profile?->id;
            $cams = $cameraGrouped->get($profileId, collect());

            $phoneCount = $cams->where('loai_canh_bao', 'phat_hien_dien_thoai')->count();
            $otherCamCount = $cams->where('loai_canh_bao', '!=', 'phat_hien_dien_thoai')->count();
            $tabCount = (int) ($tabViolations[$profileId] ?? 0);
            $daNop = isset($submittedMap[$memberUserId]);

            $isLocked = ($phoneCount >= 3) || ($otherCamCount >= 5) || ($tabCount >= 3);
            $lockReason = $phoneCount >= 3
                ? 'dien_thoai'
                : ($otherCamCount >= 5
                    ? 'camera'
                    : ($tabCount >= 3 ? 'roi_tab' : null));

            $latestCam = $cams->first();
            $canhBaoGanNhat = null;
            if ($latestCam) {
                $canhBaoGanNhat = [
                    'loai' => $latestCam->loai_canh_bao,
                    'noiDung' => $tenLoaiCanhBao[$latestCam->loai_canh_bao] ?? $latestCam->loai_canh_bao,
                    'thoiGian' => $latestCam->created_at,
                ];
            }

            return [
                'thanhVienId' => $m->id,
                'sinhVienId' => $profile?->id,
                'maSinhVien' => $profile?->ma_sinh_vien ?? ('SV' . str_pad($memberUserId, 4, '0', STR_PAD_LEFT)),
                'hoTen' => $profile?->nguoiDung?->ho_ten ?? 'Sinh viên',
                'email' => $profile?->nguoiDung?->email ?? '',
                'daNopBai' => $daNop,
                'soLanViPham' => $tabCount,
                'soLanCamera' => $otherCamCount,
                'soLanDienThoai' => $phoneCount,
                'biKhoa' => $isLocked,
                'lyDoKhoa' => $lockReason,
                'canhBaoGanNhat' => $canhBaoGanNhat,
            ];
        })->values();

        // 5. Danh sách cảnh báo gần đây của toàn phiên
        $recentAlerts = collect();
        if ($session->bai_tap_id) {
            $camAlerts = DB::table('canh_bao_camera')
                ->where('bai_tap_id', $session->bai_tap_id)
                ->orderByDesc('id')
                ->limit(30)
                ->get()
                ->map(function ($row) use ($studentProfilesById, $tenLoaiCanhBao) {
                    $profile = $studentProfilesById->get($row->sinh_vien_id);
                    return [
                        'id' => 'cam_' . $row->id,
                        'sinhVienId' => $profile?->id,
                        'hoTen' => $profile?->nguoiDung?->ho_ten ?? 'Sinh viên',
                        'maSinhVien' => $profile?->ma_sinh_vien ?? '',
                        'loai' => $row->loai_canh_bao,
                        'noiDung' => $tenLoaiCanhBao[$row->loai_canh_bao] ?? $row->loai_canh_bao,
                        'thoiGian' => $row->created_at,
                    ];
                });

            $tabAlerts = DB::table('vi_pham_kiem_tra')
                ->where('bai_tap_id', $session->bai_tap_id)
                ->orderByDesc('id')
                ->limit(30)
                ->get()
                ->map(function ($row) use ($studentProfilesById) {
                    $profile = $studentProfilesById->get($row->sinh_vien_id);
                    return [
                        'id' => 'tab_' . $row->id,
                        'sinhVienId' => $profile?->id,
                        'hoTen' => $profile?->nguoiDung?->ho_ten ?? 'Sinh viên',
                        'maSinhVien' => $profile?->ma_sinh_vien ?? '',
                        'loai' => 'roi_tab',
                        'noiDung' => "Rời tab làm bài (lần {$row->lan_thu})",
                        'thoiGian' => $row->created_at,
                    ];
                });

            $recentAlerts = $camAlerts->concat($tabAlerts)
                ->sortByDesc('thoiGian')
                ->values()
                ->take(30);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'phien' => [
                    'id' => $session->id,
                    'tenPhien' => $session->ten_phien,
                    'trangThai' => $session->trang_thai,
                    'lopId' => $session->lop_hoc_id,
                    'tenLop' => $session->lopHoc?->ten_lop,
                    'baiTapId' => $session->bai_tap_id,
                    'tenBaiKiemTra' => $session->baiTap?->tieu_de,
                    'thoigianBatDau' => $session->thoi_gian_bat_dau?->toISOString(),
                    'thoigianKetThuc' => $session->thoi_gian_ket_thuc?->toISOString(),
                ],
                'students' => $students,
                'recentAlerts' => $recentAlerts,
            ],
        ]);
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
        // 1. Kiểm tra ID phiên
        $data = $request->validate([
            'phienId' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $phienId = (int) $data['phienId'];

        // 2. Kiểm tra quyền Giảng viên
        $this->ownSession($request, $phienId);

        // 3. Lấy ảnh từ Database
        $images = DB::table('anh_man_hinh as amh')
            ->join(
                'sinh_vien as sv',
                'amh.sinh_vien_id',
                '=',
                'sv.id'
            )
            ->join(
                'nguoi_dung as nd',
                'sv.nguoi_dung_id',
                '=',
                'nd.id'
            )
            ->where('amh.phien_id', $phienId)
            ->select([
                'amh.id',
                'amh.phien_id',
                'amh.sinh_vien_id',
                'amh.duong_dan',
                'amh.thoi_gian_chup',
                'sv.ma_sinh_vien',
                'nd.ho_ten',
            ])
            ->orderByDesc('amh.thoi_gian_chup')
            ->limit(200)
            ->get();

        // 4. Chuẩn hóa dữ liệu cho Vue
        $result = $images->map(function ($image) {
            $imageUrl = null;

            if (!empty($image->duong_dan)) {
                $path = ltrim(
                    (string) $image->duong_dan,
                    '/'
                );

                if (Storage::disk('public')->exists($path)) {
                    $imageUrl = url('/storage/' . $path);
                }
            }

            return [
                'id' => $image->id,

                'phienId' => $image->phien_id,

                'sinhVienId' =>
                $image->sinh_vien_id,

                'tenSinhVien' =>
                $image->ho_ten,

                'mssv' =>
                $image->ma_sinh_vien,

                // Chỉ trả dữ liệu có thật.
                'tenMay' => null,
                'trangThai' => null,

                'imageUrl' => $imageUrl,

                'thoiGianCapNhat' =>
                $this->dateValue(
                    $image->thoi_gian_chup
                ),
            ];
        });

        return response()->json([
            'status' => true,
            'message' =>
            'Lấy danh sách ảnh màn hình thành công.',
            'data' => $result,
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH CẢNH BÁO HỆ THỐNG
    |--------------------------------------------------------------------------
    */



    public function canhBao(
        Request $request
    ): JsonResponse {
        $nguoiDung = $request->user();

        if (!$nguoiDung) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        /*
    |--------------------------------------------------------------------------
    | 1. CẢNH BÁO TỪ PHIÊN GIÁM SÁT
    |--------------------------------------------------------------------------
    */

        $canhBaoGiamSat = DB::table('canh_bao as cb')
            ->join(
                'phien_giam_sat as p',
                'cb.phien_id',
                '=',
                'p.id'
            )
            ->join(
                'lop_hoc as lh',
                'p.lop_hoc_id',
                '=',
                'lh.id'
            )
            ->join(
                'sinh_vien as sv',
                'cb.sinh_vien_id',
                '=',
                'sv.id'
            )
            ->join(
                'nguoi_dung as nd',
                'sv.nguoi_dung_id',
                '=',
                'nd.id'
            )
            ->where(
                'lh.giang_vien_id',
                $nguoiDung->id
            )
            ->select([
                'cb.id',
                'cb.phien_id',
                'cb.sinh_vien_id',
                'cb.loai_canh_bao',
                'cb.noi_dung',
                'cb.thoi_gian',
                'cb.da_xu_ly',
                'sv.ma_sinh_vien',
                'nd.ho_ten',
                'lh.ten_lop',
            ])
            ->orderByDesc('cb.thoi_gian')
            ->limit(500)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'GS-' . $item->id,
                    'canhBaoId' => $item->id,
                    'nguon' => 'giam_sat',

                    'phienId' => $item->phien_id,
                    'baiTapId' => null,

                    'sinhVienId' => $item->sinh_vien_id,
                    'tenSinhVien' => $item->ho_ten,
                    'mssv' => $item->ma_sinh_vien,
                    'tenLop' => $item->ten_lop,

                    'loaiCanhBao' => $item->loai_canh_bao,
                    'chiTiet' => $item->noi_dung,
                    'thoiGian' => $item->thoi_gian,

                    'soLanViPham' => null,
                    'biKhoa' => false,

                    'trangThai' => $item->da_xu_ly
                        ? 'DA_XU_LY'
                        : 'CHUA_XU_LY',
                ];
            });

        /*
    |--------------------------------------------------------------------------
    | 2. CẢNH BÁO RỜI TAB KHI KIỂM TRA
    |--------------------------------------------------------------------------
    */

        $viPhamKiemTra = DB::table('vi_pham_kiem_tra as vp')
            ->join(
                'bai_tap as bt',
                'vp.bai_tap_id',
                '=',
                'bt.id'
            )
            ->join(
                'lop_hoc as lh',
                'bt.lop_hoc_id',
                '=',
                'lh.id'
            )
            ->join(
                'sinh_vien as sv',
                'vp.sinh_vien_id',
                '=',
                'sv.id'
            )
            ->join(
                'nguoi_dung as nd',
                'sv.nguoi_dung_id',
                '=',
                'nd.id'
            )
            ->where(
                'lh.giang_vien_id',
                $nguoiDung->id
            )
            ->select([
                'vp.id',
                'vp.bai_tap_id',
                'vp.sinh_vien_id',
                'vp.lan_thu',
                'vp.loai_vi_pham',
                'vp.created_at',
                'bt.tieu_de',
                'sv.ma_sinh_vien',
                'nd.ho_ten',
                'lh.ten_lop',
            ])
            ->orderByDesc('vp.created_at')
            ->limit(500)
            ->get()
            ->map(function ($item) {
                $lanThu = (int) $item->lan_thu;
                $biKhoaTaiSuKien = $lanThu >= 3;

                return [
                    'id' => 'KT-' . $item->id,
                    'viPhamId' => $item->id,
                    'nguon' => 'kiem_tra',

                    'phienId' => null,
                    'baiTapId' => $item->bai_tap_id,

                    'sinhVienId' => $item->sinh_vien_id,
                    'tenSinhVien' => $item->ho_ten,
                    'mssv' => $item->ma_sinh_vien,
                    'tenLop' => $item->ten_lop,
                    'tenBaiKiemTra' => $item->tieu_de,

                    'loaiCanhBao' => $biKhoaTaiSuKien
                        ? 'khoa_bai_kiem_tra'
                        : 'roi_tab',

                    'chiTiet' => $biKhoaTaiSuKien
                        ? 'Sinh viên đã rời tab 3 lần. '
                        . 'Bài kiểm tra bị khóa.'
                        : 'Sinh viên rời tab khi làm bài '
                        . 'kiểm tra (lần '
                        . $lanThu . '/3).',

                    'thoiGian' => $item->created_at,

                    'soLanViPham' => $lanThu,
                    'biKhoa' => $biKhoaTaiSuKien,

                    // Bảng vi_pham_kiem_tra hiện chưa có
                    // trường da_xu_ly, nên không giả định
                    // là đã xử lý.
                    'trangThai' => 'CHUA_XU_LY',
                ];
            });

        /*
    |--------------------------------------------------------------------------
    | 3. GỘP DANH SÁCH
    |--------------------------------------------------------------------------
    */

        $alerts = $canhBaoGiamSat
            ->concat($viPhamKiemTra)
            ->sortByDesc('thoiGian')
            ->take(500)
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách cảnh báo thành công.',
            'data' => $alerts,
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | XỬ LÝ CẢNH BÁO
    |--------------------------------------------------------------------------
    */


    
public function xuLyCanhBao(
    Request $request,
    string $id
): JsonResponse {

    // Chỉ xử lý cảnh báo từ bảng canh_bao.
    // Chấp nhận ID số hoặc mã GS-16.
    if (preg_match('/^GS-([1-9][0-9]*)$/', $id, $matches)) {
        $id = $matches[1];
    }

    abort_unless(
        ctype_digit($id) && (int) $id > 0,
        422,
        'Mã cảnh báo không hợp lệ.'
    );

    $warning = CanhBao::whereHas(
        'phien',
        function ($query) use ($request) {
            $query->whereIn(
                'lop_hoc_id',
                $this->classIds($request)
            );
        }
    )->findOrFail((int) $id);

    if (!$warning->da_xu_ly) {
        $warning->update([
            'da_xu_ly' => true
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Đã xử lý cảnh báo thành công.',
        'data' => [
            'id' => $warning->id,
            'daXuLy' => true,
            'trangThai' => 'DA_XU_LY'
        ]
    ]);
}

}
