<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Khoa extends Model
{
    use HasFactory;

    protected $table = 'khoa';

    protected $fillable = [
        'ma_khoa',
        'ten_khoa',
        'mo_ta',
    ];

    public function nganhs()
    {
        return $this->hasMany(
            Nganh::class,
            'khoa_id'
        );
    }
}