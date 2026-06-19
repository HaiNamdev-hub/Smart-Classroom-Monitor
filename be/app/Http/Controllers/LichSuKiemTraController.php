<?php

namespace App\Http\Controllers;

use App\Models\LichSuKiemTra;
use Illuminate\Http\Request;

class LichSuKiemTraController extends Controller
{
    public function index()
    {
        $data = LichSuKiemTra::with(['phieu', 'nguoiKiemTra'])->orderByDesc('id')->get();

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'phieu_id' => 'required|exists:phieu_ren_luyen,id',
            'nguoi_kiem_tra_id' => 'required|exists:nguoi_dung,id',
            'vai_tro_kiem_tra' => 'required|in:lop_truong,bi_thu,co_van',
            'hanh_dong' => 'required|in:danh_gia,kiem_tra,tra_lai,xac_nhan',
            'ghi_chu' => 'nullable|string',
        ]);

        $record = LichSuKiemTra::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Tạo lịch sử kiểm tra thành công.',
            'data' => $record,
        ], 201);
    }

    public function show($id)
    {
        $record = LichSuKiemTra::with(['phieu', 'nguoiKiemTra'])->find($id);

        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Lịch sử kiểm tra không tồn tại.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $record,
        ]);
    }

    public function update(Request $request, $id)
    {
        $record = LichSuKiemTra::find($id);
        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Lịch sử kiểm tra không tồn tại.',
            ], 404);
        }

        $validated = $request->validate([
            'phieu_id' => 'sometimes|required|exists:phieu_ren_luyen,id',
            'nguoi_kiem_tra_id' => 'sometimes|required|exists:nguoi_dung,id',
            'vai_tro_kiem_tra' => 'sometimes|required|in:lop_truong,bi_thu,co_van',
            'hanh_dong' => 'sometimes|required|in:danh_gia,kiem_tra,tra_lai,xac_nhan',
            'ghi_chu' => 'nullable|string',
        ]);

        $record->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật lịch sử kiểm tra thành công.',
            'data' => $record,
        ]);
    }

    public function destroy($id)
    {
        $record = LichSuKiemTra::find($id);
        if (!$record) {
            return response()->json([
                'status' => false,
                'message' => 'Lịch sử kiểm tra không tồn tại.',
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa lịch sử kiểm tra thành công.',
        ]);
    }
}
