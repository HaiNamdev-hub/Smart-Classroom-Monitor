
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vi_pham_kiem_tra', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bai_tap_id')
                ->constrained('bai_tap')
                ->cascadeOnDelete();

            $table->foreignId('sinh_vien_id')
                ->constrained('sinh_vien')
                ->cascadeOnDelete();

            // Mỗi lần chuyển tab có mã riêng,
            // tránh đếm trùng khi request được gửi lại.
            $table->uuid('event_id')->unique();

            $table->unsignedTinyInteger('lan_thu');

            $table->string('loai_vi_pham', 50)
                ->default('roi_tab');

            $table->timestamps();

            $table->index([
                'bai_tap_id',
                'sinh_vien_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vi_pham_kiem_tra');
    }
};
