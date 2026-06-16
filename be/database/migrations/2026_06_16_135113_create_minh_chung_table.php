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
        Schema::create('minh_chung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chi_tiet_id')
                ->constrained('chi_tiet_danh_gia')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('ten_file');
            $table->string('duong_dan');
            $table->enum('trang_thai', ['cho_duyet', 'hop_le', 'khong_hop_le'])->default('cho_duyet');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minh_chung');
    }
};
