<?php

namespace App\Http\Controllers;

use App\Models\BaiTap;
use Illuminate\Database\Eloquent\Model;

class BaiTapController extends CrudController
{
    protected string $modelClass = BaiTap::class;
    protected function rules(?Model $record = null): array
    {
        return ['lop_hoc_id' => ['required', 'integer', 'exists:lop_hoc,id'], 'nguoi_tao_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'tieu_de' => ['required', 'string', 'max:255'], 'mo_ta' => ['nullable', 'string'], 'thoi_gian_bat_dau' => ['required', 'date'], 'thoi_gian_ket_thuc' => ['required', 'date', 'after_or_equal:thoi_gian_bat_dau'], 'han_nop' => ['required', 'date'], 'cho_phep_nop_tre' => ['boolean']];
    }
}
