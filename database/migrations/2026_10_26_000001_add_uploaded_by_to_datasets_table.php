<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skema Kepemilikan: setiap dataset dimiliki oleh evaluator yang mengunggahnya.
     * Seluruh baris pada dataset tersebut otomatis menjadi tanggung jawab pengunggah.
     */
    public function up(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->foreignId('uploaded_by')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['uploaded_by', 'periode_tahun', 'periode_bulan']);
        });
    }

    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropIndex(['uploaded_by', 'periode_tahun', 'periode_bulan']);
            $table->dropColumn('uploaded_by');
        });
    }
};
