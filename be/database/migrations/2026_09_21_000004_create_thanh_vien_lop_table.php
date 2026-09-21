<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('thanh_vien_lop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lop_hoc_id')->constrained('lop_hoc')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('nguoi_dung')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('trang_thai', ['dang_hoc', 'da_roi_lop']);
            $table->timestamps();
            $table->unique(['lop_hoc_id', 'sinh_vien_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('thanh_vien_lop'); }
};
