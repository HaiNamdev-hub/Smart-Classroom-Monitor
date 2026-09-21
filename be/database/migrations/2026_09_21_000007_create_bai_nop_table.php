<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bai_nop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bai_tap_id')->constrained('bai_tap')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('nguoi_dung')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('ten_file');
            $table->string('duong_dan');
            $table->string('loai_file')->nullable();
            $table->dateTime('thoi_gian_nop');
            $table->enum('trang_thai', ['dung_han', 'tre_han']);
            $table->timestamps();
            $table->unique(['bai_tap_id', 'sinh_vien_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('bai_nop'); }
};
