<?php

namespace App\Http\Controllers;

use App\Models\VaiTro;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class VaiTroController extends CrudController
{
    protected string $modelClass = VaiTro::class;
    protected function rules(?Model $record = null): array
    {
        return ['ten_vai_tro' => ['required', 'string', 'max:255', Rule::unique('vai_tro')->ignore($record)], 'mo_ta' => ['nullable', 'string']];
    }
}
