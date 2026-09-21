<?php

namespace App\Http\Controllers;

use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class NguoiDungController extends CrudController
{
    protected string $modelClass = NguoiDung::class;
    protected function rules(?Model $record = null): array
    {
        return [
            'ho_ten' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('nguoi_dung')->ignore($record)],
            'password' => [$record ? 'nullable' : 'required', 'string', 'min:8'],
            'vai_tro_id' => ['required', 'integer', 'exists:vai_tro,id'],
            'trang_thai' => ['required', Rule::in(['hoat_dong', 'khong_hoat_dong', 'bi_khoa'])],
        ];
    }
}
