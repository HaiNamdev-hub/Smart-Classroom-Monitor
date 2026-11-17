<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ngan_hang_de_tao', function (Blueprint $table) {
            $table->boolean('tron_cau_hoi')->default(false);
            $table->boolean('tron_dap_an')->default(false);
        });

        Schema::create('bai_kiem_tra_luot_lam_cau_hoi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('luot_lam_id')->constrained('bai_kiem_tra_luot_lam')->cascadeOnDelete();
            $table->foreignId('cau_hoi_id')->constrained('cau_hoi')->cascadeOnDelete();
            $table->unsignedInteger('thu_tu_hien_thi');
            $table->timestamps();
            $table->unique(['luot_lam_id', 'cau_hoi_id'], 'attempt_question_once');
            $table->unique(['luot_lam_id', 'thu_tu_hien_thi'], 'attempt_question_position_once');
        });

        Schema::create('bai_kiem_tra_luot_lam_dap_an', function (Blueprint $table) {
            $table->id();
            $table->foreignId('luot_lam_id')->constrained('bai_kiem_tra_luot_lam')->cascadeOnDelete();
            $table->foreignId('cau_hoi_id')->constrained('cau_hoi')->cascadeOnDelete();
            $table->foreignId('dap_an_id')->constrained('dap_an')->cascadeOnDelete();
            $table->unsignedInteger('thu_tu_hien_thi');
            $table->timestamps();
            $table->unique(['luot_lam_id', 'dap_an_id'], 'attempt_answer_once');
            $table->unique(['luot_lam_id', 'cau_hoi_id', 'thu_tu_hien_thi'], 'attempt_answer_position_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bai_kiem_tra_luot_lam_dap_an');
        Schema::dropIfExists('bai_kiem_tra_luot_lam_cau_hoi');
        Schema::table('ngan_hang_de_tao', function (Blueprint $table) {
            $table->dropColumn(['tron_cau_hoi', 'tron_dap_an']);
        });
    }
};
