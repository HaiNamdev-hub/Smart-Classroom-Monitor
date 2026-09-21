<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('canh_bao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phien_id')->constrained('phien_giam_sat')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('nguoi_dung')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('loai_canh_bao', ['ung_dung_khong_cho_phep', 'website_khong_cho_phep', 'copy_paste', 'roi_phien', 'mat_ket_noi']);
            $table->text('noi_dung');
            $table->dateTime('thoi_gian');
            $table->boolean('da_xu_ly')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('canh_bao'); }
};
