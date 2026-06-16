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
        Schema::create('tieu_chi_lua_chon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tieu_chi_id')
                ->constrained('tieu_chi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('noi_dung');
            $table->integer('diem_tuong_ung');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tieu_chi_lua_chon');
    }
};
