<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChiTietDanhGia extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_danh_gia';

    public $timestamps = false;

    protected $fillable = [
        'phieu_id',
        'tieu_chi_id',
        'lua_chon_id',
        'gia_tri_nhap',
        'diem_sv',
        'diem_lop',
        'diem_duyet',
        'ghi_chu',
    ];
}
