<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phien_giam_sat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lop_hoc_id')->constrained('lop_hoc')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('bai_tap_id')->nullable()->constrained('bai_tap')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('nguoi_tao_id')->constrained('nguoi_dung')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('ten_phien');
            $table->dateTime('thoi_gian_bat_dau');
            $table->dateTime('thoi_gian_ket_thuc')->nullable();
            $table->enum('trang_thai', ['chua_bat_dau', 'dang_dien_ra', 'da_ket_thuc']);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('phien_giam_sat'); }
};
