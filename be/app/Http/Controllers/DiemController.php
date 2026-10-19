<?php

namespace App\Http\Controllers;

use App\Models\Diem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiemController extends CrudController
{
    protected string $modelClass = Diem::class;
    protected function rules(?Model $record = null): array
    {
        return ['bai_nop_id' => ['required', 'integer', 'exists:bai_nop,id', Rule::unique('diem')->ignore($record)], 'nguoi_cham_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'diem' => ['required', 'numeric', 'between:0,999.99'], 'nhan_xet' => ['nullable', 'string'], 'ket_qua' => ['nullable', Rule::in(['dat', 'khong_dat'])], 'thoi_gian_cham' => ['nullable', 'date']];
    }
    public function getDiemSinhVien(Request $request)
{
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
        | Chỉ lấy điểm thuộc bài nộp của sinh viên đăng nhập
        |--------------------------------------------------------------------------
        */

        $diems = \App\Models\Diem::query()

            ->whereHas(
                'baiNop',
                function ($query) use ($sinhVien) {

                    $query->where(
                        'sinh_vien_id',
                        $sinhVien->id
                    );
                }
            )

            ->with([
                'baiNop.baiTap.lopHoc:id,ma_lop,ten_lop',

                'nguoiCham:id,ho_ten,email',
            ])

            ->orderByDesc(
                'thoi_gian_cham'
            )

            ->get();

        return response()->json([
            'status' => true,

            'message' =>
                'Lấy danh sách điểm thành công.',

            'data' =>
                $diems,
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'status' => false,

            'message' =>
                'Không thể lấy danh sách điểm.',

            'error' =>
                $e->getMessage(),
        ], 500);
    }
}
}
