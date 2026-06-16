<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhieuRenLuyen extends Model
{
    use HasFactory;

    protected $table = 'phieu_ren_luyen';

    public const UPDATED_AT = null;

    protected $fillable = [
        'sinh_vien_id',
        'dot_danh_gia_id',
        'tong_diem_sv',
        'tong_diem_lop',
        'tong_diem_cuoi',
        'xep_loai',
        'trang_thai',
    ];
}
