<?php

namespace App\Http\Controllers;

use App\Models\LopHoc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\NguoiDung;

class LopHocController extends CrudController
{
    protected string $modelClass = LopHoc::class;
    protected function rules(?Model $record = null): array
    {
        return ['ma_lop' => ['required', 'string', 'max:255', Rule::unique('lop_hoc')->ignore($record)], 'ten_lop' => ['required', 'string', 'max:255'], 'giang_vien_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'mo_ta' => ['nullable', 'string']];
    }

    public function getLopHoc(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $query = LopHoc::with('giangVien:id,ho_ten')->withCount('thanhViens');
        if (! empty($data['search'])) {
            $query->where(fn ($q) => $q->where('ten_lop', 'like', '%'.$data['search'].'%')->orWhere('ma_lop', 'like', '%'.$data['search'].'%'));
        }

        return response()->json($query->latest('id')->paginate(9));
    }

    public function getGiangVien(): JsonResponse
    {
        return response()->json(['data' => NguoiDung::where('trang_thai', 'hoat_dong')
            ->whereHas('vaiTro', fn ($q) => $q->where('ten_vai_tro', 'giang_vien'))
            ->orderBy('ho_ten')->get(['id', 'ho_ten', 'email'])]);
    }

    public function themLopHoc(Request $request): JsonResponse
    {
        return $this->luuLopHoc($request, new LopHoc);
    }

    public function suaLopHoc(Request $request, int $id): JsonResponse
    {
        return $this->luuLopHoc($request, LopHoc::findOrFail($id));
    }

    private function luuLopHoc(Request $request, LopHoc $class): JsonResponse
    {
        $data = $request->validate($this->rules($class->exists ? $class : null));
        $validTeacher = NguoiDung::whereKey($data['giang_vien_id'])->where('trang_thai', 'hoat_dong')
            ->whereHas('vaiTro', fn ($q) => $q->where('ten_vai_tro', 'giang_vien'))->exists();
        if (! $validTeacher) {
            return response()->json(['message' => 'Vui lòng chọn giảng viên đang hoạt động.'], 422);
        }
        $status = $class->exists ? 200 : 201;
        $class->fill($data)->save();

        return response()->json(['message' => 'Đã lưu lớp học.', 'data' => $class->load('giangVien')], $status);
    }
}
