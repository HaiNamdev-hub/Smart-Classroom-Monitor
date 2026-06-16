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
        Schema::create('lich_su_duyet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_id')
                ->constrained('phieu_ren_luyen')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('nguoi_duyet_id')
                ->constrained('nguoi_dung')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('vai_tro_duyet', [
                'lop_truong',
                'bi_thu',
                'co_van',
                'phong_qldt',
                'admin',
            ]);
            $table->text('ghi_chu')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_duyet');
    }
};
