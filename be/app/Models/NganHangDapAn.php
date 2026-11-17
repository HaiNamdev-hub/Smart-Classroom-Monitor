<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NganHangDapAn extends Model
{
    protected $table = 'ngan_hang_dap_an';

    protected $fillable = ['ngan_hang_cau_hoi_id', 'noi_dung', 'la_dap_an_dung', 'thu_tu'];

    protected function casts(): array
    {
        return ['la_dap_an_dung' => 'boolean', 'thu_tu' => 'integer'];
    }

    public function cauHoi(): BelongsTo
    {
        return $this->belongsTo(NganHangCauHoi::class, 'ngan_hang_cau_hoi_id');
    }
}
