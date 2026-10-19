<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaiTap extends Model
{
    protected $table = 'bai_tap';
    protected $fillable = ['lop_hoc_id', 'nguoi_tao_id', 'tieu_de', 'mo_ta', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc', 'han_nop', 'cho_phep_nop_tre'];
    protected function casts(): array
    {
        return ['thoi_gian_bat_dau' => 'datetime', 'thoi_gian_ket_thuc' => 'datetime', 'han_nop' => 'datetime', 'cho_phep_nop_tre' => 'boolean'];
    }

    public function lopHoc()
    {
        return $this->belongsTo(LopHoc::class);
    }
    public function nguoiTao()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id');
    }
    public function files()
    {
        return $this->hasMany(FileBaiTap::class);
    }
    public function baiNops()
    {
        return $this->hasMany(BaiNop::class);
    }
    public function phienGiamSats()
    {
        return $this->hasMany(PhienGiamSat::class);
    }
    public function sinhVien()
    {
        return $this->belongsTo(
            SinhVien::class,
            'sinh_vien_id'
        );
    }
    public function cauHois()
    {
        return $this->hasMany(
            CauHoi::class,
            'bai_tap_id'
        )->orderBy('thu_tu');
    }
}
