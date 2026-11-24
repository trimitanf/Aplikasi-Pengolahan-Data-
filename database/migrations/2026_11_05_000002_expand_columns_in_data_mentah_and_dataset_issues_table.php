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
        Schema::table('data_mentah', function (Blueprint $table) {
            $table->text('hasil_penilaian_iklan_upt')->nullable()->change();
            $table->text('kesesuaian_hasil_evaluasi')->nullable()->change();
            $table->text('pembuat_iklan')->nullable()->change();
            $table->string('asal_produk', 255)->nullable()->change();
            $table->string('kategori_media', 255)->nullable()->change();
            $table->string('media_iklan', 255)->nullable()->change();
            $table->string('tanggal_pengambilan_iklan', 255)->nullable()->change();
            $table->string('tgl_kirim_ka_upt_ke_pusat', 255)->nullable()->change();
            $table->string('tanggal_evaluasi_pusat', 255)->nullable()->change();
        });

        Schema::table('dataset_issues', function (Blueprint $table) {
            $table->longText('pesan')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_mentah', function (Blueprint $table) {
            $table->string('hasil_penilaian_iklan_upt', 100)->nullable()->change();
            $table->string('kesesuaian_hasil_evaluasi', 100)->nullable()->change();
            $table->string('pembuat_iklan', 100)->nullable()->change();
            $table->string('asal_produk', 100)->nullable()->change();
            $table->string('kategori_media', 100)->nullable()->change();
            $table->string('media_iklan', 100)->nullable()->change();
            $table->string('tanggal_pengambilan_iklan', 100)->nullable()->change();
            $table->string('tgl_kirim_ka_upt_ke_pusat', 100)->nullable()->change();
            $table->string('tanggal_evaluasi_pusat', 100)->nullable()->change();
        });

        Schema::table('dataset_issues', function (Blueprint $table) {
            $table->text('pesan')->change();
        });
    }
};
