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
        Schema::create('chi_tiet_danh_gia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_id')
                ->constrained('phieu_ren_luyen')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('tieu_chi_id')
                ->constrained('tieu_chi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('lua_chon_id')
                ->nullable()
                ->constrained('tieu_chi_lua_chon')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->text('gia_tri_nhap')->nullable();
            $table->integer('diem_sv')->default(0);
            $table->integer('diem_lop')->default(0);
            $table->integer('diem_duyet')->default(0);
            $table->text('ghi_chu')->nullable();
            $table->unique(['phieu_id', 'tieu_chi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_danh_gia');
    }
};
