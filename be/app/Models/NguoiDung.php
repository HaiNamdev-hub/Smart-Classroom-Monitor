<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class NguoiDung extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'nguoi_dung';

    protected $fillable = [
        'ho_ten',
        'email',

        'email_verified_at',
        'verification_token',

        'password',

        'so_dien_thoai',
        'ngay_sinh',
        'avatar',
        'vai_tro_id',
        'trang_thai',
    ];
    protected $hidden = [
        'password',
    ];

   protected function casts(): array
{
    return [
        'ngay_sinh' => 'date',

        'email_verified_at' => 'datetime',

        'password' => 'hashed',
    ];
}
}
