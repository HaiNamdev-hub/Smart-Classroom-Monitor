<?php

namespace App\Http\Controllers;

use App\Models\CanhBao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CanhBaoController extends CrudController
{
    protected string $modelClass = CanhBao::class;
    protected function rules(?Model $record = null): array
    {
        return ['phien_id' => ['required', 'integer', 'exists:phien_giam_sat,id'], 'sinh_vien_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'loai_canh_bao' => ['required', Rule::in(['ung_dung_khong_cho_phep', 'website_khong_cho_phep', 'copy_paste', 'roi_phien', 'mat_ket_noi'])], 'noi_dung' => ['required', 'string'], 'thoi_gian' => ['required', 'date'], 'da_xu_ly' => ['boolean']];
    }
}
