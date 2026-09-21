<?php

namespace App\Http\Controllers;

use App\Models\AnhManHinh;
use Illuminate\Database\Eloquent\Model;

class AnhManHinhController extends CrudController
{
    protected string $modelClass = AnhManHinh::class;
    protected function rules(?Model $record = null): array
    {
        return ['phien_id' => ['required', 'integer', 'exists:phien_giam_sat,id'], 'sinh_vien_id' => ['required', 'integer', 'exists:nguoi_dung,id'], 'duong_dan' => ['required', 'string', 'max:255'], 'thoi_gian_chup' => ['required', 'date']];
    }
}
