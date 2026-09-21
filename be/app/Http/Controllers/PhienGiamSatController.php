<?php

namespace App\Http\Controllers;

use App\Models\PhienGiamSat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PhienGiamSatController extends CrudController
{
    protected string $modelClass = PhienGiamSat::class;
    protected function rules(?Model $record = null): array
    {
        return ['lop_hoc_id' => ['required', 'integer', 'exists:lop_hoc,id'], 'bai_tap_id' => ['nullable', 'integer', 'exists:bai_tap,id'], 'nguoi_tao_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'ten_phien' => ['required', 'string', 'max:255'], 'thoi_gian_bat_dau' => ['required', 'date'], 'thoi_gian_ket_thuc' => ['nullable', 'date', 'after_or_equal:thoi_gian_bat_dau'], 'trang_thai' => ['required', Rule::in(['chua_bat_dau', 'dang_dien_ra', 'da_ket_thuc'])]];
    }
}
