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
        Schema::create('tieu_chi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nhom_id')
                ->constrained('nhom_tieu_chi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('ma_tieu_chi');
            $table->string('ten_tieu_chi');
            $table->integer('diem_toi_da');
            $table->enum('loai_tieu_chi', ['thuong', 'phat']);
            $table->enum('loai_nhap', ['textbox', 'checkbox', 'combobox']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tieu_chi');
    }
};
