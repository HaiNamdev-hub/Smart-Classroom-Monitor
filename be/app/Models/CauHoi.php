<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CauHoi extends Model
{
    use HasFactory;

    protected $table = 'cau_hoi';

    protected $fillable = [
        'bai_tap_id',
        'noi_dung',
        'loai_cau_hoi',
        'diem',
        'thu_tu',
    ];

    public function baiTap()
    {
        return $this->belongsTo(
            BaiTap::class,
            'bai_tap_id'
        );
    }

    public function dapAns()
    {
        return $this->hasMany(
            DapAn::class,
            'cau_hoi_id'
        )->orderBy('thu_tu');
    }

    public function cauTraLois()
    {
        return $this->hasMany(
            CauTraLoi::class,
            'cau_hoi_id'
        );
    }
}