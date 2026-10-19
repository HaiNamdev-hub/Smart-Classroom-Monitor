<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nganh extends Model
{
    use HasFactory;

    protected $table = 'nganh';

    protected $fillable = [
        'khoa_id',
        'ma_nganh',
        'ten_nganh',
        'mo_ta',
    ];

    public function khoa()
    {
        return $this->belongsTo(
            Khoa::class,
            'khoa_id'
        );
    }

    public function sinhViens()
    {
        return $this->hasMany(
            SinhVien::class,
            'nganh_id'
        );
    }
}