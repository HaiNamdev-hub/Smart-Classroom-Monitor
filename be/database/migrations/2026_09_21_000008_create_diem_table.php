<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('diem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bai_nop_id')->unique()->constrained('bai_nop')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('nguoi_cham_id')->constrained('nguoi_dung')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('diem', 5, 2);
            $table->text('nhan_xet')->nullable();
            $table->enum('ket_qua', ['dat', 'khong_dat'])->nullable();
            $table->dateTime('thoi_gian_cham')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('diem'); }
};
