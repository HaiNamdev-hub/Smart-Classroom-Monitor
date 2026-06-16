<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TieuChiLuaChon extends Model
{
    use HasFactory;

    protected $table = 'tieu_chi_lua_chon';

    public $timestamps = false;

    protected $fillable = [
        'tieu_chi_id',
        'noi_dung',
        'diem_tuong_ung',
    ];
}
