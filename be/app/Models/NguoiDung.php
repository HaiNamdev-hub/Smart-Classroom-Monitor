<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class NguoiDung extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'nguoi_dung';
    protected $fillable = ['ho_ten', 'email', 'password', 'vai_tro_id', 'trang_thai'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function vaiTro()
    {
        return $this->belongsTo(VaiTro::class, 'vai_tro_id');
    }
    public function lopGiangDays()
    {
        return $this->hasMany(LopHoc::class, 'giang_vien_id');
    }
    public function thanhVienLops()
    {
        return $this->hasMany(ThanhVienLop::class, 'sinh_vien_id');
    }
    public function baiTapDaTaos()
    {
        return $this->hasMany(BaiTap::class, 'nguoi_tao_id');
    }
    public function baiNops()
    {
        return $this->hasMany(BaiNop::class, 'sinh_vien_id');
    }
    public function diemDaChams()
    {
        return $this->hasMany(Diem::class, 'nguoi_cham_id');
    }
    public function phienDaTaos()
    {
        return $this->hasMany(PhienGiamSat::class, 'nguoi_tao_id');
    }
}
