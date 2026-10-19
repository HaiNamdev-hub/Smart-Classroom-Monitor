<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChucNang extends Model
{
    protected $table = 'chuc_nang';

    protected $fillable = [
        'ma_chuc_nang',
        'ten_chuc_nang',
        'mo_ta',
        'trang_thai',
    ];

    public function phanQuyens()
    {
        return $this->hasMany(
            PhanQuyen::class,
            'chuc_nang_id'
        );
    }
}