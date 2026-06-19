<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhanQuyen extends Model
{
    protected $table = 'phan_quyen';

    protected $fillable = [
        'vai_tro_id',
        'chuc_nang_id',
    ];
}
