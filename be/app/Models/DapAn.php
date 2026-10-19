<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DapAn extends Model
{
    use HasFactory;

    protected $table = 'dap_an';

    protected $fillable = [
        'cau_hoi_id',
        'noi_dung',
        'la_dap_an_dung',
        'thu_tu',
    ];

    protected $casts = [
        'la_dap_an_dung' => 'boolean',
    ];

    public function cauHoi()
    {
        return $this->belongsTo(
            CauHoi::class,
            'cau_hoi_id'
        );
    }
}