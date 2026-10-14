<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_mentah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->unsignedInteger('nomor_baris');

            // 26 Kolom SIPT apa adanya
            $table->string('no', 50)->nullable();
            $table->string('upt', 255)->nullable();
            $table->string('tanggal_pengambilan_iklan', 100)->nullable();
            $table->string('tgl_kirim_ka_upt_ke_pusat', 100)->nullable();
            $table->string('tanggal_evaluasi_pusat', 100)->nullable();
            $table->string('jenis_produk', 255)->nullable();
            $table->text('nama_produk')->nullable();
            $table->string('nomor_izin_edar', 255)->nullable();
            $table->text('khasiat_indikasi')->nullable();
            $table->text('pendaftar')->nullable();
            $table->string('asal_produk', 100)->nullable();
            $table->string('kategori_media', 100)->nullable();
            $table->string('media_iklan', 100)->nullable();
            $table->text('tempat_pengambilan_iklan')->nullable();
            $table->text('pencantuman_nomor_izin_edar')->nullable();
            $table->text('link_url_iklan')->nullable();
            $table->text('klaim_dalam_promosi_iklan')->nullable();
            $table->text('klasifikasi_klaim')->nullable();
            $table->string('pembuat_iklan', 100)->nullable();
            $table->string('hasil_penilaian_iklan_upt', 100)->nullable();
            $table->text('kesimpulan_upt')->nullable();
            $table->text('kesimpulan_pusat')->nullable();
            $table->string('kesesuaian_hasil_evaluasi', 100)->nullable();
            $table->text('kategori_tmk')->nullable();
            $table->text('rincian_tmk')->nullable();
            $table->text('tindak_lanjut')->nullable();

            $table->timestamps();

            $table->index(['dataset_id', 'nomor_baris']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_mentah');
    }
};
