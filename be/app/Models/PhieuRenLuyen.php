<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhieuRenLuyen extends Model
{
    use HasFactory;

    protected $table = 'phieu_ren_luyen';

    protected $fillable = [
        'sinh_vien_id',
        'dot_danh_gia_id',
        'tong_diem_sv',
        'tong_diem_lop',
        'tong_diem_cuoi',
        'xep_loai',
        'trang_thai',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'sinh_vien_id');
    }

    public function dotDanhGia()
    {
        return $this->belongsTo(DotDanhGia::class, 'dot_danh_gia_id');
    }

    public function chiTietDanhGias()
    {
        return $this->hasMany(ChiTietDanhGia::class, 'phieu_id');
    }

    public function lichSuDuyets()
    {
        return $this->hasMany(LichSuDuyet::class, 'phieu_id');
    }
}
