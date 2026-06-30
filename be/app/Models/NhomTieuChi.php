<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhomTieuChi extends Model
{
    use HasFactory;

    protected $table = 'nhom_tieu_chi';

    public $timestamps = false;

    protected $fillable = [
        'ma_nhom',
        'ten_nhom',
        'diem_toi_da',
        'thu_tu',
    ];
}