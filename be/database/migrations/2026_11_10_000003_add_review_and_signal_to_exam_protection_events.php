<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('su_kien_bao_ve_bai_kiem_tra', function (Blueprint $table) {
            $table->json('thong_tin_tin_hieu')->nullable();
            $table->boolean('da_xem')->default(false);
            $table->foreignId('xem_boi_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->dateTime('xem_luc')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('su_kien_bao_ve_bai_kiem_tra', function (Blueprint $table) {
            $table->dropForeign(['xem_boi_id']);
            $table->dropColumn(['thong_tin_tin_hieu', 'da_xem', 'xem_boi_id', 'xem_luc']);
        });
    }
};
