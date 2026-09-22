<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuanTriVien
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->trang_thai !== 'hoat_dong' || $user->vaiTro?->ten_vai_tro !== 'quan_tri_vien') {
            return response()->json(['message' => 'Bạn không có quyền truy cập trang quản trị.'], 403);
        }

        return $next($request);
    }
}
