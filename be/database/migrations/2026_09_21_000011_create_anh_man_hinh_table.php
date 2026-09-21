<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anh_man_hinh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phien_id')->constrained('phien_giam_sat')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('nguoi_dung')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('duong_dan');
            $table->dateTime('thoi_gian_chup');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void { Schema::dropIfExists('anh_man_hinh'); }
};
