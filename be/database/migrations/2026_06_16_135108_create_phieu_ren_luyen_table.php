<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phieu_ren_luyen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sinh_vien_id')
                ->constrained('sinh_vien')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('dot_danh_gia_id')
                ->constrained('dot_danh_gia')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->integer('tong_diem_sv')->default(0);
            $table->integer('tong_diem_lop')->default(0);
            $table->integer('tong_diem_cuoi')->default(0);
            $table->enum('xep_loai', [
                'xuat_sac',
                'tot',
                'kha',
                'trung_binh',
                'yeu',
                'kem',
            ])->nullable();
            $table->enum('trang_thai', [
                'draft',
                'submitted',
                'class_reviewed',
                'advisor_reviewed',
                'approved',
            ])->default('draft');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['sinh_vien_id', 'dot_danh_gia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phieu_ren_luyen');
    }
};
