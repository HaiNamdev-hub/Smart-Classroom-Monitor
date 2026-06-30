<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhom_tieu_chi', function (Blueprint $table) {
            $table->id();
            $table->string('ma_nhom')->unique();
            $table->string('ten_nhom');
            $table->integer('diem_toi_da');
            $table->integer('thu_tu')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhom_tieu_chi');
    }
};