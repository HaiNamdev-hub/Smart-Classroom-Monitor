<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ngan_hang_de_preview', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_tao_id')->constrained('nguoi_dung')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('lop_hoc_id')->constrained('lop_hoc')->cascadeOnUpdate()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->char('idempotency_key', 36)->unique();
            $table->json('cau_hinh');
            $table->json('snapshot_cau_hoi');
            $table->timestamp('het_han_luc');
            $table->foreignId('bai_tap_id')->nullable()->constrained('bai_tap')->nullOnDelete();
            $table->timestamps();

            $table->index(['nguoi_tao_id', 'het_han_luc'], 'question_bank_preview_owner_expiry_idx');
        });

        Schema::create('ngan_hang_de_tao', function (Blueprint $table) {
            $table->foreignId('bai_tap_id')->primary()->constrained('bai_tap')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('nguoi_tao_id')->constrained('nguoi_dung')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedSmallInteger('thoi_luong_phut');
            $table->enum('trang_thai', ['nhap', 'da_cong_bo'])->default('nhap');
            $table->timestamps();
            $table->index(['nguoi_tao_id', 'trang_thai'], 'question_bank_exam_owner_status_idx');
        });

        Schema::create('cau_hoi_ngan_hang_snapshot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cau_hoi_id')->unique()->constrained('cau_hoi')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ngan_hang_cau_hoi_id')->constrained('ngan_hang_cau_hoi')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('ngan_hang_version');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cau_hoi_ngan_hang_snapshot');
        Schema::dropIfExists('ngan_hang_de_tao');
        Schema::dropIfExists('ngan_hang_de_preview');
    }
};
