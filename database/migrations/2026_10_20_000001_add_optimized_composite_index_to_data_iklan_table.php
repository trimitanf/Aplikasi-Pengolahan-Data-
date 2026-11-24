<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_iklan', function (Blueprint $table) {
            $table->index(['is_final', 'periode_tahun', 'periode_bulan', 'no'], 'idx_data_iklan_final_periode_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_iklan', function (Blueprint $table) {
            $table->dropIndex('idx_data_iklan_final_periode_no');
        });
    }
};
