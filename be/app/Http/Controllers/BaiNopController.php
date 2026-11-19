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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                'exists:nguoi_dung,id',
            ],

            'ten_file' => [
                'required',
                'string',
                'max:255',
            ],

            'duong_dan' => [
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
    private function getSinhVienUserId(Request $request): int
    {
        $nguoiDung = $request->user();

        abort_unless(
            $nguoiDung,
            401,
            'Bạn chưa đăng nhập.'
        );

        $hasStudentProfile = DB::table('sinh_vien')
            ->where(
                'nguoi_dung_id',
                $nguoiDung->id
            )
            ->exists();

        abort_unless(
            $hasStudentProfile,
            403,
            'Không tìm thấy hồ sơ Sinh viên.'
        );

        // bai_nop.sinh_vien_id and thanh_vien_lop.sinh_vien_id both
        // reference nguoi_dung.id (the authenticated Sanctum user ID).
        return (int) $nguoiDung->id;
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH BÀI NỘP CỦA SINH VIÊN
    |--------------------------------------------------------------------------
    */
    public function getBaiNopSinhVien(
        Request $request
    ): JsonResponse {
        $sinhVienId = $this->getSinhVienUserId($request);

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
        $sinhVienId = $this->getSinhVienUserId($request);

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
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt',
                'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt',
                function ($attribute, $value, $fail) {
                    if (!$value instanceof \Illuminate\Http\UploadedFile || !$value->isValid()) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());
                    $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath()) ?: '';
                    $allowedMimes = [
                        'pdf' => ['application/pdf'],
                        'doc' => ['application/msword', 'application/x-ole-storage'],
                        'docx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage'],
                        'xlsx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                        'ppt' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage'],
                        'pptx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
                        'zip' => ['application/zip', 'application/x-zip'],
                        'txt' => ['text/plain'],
                    ];

                    if (!in_array($mime, $allowedMimes[$extension] ?? [], true)) {
                        $fail('Nội dung MIME thực tế không khớp với định dạng file được phép.');
                    }
                },
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
        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: 'application/octet-stream';

        $path = $file->store('bai-nop', 'local');

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
                    $detectedMime
                ) {
                    return BaiNop::create([
                        'bai_tap_id' =>
                            $baiTapId,

                        'sinh_vien_id' =>
                            $sinhVienId,

                        'ten_file' =>
                            $originalName,

                        'duong_dan' =>
                            $path,

                        'loai_file' =>
                            $detectedMime,

                        'thoi_gian_nop' =>
                            $now,

                        'trang_thai' =>
                            $trangThai,
                    ]);
                }
            );

        } catch (\Throwable $e) {
            // Không để file mồ côi khi ghi DB thất bại.
            Storage::disk('local')->delete($path);

            if (BaiNop::query()->where('bai_tap_id', $baiTapId)->where('sinh_vien_id', $sinhVienId)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bạn đã nộp bài tập này.',
                ], 409);
            }

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

    /** Download an assignment file only for its owner or the assigned teacher. */
    public function download(Request $request, int $baiNopId): StreamedResponse
    {
        $submission = DB::table('bai_nop as bn')
            ->join('bai_tap as bt', 'bt.id', '=', 'bn.bai_tap_id')
            ->join('lop_hoc as lh', 'lh.id', '=', 'bt.lop_hoc_id')
            ->where('bn.id', $baiNopId)
            ->select('bn.*', 'lh.giang_vien_id')
            ->first();

        abort_unless($submission, 404);
        $userId = (int) $request->user()->id;
        $isOwner = (int) $submission->sinh_vien_id === $userId;
        $isAssignedTeacher = (int) $submission->giang_vien_id === $userId;
        abort_unless($isOwner || $isAssignedTeacher, 404);
        abort_if($submission->loai_file === 'online_exam', 404);

        $path = str_replace('\\', '/', (string) $submission->duong_dan);
        $segments = explode('/', $path);
        abort_if(
            $path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)
                || in_array('..', $segments, true) || in_array('.', $segments, true),
            404
        );

        // New files use private local storage. Existing public-disk files are
        // served only through this authorization gate and are not moved/deleted.
        $disk = Storage::disk('local')->exists($path)
            ? Storage::disk('local')
            : (Storage::disk('public')->exists($path) ? Storage::disk('public') : null);
        abort_unless($disk, 404, 'Tệp bài nộp không còn khả dụng.');

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($submission->ten_file)) ?: 'submission';

        return $disk->response($path, $safeName, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$safeName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
