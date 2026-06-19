<?php

namespace App\Http\Controllers;

use App\Models\FileDanhSachLop;
use Illuminate\Http\Request;

class FileDanhSachLopController extends Controller
{
    public function index()
    {
        $data = FileDanhSachLop::with(['lop', 'nguoiTai'])->orderByDesc('id')->get();

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lop_id' => 'required|exists:lop,id',
            'nguoi_tai_id' => 'required|exists:nguoi_dung,id',
            'ten_file' => 'required|string|max:255',
            'duong_dan' => 'required|string|max:255',
            'trang_thai' => 'required|in:cho_xu_ly,da_xu_ly,loi',
        ]);

        $file = FileDanhSachLop::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Tạo file danh sách lớp thành công.',
            'data' => $file,
        ], 201);
    }

    public function show($id)
    {
        $file = FileDanhSachLop::with(['lop', 'nguoiTai'])->find($id);

        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $file,
        ]);
    }

    public function update(Request $request, $id)
    {
        $file = FileDanhSachLop::find($id);
        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        $validated = $request->validate([
            'lop_id' => 'sometimes|required|exists:lop,id',
            'nguoi_tai_id' => 'sometimes|required|exists:nguoi_dung,id',
            'ten_file' => 'sometimes|required|string|max:255',
            'duong_dan' => 'sometimes|required|string|max:255',
            'trang_thai' => 'sometimes|required|in:cho_xu_ly,da_xu_ly,loi',
        ]);

        $file->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật file danh sách lớp thành công.',
            'data' => $file,
        ]);
    }

    public function destroy($id)
    {
        $file = FileDanhSachLop::find($id);
        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File danh sách lớp không tồn tại.',
            ], 404);
        }

        $file->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa file danh sách lớp thành công.',
        ]);
    }
}
