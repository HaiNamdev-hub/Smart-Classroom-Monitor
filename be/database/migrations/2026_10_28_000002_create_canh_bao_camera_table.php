
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canh_bao_camera', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bai_tap_id');
            $table->unsignedBigInteger('sinh_vien_id');

            // Mỗi lần cảnh báo có mã riêng, tránh ghi trùng khi gửi lại
            $table->uuid('event_id')->unique();

            $table->string('loai_canh_bao', 50);

            $table->timestamps();

            $table->index([
                'bai_tap_id',
                'sinh_vien_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canh_bao_camera');
    }
};
