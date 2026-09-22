<?php

namespace App\Http\Controllers;

use App\Models\VaiTro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VaiTroController extends CrudController
{
    protected string $modelClass = VaiTro::class;
    protected function rules(?Model $record = null): array
    {
        return ['ten_vai_tro' => ['required', 'string', 'max:255', Rule::unique('vai_tro')->ignore($record)], 'mo_ta' => ['nullable', 'string']];
    }

    public function getVaiTro(): JsonResponse
    {
        return response()->json(['data' => VaiTro::withCount('nguoiDungs')->orderBy('id')->get()]);
    }

    public function suaVaiTro(Request $request, int $id): JsonResponse
    {
        $role = VaiTro::findOrFail($id);
        $role->update($request->validate(['mo_ta' => ['nullable', 'string', 'max:1000']]));

        return response()->json(['message' => 'Đã cập nhật mô tả vai trò.', 'data' => $role]);
    }
}
