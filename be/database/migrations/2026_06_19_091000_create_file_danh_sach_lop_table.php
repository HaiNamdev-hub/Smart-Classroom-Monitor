<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_danh_sach_lop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lop_id')
                ->constrained('lop')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('nguoi_tai_id')
                ->constrained('nguoi_dung')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('ten_file');
            $table->string('duong_dan');
            $table->enum('trang_thai', ['cho_xu_ly', 'da_xu_ly', 'loi'])->default('cho_xu_ly');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_danh_sach_lop');
    }
};
