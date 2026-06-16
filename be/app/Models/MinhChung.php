<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinhChung extends Model
{
    use HasFactory;

    protected $table = 'minh_chung';

    public const UPDATED_AT = null;

    protected $fillable = [
        'chi_tiet_id',
        'ten_file',
        'duong_dan',
        'trang_thai',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function chiTiet()
    {
        return $this->belongsTo(ChiTietDanhGia::class, 'chi_tiet_id');
    }
}
