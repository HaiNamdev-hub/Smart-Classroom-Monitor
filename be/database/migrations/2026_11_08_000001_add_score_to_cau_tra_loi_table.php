<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('cau_tra_loi', 'diem')) {
            Schema::table('cau_tra_loi', function (Blueprint $table) {
                $table->decimal('diem', 4, 2)->nullable()->after('noi_dung_tra_loi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cau_tra_loi', 'diem')) {
            Schema::table('cau_tra_loi', function (Blueprint $table) {
                $table->dropColumn('diem');
            });
        }
    }
};
