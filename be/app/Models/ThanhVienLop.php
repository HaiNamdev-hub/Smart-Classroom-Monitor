<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhVienLop extends Model
{
    protected $table = 'thanh_vien_lop';
    protected $fillable = ['lop_hoc_id', 'sinh_vien_id', 'trang_thai'];

    public function lopHoc()
    {
        return $this->belongsTo(LopHoc::class);
    }
    public function sinhVien()
{
    return $this->belongsTo(
        SinhVien::class,
        'sinh_vien_id',
        'nguoi_dung_id'
    );
}
}
