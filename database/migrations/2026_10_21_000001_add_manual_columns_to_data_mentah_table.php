<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_mentah', function (Blueprint $table) {
            $table->unsignedSmallInteger('kesesuaian_evaluasi')->nullable()->after('tindak_lanjut');
            $table->unsignedSmallInteger('kualitas_laporan')->nullable()->after('kesesuaian_evaluasi');
            $table->unsignedSmallInteger('ketepatan_waktu')->nullable()->after('kualitas_laporan');
        });

        // Copy existing manual values from data_iklan if any exists
        if (Schema::hasTable('data_iklan')) {
            $items = DB::table('data_iklan')
                ->whereNotNull('kesesuaian_evaluasi')
                ->orWhereNotNull('kualitas_laporan')
                ->orWhereNotNull('ketepatan_waktu')
                ->get();

            foreach ($items as $item) {
                if ($item->data_mentah_id) {
                    DB::table('data_mentah')
                        ->where('id', $item->data_mentah_id)
                        ->update([
                            'kesesuaian_evaluasi' => $item->kesesuaian_evaluasi,
                            'kualitas_laporan' => $item->kualitas_laporan,
                            'ketepatan_waktu' => $item->ketepatan_waktu,
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('data_mentah', function (Blueprint $table) {
            $table->dropColumn(['kesesuaian_evaluasi', 'kualitas_laporan', 'ketepatan_waktu']);
        });
    }
};
