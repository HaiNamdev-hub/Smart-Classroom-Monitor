<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('file_bai_tap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bai_tap_id')->constrained('bai_tap')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('ten_file');
            $table->string('duong_dan');
            $table->string('loai_file')->nullable();
            $table->unsignedBigInteger('kich_thuoc')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('file_bai_tap'); }
};
