<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hoat_dong', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phien_id')->constrained('phien_giam_sat')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('nguoi_dung')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('loai_hoat_dong', ['ung_dung', 'website', 'copy_paste', 'tham_gia', 'roi_phien']);
            $table->string('ten_ung_dung')->nullable();
            $table->string('website_url')->nullable();
            $table->dateTime('thoi_gian');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void { Schema::dropIfExists('hoat_dong'); }
};
