<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileBaiTap extends Model
{
    protected $table = 'file_bai_tap';
    protected $fillable = ['bai_tap_id', 'ten_file', 'duong_dan', 'loai_file', 'kich_thuoc'];

    public function baiTap()
    {
        return $this->belongsTo(BaiTap::class);
    }
}
