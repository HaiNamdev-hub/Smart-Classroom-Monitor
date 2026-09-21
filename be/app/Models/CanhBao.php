<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanhBao extends Model
{
    protected $table = 'canh_bao';
    protected $fillable = ['phien_id', 'sinh_vien_id', 'loai_canh_bao', 'noi_dung', 'thoi_gian', 'da_xu_ly'];
    protected function casts(): array
    {
        return ['thoi_gian' => 'datetime', 'da_xu_ly' => 'boolean'];
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
