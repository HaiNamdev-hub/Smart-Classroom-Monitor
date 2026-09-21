<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nguoi_dung', function (Blueprint $table) {
            $table->id();
            $table->string('ho_ten');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('vai_tro_id')->constrained('vai_tro')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('trang_thai', ['hoat_dong', 'khong_hoat_dong', 'bi_khoa']);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('nguoi_dung'); }
};
