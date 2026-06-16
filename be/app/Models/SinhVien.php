<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SinhVien extends Model
{
    use HasFactory;

    protected $table = 'sinh_vien';

    protected $fillable = [
        'ma_sinh_vien',
        'nam_trung_tuyen',
        'user_id',
        'lop_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(NguoiDung::class, 'user_id');
    }

    public function lop()
    {
        return $this->belongsTo(Lop::class, 'lop_id');
    }

    public function phieuRenLuyens()
    {
        return $this->hasMany(PhieuRenLuyen::class, 'sinh_vien_id');
    }
}
