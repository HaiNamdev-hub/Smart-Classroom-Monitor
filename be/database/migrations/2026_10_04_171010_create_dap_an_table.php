<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dap_an', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cau_hoi_id')
                ->constrained('cau_hoi')
                ->cascadeOnDelete();

            $table->text('noi_dung');

            $table->boolean('la_dap_an_dung')
                ->default(false);

            $table->integer('thu_tu')
                ->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dap_an');
    }
};