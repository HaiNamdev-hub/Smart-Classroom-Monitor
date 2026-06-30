<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tieu_chi', function (Blueprint $table) {

            $table->id();

            $table->foreignId('nhom_id')
                ->constrained('nhom_tieu_chi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('tieu_chi')
                ->nullOnDelete();

            $table->string('ma_tieu_chi');

            $table->text('ten_tieu_chi');

            $table->integer('diem_toi_da');

            $table->enum('loai_tieu_chi', [
                'cong',
                'tru'
            ]);

            $table->enum('loai_nhap', [
                'none',
                'textbox',
                'checkbox',
                'combobox'
            ]);

            $table->boolean('bat_buoc_minh_chung')
                ->default(false);

            $table->integer('thu_tu')->default(1);

            $table->unique([
                'nhom_id',
                'ma_tieu_chi'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tieu_chi');
    }
};