<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chuc_nang', function (Blueprint $table) {
            $table->id();

            $table->string('ma_chuc_nang')->unique();
            $table->string('ten_chuc_nang');
            $table->text('mo_ta')->nullable();

            $table->enum('trang_thai', [
                'hoat_dong',
                'tam_dung'
            ])->default('hoat_dong');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chuc_nang');
    }
};
