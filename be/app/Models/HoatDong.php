<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoatDong extends Model
{
    protected $table = 'hoat_dong';
    public const UPDATED_AT = null;
    protected $fillable = ['phien_id', 'sinh_vien_id', 'loai_hoat_dong', 'ten_ung_dung', 'website_url', 'thoi_gian'];
    protected function casts(): array
    {
        return ['thoi_gian' => 'datetime'];
    }

    public function phien()
    {
        return $this->belongsTo(PhienGiamSat::class, 'phien_id');
    }
    public function sinhVien()
    {
        return $this->belongsTo(NguoiDung::class, 'sinh_vien_id');
    }
}
