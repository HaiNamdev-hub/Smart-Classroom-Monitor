<?php

use App\Http\Controllers\AnhManHinhController;
use App\Http\Controllers\BaiNopController;
use App\Http\Controllers\BaiTapController;
use App\Http\Controllers\CanhBaoController;
use App\Http\Controllers\DiemController;
use App\Http\Controllers\FileBaiTapController;
use App\Http\Controllers\HoatDongController;
use App\Http\Controllers\LopHocController;
use App\Http\Controllers\NguoiDungController;
use App\Http\Controllers\PhienGiamSatController;
use App\Http\Controllers\ThanhVienLopController;
use App\Http\Controllers\VaiTroController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'service' => 'smart-classroom-monitor',
    'status' => 'ok',
]));

