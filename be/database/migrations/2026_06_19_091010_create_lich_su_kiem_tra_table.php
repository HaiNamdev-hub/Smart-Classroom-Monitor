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
        Schema::create('lich_su_kiem_tra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_id')
                ->constrained('phieu_ren_luyen')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('nguoi_kiem_tra_id')
                ->constrained('nguoi_dung')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('vai_tro_kiem_tra', ['lop_truong', 'bi_thu', 'co_van']);
            $table->enum('hanh_dong', ['danh_gia', 'kiem_tra', 'tra_lai', 'xac_nhan']);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_kiem_tra');
    }
};
