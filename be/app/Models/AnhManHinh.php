<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnhManHinh extends Model
{
    protected $table = 'anh_man_hinh';
    public const UPDATED_AT = null;
    protected $fillable = ['phien_id', 'sinh_vien_id', 'duong_dan', 'thoi_gian_chup'];
    protected function casts(): array
    {
        return ['thoi_gian_chup' => 'datetime'];
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
