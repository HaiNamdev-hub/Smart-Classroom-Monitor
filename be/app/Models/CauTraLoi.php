<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CauTraLoi extends Model
{
    use HasFactory;

    protected $table = 'cau_tra_loi';

    protected $fillable = [
        'bai_nop_id',
        'cau_hoi_id',
        'dap_an_id',
        'noi_dung_tra_loi',
        'diem',
    ];

    protected $casts = ['diem' => 'decimal:2'];

    public function baiNop()
    {
        return $this->belongsTo(
            BaiNop::class,
            'bai_nop_id'
        );
    }

    public function cauHoi()
    {
        return $this->belongsTo(
            CauHoi::class,
            'cau_hoi_id'
        );
    }

    public function dapAn()
    {
        return $this->belongsTo(
            DapAn::class,
            'dap_an_id'
        );
    }
}
