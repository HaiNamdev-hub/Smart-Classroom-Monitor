
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cau_hoi', 'thu_tu')) {
            Schema::table('cau_hoi', function (Blueprint $table) {
                $table->unsignedInteger('thu_tu')->default(0);
            });
        }

        if (!Schema::hasColumn('cau_hoi', 'dap_an_mau')) {
            Schema::table('cau_hoi', function (Blueprint $table) {
                $table->longText('dap_an_mau')->nullable();
            });
        }

        if (!Schema::hasColumn('dap_an', 'thu_tu')) {
            Schema::table('dap_an', function (Blueprint $table) {
                $table->unsignedInteger('thu_tu')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cau_hoi', 'dap_an_mau')) {
            Schema::table('cau_hoi', function (Blueprint $table) {
                $table->dropColumn('dap_an_mau');
            });
        }

        // Không xóa thu_tu tự động vì cột này
        // có thể đã tồn tại trước migration.
    }
};
