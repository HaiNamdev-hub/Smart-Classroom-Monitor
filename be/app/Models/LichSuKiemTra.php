<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LichSuKiemTra extends Model
{
    use HasFactory;

    protected $table = 'lich_su_kiem_tra';

    protected $fillable = [
        'phieu_id',
        'nguoi_kiem_tra_id',
        'vai_tro_kiem_tra',
        'hanh_dong',
        'ghi_chu',
    ];

    public function phieu()
    {
        return $this->belongsTo(PhieuRenLuyen::class, 'phieu_id');
    }

    public function nguoiKiemTra()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_kiem_tra_id');
    }
}
