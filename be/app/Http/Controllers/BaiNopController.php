<?php

namespace App\Http\Controllers;

use App\Models\BaiNop;
use App\Models\BaiTap;
use App\Models\ThanhVienLop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BaiNopController extends CrudController
{
    protected string $modelClass = BaiNop::class;

    protected function rules(?Model $record = null): array
    {
        return [
            'bai_tap_id' => [
                'required',
                'integer',
                'exists:bai_tap,id',
            ],

            'sinh_vien_id' => [
                'required',
                'integer',
                'exists:sinh_vien,id',
            ],

            'ten_file' => [
                'required',
                'string',
                'max:255',
            ],

            'duong_dan_file' => [
                'required',
                'string',
                'max:255',
            ],

            'loai_file' => [
                'nullable',
                'string',
                'max:255',
            ],

            'thoi_gian_nop' => [
                'required',
                'date',
            ],

            'trang_thai' => [
                'required',
                Rule::in([
                    'dung_han',
                    'tre_han',
                ]),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LẤY ID SINH VIÊN ĐANG ĐĂNG NHẬP
    |--------------------------------------------------------------------------
    */
    private function getSinhVienId(Request $request): int
    {
        $nguoiDung = $request->user();

        abort_unless(
            $nguoiDung,
            401,
            'Bạn chưa đăng nhập.'
        );

        $sinhVienId = DB::table('sinh_vien')
            ->where(
                'nguoi_dung_id',
                $nguoiDung->id
            )
            ->value('id');

        abort_unless(
            $sinhVienId,
            403,
            'Không tìm thấy hồ sơ Sinh viên.'
        );

        return (int) $sinhVienId;
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH BÀI NỘP CỦA SINH VIÊN
    |--------------------------------------------------------------------------
    */
    public function getBaiNopSinhVien(
        Request $request
    ): JsonResponse {
        $sinhVienId = $this->getSinhVienId($request);

        // Chỉ lấy các bài do Sinh viên này nộp.
        $baiNops = BaiNop::query()
            ->where(
                'sinh_vien_id',
                $sinhVienId
            )
            ->whereHas(
                'baiTap',
                function ($query) use ($sinhVienId) {
                    $query->whereHas(
                        'lopHoc',
                        function ($classQuery) use ($sinhVienId) {
                            $classQuery->whereHas(
                                'thanhViens',
                                function ($memberQuery) use ($sinhVienId) {
                                    $memberQuery->where(
                                        'sinh_vien_id',
                                        $sinhVienId
                                    );
                                }
                            );
                        }
                    );
                }
            )
            ->with([
                'baiTap.lopHoc:id,ma_lop,ten_lop',
                'diem.nguoiCham:id,ho_ten,email',
            ])
            ->orderByDesc('thoi_gian_nop')
            ->get();

        return response()->json([
            'status' => true,

            'message' =>
                'Lấy danh sách bài nộp thành công.',

            'data' => $baiNops,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NỘP BÀI THỰC HÀNH BẰNG FILE
    |--------------------------------------------------------------------------
    */
    public function nopBai(
        Request $request,
        int $baiTapId
    ): JsonResponse {
        $sinhVienId = $this->getSinhVienId($request);

        // 1. Kiểm tra bài tập tồn tại.
        $baiTap = BaiTap::findOrFail($baiTapId);

        // 2. Kiểm tra Sinh viên thuộc đúng lớp.
        $laThanhVien = ThanhVienLop::query()
            ->where(
                'lop_hoc_id',
                $baiTap->lop_hoc_id
            )
            ->where(
                'sinh_vien_id',
                $sinhVienId
            )
            ->where(
                'trang_thai',
                'dang_hoc'
            )
            ->exists();

        if (!$laThanhVien) {
            return response()->json([
                'status' => false,

                'message' =>
                    'Bạn không thuộc lớp học của bài tập này.',
            ], 403);
        }

        // 3. Không dùng API nộp file cho đề thi trực tuyến.
        $laBaiKiemTra = DB::table('cau_hoi')
            ->where(
                'bai_tap_id',
                $baiTapId
            )
            ->exists();

        if ($laBaiKiemTra) {
            return response()->json([
                'status' => false,

                'message' =>
                    'Đây là bài kiểm tra trực tuyến. '
                    . 'Vui lòng nộp tại trang Làm bài kiểm tra.',
            ], 422);
        }

        // 4. Kiểm tra đã đến giờ bắt đầu.
        if ($baiTap->thoi_gian_bat_dau) {
            $start = Carbon::parse(
                $baiTap->thoi_gian_bat_dau
            );

            if (now()->lessThan($start)) {
                return response()->json([
                    'status' => false,

                    'message' =>
                        'Bài tập chưa đến thời gian bắt đầu.',
                ], 403);
            }
        }

        // 5. Validate file tải lên.
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
            ],
        ], [
            'file.required' =>
                'Vui lòng chọn file để nộp.',

            'file.file' =>
                'File không hợp lệ.',

            'file.max' =>
                'Dung lượng file không được vượt quá 10 MB.',
        ]);

        // 6. Kiểm tra hạn nộp.
        $now = now();
        $trangThai = 'dung_han';

        if ($baiTap->han_nop) {
            $deadline = Carbon::parse(
                $baiTap->han_nop
            );

            if ($now->greaterThan($deadline)) {
                if (!$baiTap->cho_phep_nop_tre) {
                    return response()->json([
                        'status' => false,

                        'message' =>
                            'Bài tập đã hết hạn nộp.',
                    ], 422);
                }

                $trangThai = 'tre_han';
            }
        }

        // 7. Không cho nộp lại bài đã nộp.
        $daNop = BaiNop::query()
            ->where(
                'bai_tap_id',
                $baiTapId
            )
            ->where(
                'sinh_vien_id',
                $sinhVienId
            )
            ->exists();

        if ($daNop) {
            return response()->json([
                'status' => false,

                'message' =>
                    'Bạn đã nộp bài tập này.',
            ], 409);
        }

        // 8. Lưu file.
        $file = $request->file('file');

        $originalName = $file->getClientOriginalName();

        $path = $file->store(
            'bai-nop',
            'public'
        );

        try {
            // 9. Tạo bản ghi bài nộp.
            $baiNop = DB::transaction(
                function () use (
                    $baiTapId,
                    $sinhVienId,
                    $originalName,
                    $path,
                    $now,
                    $trangThai,
                    $file
                ) {
                    // Khóa theo bài tập để hạn chế nộp trùng.
                    DB::table('bai_tap')
                        ->where('id', $baiTapId)
                        ->lockForUpdate()
                        ->first();

                    $exists = BaiNop::query()
                        ->where(
                            'bai_tap_id',
                            $baiTapId
                        )
                        ->where(
                            'sinh_vien_id',
                            $sinhVienId
                        )
                        ->exists();

                    abort_if(
                        $exists,
                        409,
                        'Bạn đã nộp bài tập này.'
                    );

                    return BaiNop::create([
                        'bai_tap_id' =>
                            $baiTapId,

                        'sinh_vien_id' =>
                            $sinhVienId,

                        'ten_file' =>
                            $originalName,

                        'duong_dan_file' =>
                            $path,

                        'loai_file' =>
                            $file->getClientMimeType(),

                        'thoi_gian_nop' =>
                            $now,

                        'trang_thai' =>
                            $trangThai,
                    ]);
                }
            );

        } catch (\Throwable $e) {
            // Không để file mồ côi khi ghi DB thất bại.
            Storage::disk('public')->delete($path);

            throw $e;
        }

        return response()->json([
            'status' => true,

            'message' =>
                $trangThai === 'tre_han'
                    ? 'Nộp bài thành công nhưng đã trễ hạn.'
                    : 'Nộp bài thành công.',

            'data' => $baiNop,
        ], 201);
    }
}
