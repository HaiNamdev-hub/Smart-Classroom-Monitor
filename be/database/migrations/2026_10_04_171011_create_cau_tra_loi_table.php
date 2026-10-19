<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cau_tra_loi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bai_nop_id')
                ->constrained('bai_nop')
                ->cascadeOnDelete();

            $table->foreignId('cau_hoi_id')
                ->constrained('cau_hoi')
                ->cascadeOnDelete();

            $table->foreignId('dap_an_id')
                ->nullable()
                ->constrained('dap_an')
                ->nullOnDelete();

            $table->text('noi_dung_tra_loi')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'bai_nop_id',
                'cau_hoi_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cau_tra_loi');
    }
};