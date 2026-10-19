<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SinhVien extends Model
{
    use HasFactory;

    protected $table = 'sinh_vien';

    protected $fillable = [
        'nguoi_dung_id',
        'nganh_id',
        'ma_sinh_vien',
        'nam_trung_tuyen',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(
            NguoiDung::class,
            'nguoi_dung_id'
        );
    }

    public function nganh()
    {
        return $this->belongsTo(
            Nganh::class,
            'nganh_id'
        );
    }

    public function thanhVienLops()
    {
        return $this->hasMany(
            ThanhVienLop::class,
            'sinh_vien_id'
        );
    }

    public function baiNops()
    {
        return $this->hasMany(
            BaiNop::class,
            'sinh_vien_id'
        );
    }

    public function hoatDongs()
    {
        return $this->hasMany(
            HoatDong::class,
            'sinh_vien_id'
        );
    }

    public function anhManHinhs()
    {
        return $this->hasMany(
            AnhManHinh::class,
            'sinh_vien_id'
        );
    }

    public function canhBaos()
    {
        return $this->hasMany(
            CanhBao::class,
            'sinh_vien_id'
        );
    }
}