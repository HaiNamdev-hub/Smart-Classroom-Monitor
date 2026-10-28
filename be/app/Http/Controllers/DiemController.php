<?php

namespace App\Http\Controllers;

use App\Models\Diem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiemController extends CrudController
{
    protected string $modelClass = Diem::class;

    /*
    |--------------------------------------------------------------------------
    | VALIDATE DỮ LIỆU ĐIỂM
    |--------------------------------------------------------------------------
    */
    protected function rules(?Model $record = null): array
    {
        return [
            'bai_nop_id' => [
                'required',
                'integer',
                'exists:bai_nop,id',
                Rule::unique('diem', 'bai_nop_id')
                    ->ignore($record?->getKey()),
            ],

            'nguoi_cham_id' => [
                'required',
                'integer',
                'exists:nguoi_dung,id',
            ],

            'diem' => [
                'required',
                'numeric',
                'between:0,999.99',
            ],

            'nhan_xet' => [
                'nullable',
                'string',
            ],

            'ket_qua' => [
                'nullable',
                Rule::in([
                    'dat',
                    'khong_dat',
                ]),
            ],

            'thoi_gian_cham' => [
                'nullable',
                'date',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LẤY DANH SÁCH ĐIỂM CỦA SINH VIÊN
    |--------------------------------------------------------------------------
    */
    public function getDiemSinhVien(
        Request $request
    ): JsonResponse {
        try {
            // 1. Kiểm tra đăng nhập
            $nguoiDung = $request->user();

            if (!$nguoiDung) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bạn chưa đăng nhập.',
                ], 401);
            }

            // 2. Lấy đúng ID trong bảng sinh_vien
            $sinhVienId = DB::table('sinh_vien')
                ->where(
                    'nguoi_dung_id',
                    $nguoiDung->id
                )
                ->value('id');

            if (!$sinhVienId) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Không tìm thấy hồ sơ Sinh viên.',
                ], 403);
            }

            // 3. Lấy điểm của những bài Sinh viên đã nộp
            $diems = Diem::query()
                ->whereHas(
                    'baiNop',
                    function ($query) use ($sinhVienId) {
                        $query->where(
                            'sinh_vien_id',
                            $sinhVienId
                        );
                    }
                )
                ->with([
                    'baiNop.baiTap.lopHoc:id,ma_lop,ten_lop',
                    'nguoiCham:id,ho_ten,email',
                ])
                ->orderByDesc('thoi_gian_cham')
                ->get();

            // 4. Trả dữ liệu cho Frontend
            return response()->json([
                'status' => true,

                'message' =>
                    'Lấy danh sách điểm thành công.',

                'data' => $diems,
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' =>
                    'Không thể lấy danh sách điểm.',
            ], 500);
        }
    }
}
