<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhienGiamSat extends Model
{
    protected $table = 'phien_giam_sat';
    protected $fillable = ['lop_hoc_id', 'bai_tap_id', 'nguoi_tao_id', 'ten_phien', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc', 'trang_thai'];
    protected function casts(): array
    {
        return ['thoi_gian_bat_dau' => 'datetime', 'thoi_gian_ket_thuc' => 'datetime'];
    }

    public function lopHoc()
    {
        return $this->belongsTo(LopHoc::class);
    }
    public function baiTap()
    {
        return $this->belongsTo(BaiTap::class);
    }
    public function nguoiTao()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id');
    }
    public function hoatDongs()
    {
        return $this->hasMany(HoatDong::class, 'phien_id');
    }
    public function anhManHinhs()
    {
        return $this->hasMany(AnhManHinh::class, 'phien_id');
    }
    public function canhBaos()
    {
        return $this->hasMany(CanhBao::class, 'phien_id');
    }
}
