<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VaiTro extends Model
{
    protected $table = 'vai_tro';
    protected $fillable = ['ten_vai_tro', 'mo_ta'];

    public function nguoiDungs(): HasMany
    {
        return $this->hasMany(NguoiDung::class, 'vai_tro_id');
    }
}
