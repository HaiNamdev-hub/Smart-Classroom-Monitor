<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bai_tap_hinh_thuc_kiem_tra', function (Blueprint $table) {
            $table->foreignId('bai_tap_id')->primary()->constrained('bai_tap')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('hinh_thuc', 40)->default('ONLINE_CAMERA_REQUIRED');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bai_tap_hinh_thuc_kiem_tra');
    }
};
