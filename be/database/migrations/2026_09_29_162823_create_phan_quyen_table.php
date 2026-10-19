<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phan_quyen', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vai_tro_id')
                ->constrained('vai_tro')
                ->cascadeOnDelete();

            $table->foreignId('chuc_nang_id')
                ->constrained('chuc_nang')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'vai_tro_id',
                'chuc_nang_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phan_quyen');
    }
};
