<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bai_kiem_tra_luot_lam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bai_tap_id')->constrained('bai_tap')->cascadeOnDelete();
            $table->foreignId('sinh_vien_id')->constrained('sinh_vien')->cascadeOnDelete();
            $table->dateTime('bat_dau_luc');
            $table->timestamps();

            $table->unique(['bai_tap_id', 'sinh_vien_id'], 'exam_attempt_assignment_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bai_kiem_tra_luot_lam');
    }
};
