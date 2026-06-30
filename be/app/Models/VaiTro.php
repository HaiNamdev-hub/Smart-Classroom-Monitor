<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaiTro extends Model
{
    use HasFactory;

    protected $table = 'vai_tro';

    protected $fillable = [
        'ten_vai_tro',
        'mo_ta',
    ];

    public function nguoiDungs()
    {
        return $this->hasMany(NguoiDung::class, 'vai_tro_id');
    }

    public function phanQuyens()
    {
        return $this->hasMany(PhanQuyen::class, 'vai_tro_id');
    }
}