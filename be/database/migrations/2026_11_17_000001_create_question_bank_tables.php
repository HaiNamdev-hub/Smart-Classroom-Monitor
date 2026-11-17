<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ngan_hang_cau_hoi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_tao_id')->constrained('nguoi_dung')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('noi_dung');
            $table->enum('loai_cau_hoi', ['trac_nghiem', 'tu_luan']);
            $table->string('chu_de', 120)->nullable();
            $table->enum('do_kho', ['de', 'trung_binh', 'kho'])->default('trung_binh');
            $table->decimal('diem_tu_luan', 5, 2)->nullable();
            $table->longText('dap_an_mau')->nullable();
            $table->enum('trang_thai', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['nguoi_tao_id', 'trang_thai', 'loai_cau_hoi'], 'question_bank_owner_status_type_idx');
            $table->index(['nguoi_tao_id', 'chu_de', 'do_kho'], 'question_bank_owner_topic_difficulty_idx');
        });

        Schema::create('ngan_hang_dap_an', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ngan_hang_cau_hoi_id')->constrained('ngan_hang_cau_hoi')->cascadeOnUpdate()->cascadeOnDelete();
            $table->text('noi_dung');
            $table->boolean('la_dap_an_dung')->default(false);
            $table->unsignedSmallInteger('thu_tu')->default(1);
            $table->timestamps();

            $table->index(['ngan_hang_cau_hoi_id', 'thu_tu'], 'question_bank_answers_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ngan_hang_dap_an');
        Schema::dropIfExists('ngan_hang_cau_hoi');
    }
};
