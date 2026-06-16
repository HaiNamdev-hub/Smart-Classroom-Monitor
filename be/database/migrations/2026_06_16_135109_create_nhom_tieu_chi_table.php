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
        Schema::create('nhom_tieu_chi', function (Blueprint $table) {
            $table->id();
            $table->string('ma_nhom');
            $table->string('ten_nhom');
            $table->integer('diem_toi_da');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhom_tieu_chi');
    }
};
