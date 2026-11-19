<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ExamMode
{
    public const ONLINE_CAMERA_REQUIRED = 'ONLINE_CAMERA_REQUIRED';
    public const LAB_NO_CAMERA = 'LAB_NO_CAMERA';

    /** Legacy assignments remain camera-required until explicitly configured otherwise. */
    public static function forAssignment(int $baiTapId): string
    {
        if (!Schema::hasTable('bai_tap_hinh_thuc_kiem_tra')) {
            return self::ONLINE_CAMERA_REQUIRED;
        }

        $mode = DB::table('bai_tap_hinh_thuc_kiem_tra')
            ->where('bai_tap_id', $baiTapId)
            ->value('hinh_thuc');

        return $mode === self::LAB_NO_CAMERA
            ? self::LAB_NO_CAMERA
            : self::ONLINE_CAMERA_REQUIRED;
    }

    public static function requiresCamera(int $baiTapId): bool
    {
        return self::forAssignment($baiTapId) === self::ONLINE_CAMERA_REQUIRED;
    }
}
