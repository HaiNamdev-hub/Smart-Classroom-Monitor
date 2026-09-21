<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lop_hoc', function (Blueprint $table) {
            $table->id();
            $table->string('ma_lop')->unique();
            $table->string('ten_lop');
            $table->foreignId('giang_vien_id')->constrained('nguoi_dung')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('mo_ta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('lop_hoc'); }
};
