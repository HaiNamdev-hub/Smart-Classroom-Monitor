<?php

namespace App\Http\Controllers;

use App\Models\BaiNop;
use App\Models\BaiTap;
use App\Models\ThanhVienLop;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class BaiNopController extends CrudController
{
    protected string $modelClass = BaiNop::class;
    protected function rules(?Model $record = null): array
    {
        return ['bai_tap_id' => ['required', 'integer', 'exists:bai_tap,id'], 'sinh_vien_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'ten_file' => ['required', 'string', 'max:255'], 'duong_dan' => ['required', 'string', 'max:255'], 'loai_file' => ['nullable', 'string', 'max:255'], 'thoi_gian_nop' => ['required', 'date'], 'trang_thai' => ['required', Rule::in(['dung_han', 'tre_han'])]];
    }
    public function getBaiNopSinhVien(Request $request)
    {
        try {
            $sinhVien = $request->user();

            if (!$sinhVien) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bạn chưa đăng nhập.',
                ], 401);
            }

            $baiNops = BaiNop::query()

                ->where(
                    'sinh_vien_id',
                    $sinhVien->id
                )

                ->with([
                    'baiTap.lopHoc:id,ma_lop,ten_lop',
                    'diem.nguoiCham:id,ho_ten,email',
                ])

                ->orderByDesc(
                    'thoi_gian_nop'
                )

                ->get();

            return response()->json([
                'status' => true,

                'message' =>
                'Lấy danh sách bài nộp thành công.',

                'data' =>
                $baiNops,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,

                'message' =>
                'Không thể lấy danh sách bài nộp.',

                'error' =>
                $e->getMessage(),
            ], 500);
        }
    }
    public function nopBai(
        Request $request,
        $baiTapId
    ) {
        try {
            $sinhVien =
                $request->user();

            if (!$sinhVien) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bạn chưa đăng nhập.',
                ], 401);
            }

            /*
        |--------------------------------------------------------------------------
        | Kiểm tra bài tập
        |--------------------------------------------------------------------------
        */

            $baiTap = BaiTap::find(
                $baiTapId
            );

            if (!$baiTap) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Bài tập không tồn tại.',
                ], 404);
            }

            /*
        |--------------------------------------------------------------------------
        | Kiểm tra sinh viên có thuộc lớp không
        |--------------------------------------------------------------------------
        */

            $laThanhVien = ThanhVienLop::query()

                ->where(
                    'lop_hoc_id',
                    $baiTap->lop_hoc_id
                )

                ->where(
                    'sinh_vien_id',
                    $sinhVien->id
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

            /*
        |--------------------------------------------------------------------------
        | Kiểm tra đã nộp chưa
        |--------------------------------------------------------------------------
        */

            $daNop = BaiNop::query()

                ->where(
                    'bai_tap_id',
                    $baiTap->id
                )

                ->where(
                    'sinh_vien_id',
                    $sinhVien->id
                )

                ->exists();

            if ($daNop) {
                return response()->json([
                    'status' => false,

                    'message' =>
                    'Bạn đã nộp bài tập này.',
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Validate file
        |--------------------------------------------------------------------------
        */

            $request->validate([
                'file' =>
                'required|file|max:10240',
            ], [
                'file.required' =>
                'Vui lòng chọn file để nộp.',

                'file.file' =>
                'File không hợp lệ.',

                'file.max' =>
                'Dung lượng file không được vượt quá 10MB.',
            ]);

            /*
        |--------------------------------------------------------------------------
        | Kiểm tra hạn nộp
        |--------------------------------------------------------------------------
        */

            $now = now();

            $trangThai =
                'dung_han';

            if (
                $baiTap->han_nop &&
                $now->gt($baiTap->han_nop)
            ) {
                /*
             * Quá hạn và giảng viên không cho nộp trễ
             */
                if (!$baiTap->cho_phep_nop_tre) {

                    return response()->json([
                        'status' => false,

                        'message' =>
                        'Bài tập đã hết hạn nộp.',
                    ], 422);
                }

                $trangThai =
                    'tre_han';
            }

            /*
        |--------------------------------------------------------------------------
        | Upload file
        |--------------------------------------------------------------------------
        */

            $file =
                $request->file('file');

            $originalName =
                $file->getClientOriginalName();

            // Upload lên Cloudinary (lưu vĩnh viễn, không mất khi Render restart)
            $cloudinary = new CloudinaryService();
            $uploaded   = $cloudinary->uploadRaw(
                $file,
                'bai-nop',
            );
            $path = $uploaded['url']; // URL công khai trên Cloudinary

            /*
        |--------------------------------------------------------------------------
        | Tạo bài nộp
        |--------------------------------------------------------------------------
        */

            $baiNop = BaiNop::create([
                'bai_tap_id' =>
                $baiTap->id,

                'sinh_vien_id' =>
                $sinhVien->id,

                'ten_file' =>
                $originalName,

                'duong_dan_file' =>
                $path,

                'thoi_gian_nop' =>
                $now,

                'trang_thai' =>
                $trangThai,
            ]);

            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'status' => true,

                'message' =>
                $trangThai === 'tre_han'
                    ? 'Nộp bài thành công nhưng đã trễ hạn.'
                    : 'Nộp bài thành công.',

                'data' =>
                $baiNop,
            ], 201);
        } catch (
            \Illuminate\Validation\ValidationException $e
        ) {

            throw $e;
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,

                'message' =>
                'Không thể nộp bài.',

                'error' =>
                $e->getMessage(),
            ], 500);
        }
    }
}
