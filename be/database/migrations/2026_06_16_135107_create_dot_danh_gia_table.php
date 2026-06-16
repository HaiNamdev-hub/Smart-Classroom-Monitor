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
        Schema::create('dot_danh_gia', function (Blueprint $table) {
            $table->id();
            $table->string('nam_hoc');
            $table->unsignedTinyInteger('hoc_ky');
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc');
            $table->enum('trang_thai', ['nhap', 'dang_mo', 'da_dong'])->default('nhap');
            $table->timestamps();
            $table->unique(['nam_hoc', 'hoc_ky']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dot_danh_gia');
    }
};
