<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SinhVien extends Model
{
    use HasFactory;

    protected $table = 'sinh_vien';

    public const UPDATED_AT = null;

    protected $fillable = [
        'ma_sinh_vien',
        'nam_trung_tuyen',
        'user_id',
        'lop_id',
    ];
}
