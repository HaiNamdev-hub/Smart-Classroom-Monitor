<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anh_man_hinh', function (Blueprint $table) {
            $table->unsignedBigInteger('phien_id')->nullable()->change();
            $table->foreignId('bai_tap_id')->nullable()->after('sinh_vien_id')->constrained('bai_tap')->nullOnDelete();
            $table->uuid('event_id')->nullable()->unique()->after('bai_tap_id');
            $table->string('nguon', 30)->default('man_hinh')->after('event_id');
            $table->string('loai_canh_bao', 50)->nullable()->after('nguon');
            $table->string('storage_disk', 30)->default('public')->after('duong_dan');
            $table->string('mime_type', 100)->nullable()->after('storage_disk');
            $table->unsignedInteger('kich_thuoc_bytes')->nullable()->after('mime_type');
            $table->timestamp('expires_at')->nullable()->after('thoi_gian_chup');
            $table->index(['bai_tap_id', 'sinh_vien_id', 'thoi_gian_chup'], 'anh_man_hinh_exam_student_time_idx');
            $table->index(['phien_id', 'nguon', 'thoi_gian_chup'], 'anh_man_hinh_session_source_time_idx');
        });

        Schema::table('canh_bao_camera', function (Blueprint $table) {
            $table->string('evidence_status', 20)->default('none')->after('loai_canh_bao');
        });
    }

    public function down(): void
    {
        Schema::table('canh_bao_camera', function (Blueprint $table) {
            $table->dropColumn('evidence_status');
        });

        Schema::table('anh_man_hinh', function (Blueprint $table) {
            $table->dropIndex('anh_man_hinh_exam_student_time_idx');
            $table->dropIndex('anh_man_hinh_session_source_time_idx');
            $table->dropUnique(['event_id']);
            $table->dropForeign(['bai_tap_id']);
            $table->dropColumn([
                'bai_tap_id', 'event_id', 'nguon', 'loai_canh_bao',
                'storage_disk', 'mime_type', 'kich_thuoc_bytes', 'expires_at',
            ]);
        });
    }
};
