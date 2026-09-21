<?php

namespace App\Http\Controllers;

use App\Models\HoatDong;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class HoatDongController extends CrudController
{
    protected string $modelClass = HoatDong::class;
    protected function rules(?Model $record = null): array
    {
        return ['phien_id' => ['required', 'integer', 'exists:phien_giam_sat,id'], 'sinh_vien_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'loai_hoat_dong' => ['required', Rule::in(['ung_dung', 'website', 'copy_paste', 'tham_gia', 'roi_phien'])], 'ten_ung_dung' => ['nullable', 'string', 'max:255'], 'website_url' => ['nullable', 'url', 'max:255'], 'thoi_gian' => ['required', 'date']];
    }
}
