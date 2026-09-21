<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaiNop extends Model
{
    protected $table = 'bai_nop';
    protected $fillable = ['bai_tap_id', 'sinh_vien_id', 'ten_file', 'duong_dan', 'loai_file', 'thoi_gian_nop', 'trang_thai'];
    protected function casts(): array
    {
        return ['thoi_gian_nop' => 'datetime'];
    }

    public function baiTap()
    {
        return $this->belongsTo(BaiTap::class);
    }
    public function sinhVien()
    {
        return $this->belongsTo(NguoiDung::class, 'sinh_vien_id');
    }
    public function diem()
    {
        return $this->hasOne(Diem::class);
    }
}
