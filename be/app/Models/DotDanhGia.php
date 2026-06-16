<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DotDanhGia extends Model
{
    use HasFactory;

    protected $table = 'dot_danh_gia';

    public $timestamps = false;

    protected $fillable = [
        'nam_hoc',
        'hoc_ky',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
        ];
    }
}
