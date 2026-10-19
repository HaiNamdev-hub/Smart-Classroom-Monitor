<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinh_vien', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nguoi_dung_id')
                ->unique()
                ->constrained('nguoi_dung')
                ->cascadeOnDelete();

            $table->foreignId('nganh_id')
                ->constrained('nganh')
                ->restrictOnDelete();

            $table->string('ma_sinh_vien')->unique();

            $table->unsignedSmallInteger('nam_trung_tuyen');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinh_vien');
    }
};