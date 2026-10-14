<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_iklan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->foreignId('data_mentah_id')->unique()->constrained('data_mentah')->cascadeOnDelete();

            // 18 Kolom Kode Angka (SmallInt) persis seperti sheet Data All
            $table->unsignedInteger('no')->nullable(); // Nomor urut resmi tahunan saat Final
            $table->unsignedSmallInteger('upt')->nullable(); // Kode UPT 1-83
            $table->unsignedSmallInteger('periode_bulan')->nullable(); // 1-12
            $table->unsignedSmallInteger('periode_tahun')->nullable(); // 2020-2100
            $table->unsignedSmallInteger('jenis_produk')->nullable(); // 1 OT, 2 SK, 3 OK
            $table->unsignedSmallInteger('asal_produk')->nullable(); // 1 Lokal, 2 Impor, 3 Lisensi
            $table->unsignedSmallInteger('klaim')->nullable(); // 1-25
            $table->unsignedSmallInteger('kategori_media')->nullable(); // 1 Audio, 2 Visual, 3 Audio Visual
            $table->unsignedSmallInteger('kategori_iklan')->nullable(); // 1-8
            $table->unsignedSmallInteger('nama_media')->nullable(); // 1-19, null jika tidak berlaku
            $table->unsignedSmallInteger('pencantuman_nie')->nullable(); // 1 Ada, 2 Tidak Ada
            $table->unsignedSmallInteger('kesesuaian_nie')->nullable(); // 1 Sesuai, 2 Tidak Sesuai
            $table->unsignedSmallInteger('hasil_evaluasi')->nullable(); // 1 MK, 2 TMK, null jika menunggu evaluasi
            $table->unsignedSmallInteger('rincian_tmk')->nullable(); // 1-8
            $table->unsignedSmallInteger('jenis_tmk')->nullable(); // 1 Kritis, 2 Mayor, 3 Minor
            $table->unsignedSmallInteger('pembuat_iklan')->nullable(); // 1 Pelaku Usaha, 2 Perseorangan

            // 3 Kolom Manual (Evaluator)
            $table->unsignedSmallInteger('kesesuaian_evaluasi')->nullable(); // 1 Sesuai, 2 Tidak Sesuai
            $table->unsignedSmallInteger('kualitas_laporan')->nullable(); // 1 Lengkap & Benar, 2 Tidak Lengkap
            $table->unsignedSmallInteger('ketepatan_waktu')->nullable(); // 1 Tepat Waktu, 2 Tidak Tepat Waktu

            // Flag status & catatan audit
            $table->string('status_standardisasi', 30)->default('terstandar'); // terstandar, perlu_koreksi, menunggu_evaluasi
            $table->json('catatan_tidak_terpetakan')->nullable();
            $table->boolean('is_final')->default(false);

            $table->timestamps();

            $table->index(['dataset_id', 'is_final']);
            $table->index(['periode_tahun', 'periode_bulan', 'is_final']);
            $table->index(['jenis_produk', 'hasil_evaluasi', 'is_final']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_iklan');
    }
};
