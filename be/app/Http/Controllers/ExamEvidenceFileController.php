<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ExamEvidenceFileController extends Controller
{
    public function show(Request $request, int $imageId): Response
    {
        abort_unless($request->user(), 401);

        $teacherId = (int) $request->user()->id;

        $hasAssignmentId = Schema::hasColumn('anh_man_hinh', 'bai_tap_id');
        $imageQuery = DB::table('anh_man_hinh as amh')
            ->leftJoin(
                'phien_giam_sat as pg',
                'pg.id',
                '=',
                'amh.phien_id'
            )
            ->leftJoin(
                'lop_hoc as lhp',
                'lhp.id',
                '=',
                'pg.lop_hoc_id'
            )
            ->where('amh.id', $imageId);

        if ($hasAssignmentId) {
            $imageQuery
                ->leftJoin('bai_tap as bt', 'bt.id', '=', 'amh.bai_tap_id')
                ->leftJoin('lop_hoc as lhb', 'lhb.id', '=', 'bt.lop_hoc_id')
                ->where(function ($query) use ($teacherId) {
                    $query->where('lhp.giang_vien_id', $teacherId)
                        ->orWhere('lhb.giang_vien_id', $teacherId);
                })
                ->select(
                    'amh.duong_dan',
                    Schema::hasColumn('anh_man_hinh', 'storage_disk') ? 'amh.storage_disk' : DB::raw("'public' as storage_disk"),
                    Schema::hasColumn('anh_man_hinh', 'mime_type') ? 'amh.mime_type' : DB::raw('NULL as mime_type'),
                    Schema::hasColumn('anh_man_hinh', 'expires_at') ? 'amh.expires_at' : DB::raw('NULL as expires_at'),
                    Schema::hasColumn('anh_man_hinh', 'nguon') ? 'amh.nguon' : DB::raw("'man_hinh' as nguon"),
                    'amh.phien_id', 'amh.bai_tap_id',
                    'pg.lop_hoc_id as phien_lop_id', 'bt.lop_hoc_id as bai_tap_lop_id'
                );
        } else {
            $imageQuery
                ->where('lhp.giang_vien_id', $teacherId)
                ->select(
                    'amh.duong_dan',
                    DB::raw("'public' as storage_disk"),
                    DB::raw('NULL as mime_type'),
                    DB::raw('NULL as expires_at'),
                    DB::raw("'man_hinh' as nguon"),
                    'amh.phien_id',
                    DB::raw('NULL as bai_tap_id'),
                    'pg.lop_hoc_id as phien_lop_id',
                    DB::raw('NULL as bai_tap_lop_id')
                );
        }

        $image = $imageQuery->first();

        abort_unless($image, 404);

        // Nếu cùng liên kết phiên và bài kiểm tra,
        // chúng phải thuộc cùng một lớp.
        if ($image->phien_id && $image->bai_tap_id) {
            abort_unless(
                $image->phien_lop_id !== null &&
                $image->bai_tap_lop_id !== null &&
                (int) $image->phien_lop_id ===
                    (int) $image->bai_tap_lop_id,
                404
            );
        }

        // Kiểm tra thời hạn lưu ảnh webcam AI.
        if ($image->nguon === 'webcam_ai') {
            abort_if(
                $image->expires_at &&
                    now()->greaterThan($image->expires_at),
                404
            );
        }

        $diskName = $image->storage_disk ?: 'public';

        // Ảnh webcam AI phải được lưu ở disk riêng tư.
        // Thay 'local' nếu dự án sử dụng tên disk private khác.
        if ($image->nguon === 'webcam_ai') {
            abort_unless($diskName === 'local', 404);
        }

        $path = (string) $image->duong_dan;

        abort_if(
            $path === '' ||
                str_starts_with($path, '/') ||
                str_contains($path, '..') ||
                str_contains($path, '\\'),
            404
        );

        $disk = Storage::disk($diskName);

        abort_unless(
            $disk->exists($path),
            404,
            'Ảnh bằng chứng không còn trên hệ thống.'
        );

        // The selected metadata columns depend on the installed schema version.
        $mimeType = data_get($image, 'mime_type');

        // Evidence images are capped at 512 KiB on upload. Returning the
        // bytes as a regular response gives the dev proxy an explicit,
        // fixed-length HTTP body instead of a streamed/chunked response.
        $contents = $disk->get($path);
        abort_unless(is_string($contents), 404);

        return response($contents, 200, [
            'Content-Type' => is_string($mimeType) && $mimeType !== ''
                ? $mimeType
                : 'image/jpeg',
            'Content-Length' => (string) strlen($contents),

            'Cache-Control' =>
                'private, no-store, max-age=0',

            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
