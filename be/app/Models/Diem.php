<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diem extends Model
{
    protected $table = 'diem';
    protected $fillable = ['bai_nop_id', 'nguoi_cham_id', 'diem', 'nhan_xet', 'ket_qua', 'thoi_gian_cham'];
    protected function casts(): array
    {
        return ['diem' => 'decimal:2', 'thoi_gian_cham' => 'datetime'];
    }

    public function baiNop()
    {
        return $this->belongsTo(BaiNop::class);
    }
    public function nguoiCham()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_cham_id');
    }
}
