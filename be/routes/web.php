<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Smart Classroom Monitor Backend API is running!',
        'timestamp' => now()->toIso8601String(),
    ]);
});
