<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nganh', function (Blueprint $table) {
            $table->id();

            $table->foreignId('khoa_id')
                ->constrained('khoa')
                ->cascadeOnDelete();

            $table->string('ma_nganh')->unique();
            $table->string('ten_nganh');

            $table->text('mo_ta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nganh');
    }
};