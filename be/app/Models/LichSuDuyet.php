<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LichSuDuyet extends Model
{
    use HasFactory;

    protected $table = 'lich_su_duyet';

    public const UPDATED_AT = null;

    protected $fillable = [
        'phieu_id',
        'nguoi_duyet_id',
        'vai_tro_duyet',
        'ghi_chu',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function phieu()
    {
        return $this->belongsTo(PhieuRenLuyen::class, 'phieu_id');
    }

    public function nguoiDuyet()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_duyet_id');
    }
}
