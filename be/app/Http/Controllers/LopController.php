<?php

namespace App\Http\Controllers;

use App\Models\Lop;
use Illuminate\Http\Request;

class LopController extends Controller
{


    public function getLop()
    {
        $data = Lop::select(
                'id',
                'ma_lop',
                'ten_lop',
                'khoa_id',
                'nganh_id',
                'co_van_id',
                'lop_truong_id',
                'bi_thu_id'
            )
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Lấy danh sách lớp thành công',
            'data'    => $data
        ]);
    }

    public function chiTietLop(Request $request)
    {
        $lop = Lop::find($request->id);

        if (!$lop) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy lớp'
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Lấy chi tiết lớp thành công',
            'data'    => $lop
        ]);
    }

    public function themLop(Request $request)
    {
        $request->validate([
            'ma_lop'  => 'required|unique:lop,ma_lop',
            'ten_lop' => 'required',
            'khoa_id' => 'required|exists:khoa,id',
            'nganh_id' => 'required|exists:nganh,id',
            'co_van_id' => 'nullable|exists:nguoi_dung,id',
            'lop_truong_id' => 'nullable|exists:nguoi_dung,id',
            'bi_thu_id' => 'nullable|exists:nguoi_dung,id',
        ]);

        Lop::create([
            'ma_lop'       => $request->ma_lop,
            'ten_lop'      => $request->ten_lop,
            'khoa_id'      => $request->khoa_id,
            'nganh_id'     => $request->nganh_id,
            'co_van_id'    => $request->co_van_id,
            'lop_truong_id'=> $request->lop_truong_id,
            'bi_thu_id'    => $request->bi_thu_id,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Thêm lớp thành công'
        ]);
    }

    public function suaLop(Request $request)
    {
        $lop = Lop::find($request->id);

        if (!$lop) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy lớp'
            ]);
        }

        $request->validate([
            'ma_lop'  => 'required|unique:lop,ma_lop,' . $request->id,
            'ten_lop' => 'required',
            'khoa_id' => 'required|exists:khoa,id',
            'nganh_id' => 'required|exists:nganh,id',
            'co_van_id' => 'nullable|exists:nguoi_dung,id',
            'lop_truong_id' => 'nullable|exists:nguoi_dung,id',
            'bi_thu_id' => 'nullable|exists:nguoi_dung,id',
        ]);

        $lop->update([
            'ma_lop'       => $request->ma_lop,
            'ten_lop'      => $request->ten_lop,
            'khoa_id'      => $request->khoa_id,
            'nganh_id'     => $request->nganh_id,
            'co_van_id'    => $request->co_van_id,
            'lop_truong_id'=> $request->lop_truong_id,
            'bi_thu_id'    => $request->bi_thu_id,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật lớp thành công'
        ]);
    }

    public function xoaLop(Request $request)
    {
        $lop = Lop::find($request->id);

        if (!$lop) {
            return response()->json([
                'status'  => false,
                'message' => 'Không tìm thấy lớp'
            ]);
        }

        $lop->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Xóa lớp thành công'
        ]);
    }

    public function timKiem(Request $request)
    {
        $keyword = $request->keyword;

        $data = Lop::where('ma_lop', 'like', '%' . $keyword . '%')
            ->orWhere('ten_lop', 'like', '%' . $keyword . '%')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Tìm kiếm lớp thành công',
            'data'    => $data
        ]);
    }
}