<?php

namespace App\Http\Controllers;

use App\Models\ThanhVienLop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ThanhVienLopController extends CrudController
{
    protected string $modelClass = ThanhVienLop::class;
    protected function rules(?Model $record = null): array
    {
        return ['lop_hoc_id' => ['required', 'integer', 'exists:lop_hoc,id'], 'sinh_vien_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'trang_thai' => ['required', Rule::in(['dang_hoc', 'da_roi_lop'])]];
    }
}
