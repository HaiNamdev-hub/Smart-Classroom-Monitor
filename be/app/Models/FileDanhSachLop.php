<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileDanhSachLop extends Model
{
    use HasFactory;

    protected $table = 'file_danh_sach_lop';

    protected $fillable = [
        'lop_id',
        'nguoi_tai_id',
        'ten_file',
        'duong_dan',
        'trang_thai',
    ];

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id');
    }

    public function nguoiTai()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tai_id');
    }
}
