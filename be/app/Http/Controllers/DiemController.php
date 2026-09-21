<?php

namespace App\Http\Controllers;

use App\Models\Diem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class DiemController extends CrudController
{
    protected string $modelClass = Diem::class;
    protected function rules(?Model $record = null): array
    {
        return ['bai_nop_id' => ['required', 'integer', 'exists:bai_nop,id', Rule::unique('diem')->ignore($record)], 'nguoi_cham_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'diem' => ['required', 'numeric', 'between:0,999.99'], 'nhan_xet' => ['nullable', 'string'], 'ket_qua' => ['nullable', Rule::in(['dat', 'khong_dat'])], 'thoi_gian_cham' => ['nullable', 'date']];
    }
}
