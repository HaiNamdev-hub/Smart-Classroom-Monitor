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
        Schema::create('sinh_vien', function (Blueprint $table) {
            $table->id();
            $table->string('ma_sinh_vien')->unique();
            $table->integer('nam_trung_tuyen');
            $table->foreignId('user_id')
                ->constrained('nguoi_dung')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('lop_id')
                ->constrained('lop')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sinh_vien');
    }
};
