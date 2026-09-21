<?php

namespace App\Http\Controllers;

use App\Models\LopHoc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class LopHocController extends CrudController
{
    protected string $modelClass = LopHoc::class;
    protected function rules(?Model $record = null): array
    {
        return ['ma_lop' => ['required', 'string', 'max:255', Rule::unique('lop_hoc')->ignore($record)], 'ten_lop' => ['required', 'string', 'max:255'], 'giang_vien_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'mo_ta' => ['nullable', 'string']];
    }
}
