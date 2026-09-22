<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LopHoc extends Model
{
    protected $table = 'lop_hoc';
    protected $fillable = ['ma_lop', 'ten_lop', 'giang_vien_id', 'mo_ta'];

    public function giangVien()
    {
        return $this->belongsTo(NguoiDung::class, 'giang_vien_id');
    }
    public function thanhViens()
    {
        return $this->hasMany(ThanhVienLop::class, 'lop_hoc_id');
    }
    public function baiTaps()
    {
        return $this->hasMany(BaiTap::class, 'lop_hoc_id');
    }
    public function phienGiamSats()
    {
        return $this->hasMany(PhienGiamSat::class, 'lop_hoc_id');
    }
}
