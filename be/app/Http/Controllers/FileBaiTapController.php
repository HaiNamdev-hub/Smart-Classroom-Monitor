<?php

namespace App\Http\Controllers;

use App\Models\FileBaiTap;
use Illuminate\Database\Eloquent\Model;

class FileBaiTapController extends CrudController
{
    protected string $modelClass = FileBaiTap::class;
    protected function rules(?Model $record = null): array
    {
        return ['bai_tap_id' => ['required', 'integer', 'exists:bai_tap,id'], 'ten_file' => ['required', 'string', 'max:255'], 'duong_dan' => ['required', 'string', 'max:255'], 'loai_file' => ['nullable', 'string', 'max:255'], 'kich_thuoc' => ['nullable', 'integer', 'min:0']];
    }
}
