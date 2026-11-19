<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('su_kien_bao_ve_bai_kiem_tra', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->foreignId('bai_tap_id')->constrained('bai_tap')->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('sinh_vien')->cascadeOnDelete();
            $table->foreignId('phien_id')->nullable()->constrained('phien_giam_sat')->nullOnDelete();
            $table->string('loai_su_kien', 50);
            $table->dateTime('thoi_gian_su_kien');
            $table->dateTime('thoi_gian_trinh_duyet')->nullable();
            $table->timestamps();

            $table->index(['bai_tap_id', 'sinh_vien_id', 'created_at'], 'exam_protection_student_time_idx');
            $table->index(['phien_id', 'created_at'], 'exam_protection_session_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('su_kien_bao_ve_bai_kiem_tra');
    }
};
