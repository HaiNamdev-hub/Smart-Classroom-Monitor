<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bai_tap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lop_hoc_id')->constrained('lop_hoc')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('nguoi_tao_id')->constrained('nguoi_dung')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('tieu_de');
            $table->text('mo_ta')->nullable();
            $table->dateTime('thoi_gian_bat_dau');
            $table->dateTime('thoi_gian_ket_thuc');
            $table->dateTime('han_nop');
            $table->boolean('cho_phep_nop_tre')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('bai_tap'); }
};
