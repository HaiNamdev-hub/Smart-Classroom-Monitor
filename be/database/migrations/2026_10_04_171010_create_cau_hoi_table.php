<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cau_hoi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bai_tap_id')
                ->constrained('bai_tap')
                ->cascadeOnDelete();

            $table->text('noi_dung');

            $table->enum('loai_cau_hoi', [
                'trac_nghiem',
                'tu_luan'
            ])->default('trac_nghiem');

            $table->decimal('diem', 5, 2)
                ->default(1);

            $table->integer('thu_tu')
                ->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cau_hoi');
    }
};