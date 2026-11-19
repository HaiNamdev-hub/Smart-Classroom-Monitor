<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột cloudinary_public_id vào bảng anh_man_hinh và bai_nop
 * để có thể xóa ảnh trên Cloudinary khi cần.
 *
 * Cột duong_dan sẽ chứa URL đầy đủ từ Cloudinary (https://res.cloudinary.com/...)
 * thay vì đường dẫn local như trước.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('anh_man_hinh', function (Blueprint $table) {
            // Mở rộng độ dài cột duong_dan để chứa URL Cloudinary dài
            $table->string('duong_dan', 1024)->change();
            // Public ID để xóa ảnh trên Cloudinary khi cần
            $table->string('cloudinary_public_id')->nullable()->after('duong_dan');
        });

        Schema::table('bai_nop', function (Blueprint $table) {
            // duong_dan_file là cột lưu URL file (Cloudinary URL)
            if (Schema::hasColumn('bai_nop', 'duong_dan_file')) {
                $table->string('duong_dan_file', 1024)->change();
                $table->string('cloudinary_public_id')->nullable()->after('duong_dan_file');
            } else {
                $table->string('duong_dan', 1024)->change();
                $table->string('cloudinary_public_id')->nullable()->after('duong_dan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('anh_man_hinh', function (Blueprint $table) {
            $table->string('duong_dan', 255)->change();
            $table->dropColumn('cloudinary_public_id');
        });

        Schema::table('bai_nop', function (Blueprint $table) {
            $table->dropColumnIfExists('cloudinary_public_id');
        });
    }
};
