<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lop extends Model
{
    use HasFactory;

    protected $table = 'lop';

    public $timestamps = false;

    protected $fillable = [
        'ma_lop',
        'ten_lop',
        'khoa_id',
        'nganh_id',
        'co_van_id',
        'lop_truong_id',
        'bi_thu_id',
    ];
}
