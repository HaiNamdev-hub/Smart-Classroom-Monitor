<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lop extends Model
{
    use HasFactory;

    protected $table = 'lop';

    protected $fillable = [
        'ma_lop',
        'ten_lop',
        'khoa_id',
        'nganh_id',
        'co_van_id',
        'lop_truong_id',
        'bi_thu_id',
    ];

    public function khoa()
    {
        return $this->belongsTo(Khoa::class, 'khoa_id');
    }

    public function nganh()
    {
        return $this->belongsTo(Nganh::class, 'nganh_id');
    }

    public function coVan()
    {
        return $this->belongsTo(NguoiDung::class, 'co_van_id');
    }

    public function lopTruong()
    {
        return $this->belongsTo(SinhVien::class, 'lop_truong_id');
    }

    public function biThu()
    {
        return $this->belongsTo(SinhVien::class, 'bi_thu_id');
    }

    public function sinhViens()
    {
        return $this->hasMany(SinhVien::class, 'lop_id');
    }
}