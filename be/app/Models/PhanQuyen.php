<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhanQuyen extends Model
{
    protected $table = 'phan_quyen';

    protected $fillable = [
        'vai_tro_id',
        'chuc_nang_id',
    ];

    public function vaiTro()
    {
        return $this->belongsTo(
            VaiTro::class,
            'vai_tro_id'
        );
    }

    public function chucNang()
    {
        return $this->belongsTo(
            ChucNang::class,
            'chuc_nang_id'
        );
    }
}