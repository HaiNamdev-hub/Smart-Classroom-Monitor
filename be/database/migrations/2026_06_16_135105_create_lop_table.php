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
        Schema::create('lop', function (Blueprint $table) {
            $table->id();
            $table->string('ma_lop')->unique();
            $table->string('ten_lop');
            $table->foreignId('khoa_id')
                ->constrained('khoa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('nganh_id')
                ->constrained('nganh')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('co_van_id')
                ->nullable()
                ->constrained('nguoi_dung')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('lop_truong_id')
                ->nullable()
                ->constrained('nguoi_dung')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('bi_thu_id')
                ->nullable()
                ->constrained('nguoi_dung')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lop');
    }
};
