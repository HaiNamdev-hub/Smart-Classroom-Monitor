<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NganHangCauHoi extends Model
{
    protected $table = 'ngan_hang_cau_hoi';

    protected $fillable = [
        'nguoi_tao_id', 'noi_dung', 'loai_cau_hoi', 'chu_de', 'do_kho',
        'diem_tu_luan', 'dap_an_mau', 'trang_thai', 'version',
    ];

    protected function casts(): array
    {
        return ['diem_tu_luan' => 'decimal:2', 'version' => 'integer'];
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id');
    }

    public function dapAns(): HasMany
    {
        return $this->hasMany(NganHangDapAn::class, 'ngan_hang_cau_hoi_id')->orderBy('thu_tu')->orderBy('id');
    }
}
