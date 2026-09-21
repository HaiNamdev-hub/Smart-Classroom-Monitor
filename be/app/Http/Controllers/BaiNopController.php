<?php

namespace App\Http\Controllers;

use App\Models\BaiNop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class BaiNopController extends CrudController
{
    protected string $modelClass = BaiNop::class;
    protected function rules(?Model $record = null): array
    {
        return ['bai_tap_id' => ['required', 'integer', 'exists:bai_tap,id'], 'sinh_vien_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'ten_file' => ['required', 'string', 'max:255'], 'duong_dan' => ['required', 'string', 'max:255'], 'loai_file' => ['nullable', 'string', 'max:255'], 'thoi_gian_nop' => ['required', 'date'], 'trang_thai' => ['required', Rule::in(['dung_han', 'tre_han'])]];
    }
}
