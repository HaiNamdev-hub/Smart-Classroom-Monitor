<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bai_tap_cau_hinh_tron', function (Blueprint $table) {
            $table->foreignId('bai_tap_id')->primary()->constrained('bai_tap')->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('tron_cau_hoi')->default(false);
            $table->boolean('tron_dap_an')->default(false);
            $table->timestamps();
        });

        // Promote the Phase 3 bank-exam flags to the shared canonical table.
        // Existing generated exams retain their configured shuffle behavior.
        foreach (DB::table('ngan_hang_de_tao')->get(['bai_tap_id', 'tron_cau_hoi', 'tron_dap_an']) as $legacy) {
            DB::table('bai_tap_cau_hinh_tron')->insert([
                'bai_tap_id' => $legacy->bai_tap_id,
                'tron_cau_hoi' => (bool) $legacy->tron_cau_hoi,
                'tron_dap_an' => (bool) $legacy->tron_dap_an,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bai_tap_cau_hinh_tron');
    }
};
