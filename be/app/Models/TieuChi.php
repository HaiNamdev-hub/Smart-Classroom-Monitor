<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TieuChi extends Model
{
    use HasFactory;

    protected $table = 'tieu_chi';

    public $timestamps = false;

    protected $fillable = [
        'nhom_id',
        'parent_id',
        'ma_tieu_chi',
        'ten_tieu_chi',
        'diem_toi_da',
        'loai_tieu_chi',
        'loai_nhap',
        'bat_buoc_minh_chung',
        'thu_tu',
    ];

    
   
}