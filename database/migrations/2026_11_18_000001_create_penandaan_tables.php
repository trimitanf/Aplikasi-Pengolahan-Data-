<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Batch Dataset Penandaan
        Schema::create('datasets_penandaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file_asli');
            $table->string('path_file');
            $table->string('komoditi', 10)->default('SEMUA'); // OT, OK, SK, OBA
            $table->unsignedSmallInteger('periode_tahun');
            $table->unsignedTinyInteger('periode_bulan'); // 1 - 12
            $table->date('periode_mulai')->nullable();
            $table->date('periode_akhir')->nullable();
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->unsignedInteger('jumlah_menunggu_evaluasi')->default(0);
            $table->string('status', 30)->default('Diunggah'); // Diunggah, Diproses, Terstandarisasi, Final, Gagal
            $table->text('catatan')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['komoditi', 'periode_tahun', 'periode_bulan']);
            $table->index('status');
        });

        // 2. Data Mentah SIPT Penandaan
        Schema::create('data_mentah_penandaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_penandaan_id')->constrained('datasets_penandaan')->cascadeOnDelete();
            $table->unsignedInteger('baris_ke')->nullable();
            $table->string('upt')->nullable();
            $table->string('nama_produk')->nullable();
            $table->string('bentuk_sediaan')->nullable();
            $table->string('nomor_izin_edar')->nullable();
            $table->string('produsen')->nullable();
            $table->string('pendaftar')->nullable();
            $table->string('hasil_evaluasi_sipt')->nullable(); // MK / TMK
            $table->string('kesimpulan_pusat')->nullable();
            $table->json('payload')->nullable(); // Menampung struktur kolom mentah SIPT Penandaan fleksibel

            // Kolom Evaluator
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('kualitas_laporan')->nullable();
            $table->unsignedSmallInteger('ketepatan_waktu')->nullable();
            $table->unsignedSmallInteger('kesesuaian_evaluasi')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('dataset_penandaan_id');
            $table->index('evaluator_id');
        });

        // 3. Data Hasil Standarisasi Penandaan
        Schema::create('data_penandaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_penandaan_id')->constrained('datasets_penandaan')->cascadeOnDelete();
            $table->foreignId('data_mentah_penandaan_id')->unique()->constrained('data_mentah_penandaan')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('no')->nullable();
            $table->unsignedSmallInteger('upt')->nullable(); // Kode UPT
            $table->unsignedSmallInteger('periode_bulan')->nullable();
            $table->unsignedSmallInteger('periode_tahun')->nullable();
            $table->unsignedSmallInteger('jenis_produk')->nullable();
            $table->unsignedSmallInteger('asal_produk')->nullable();
            $table->unsignedSmallInteger('hasil_evaluasi')->nullable(); // 1 MK, 2 TMK
            $table->unsignedSmallInteger('rincian_tmk')->nullable();
            $table->unsignedSmallInteger('jenis_tmk')->nullable();

            $table->unsignedSmallInteger('kesesuaian_evaluasi')->nullable();
            $table->unsignedSmallInteger('kualitas_laporan')->nullable();
            $table->unsignedSmallInteger('ketepatan_waktu')->nullable();

            $table->string('status_standardisasi', 30)->default('terstandar');
            $table->json('catatan_tidak_terpetakan')->nullable();
            $table->boolean('is_final')->default(false);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['dataset_penandaan_id', 'is_final']);
            $table->index(['periode_tahun', 'periode_bulan', 'is_final']);
            $table->index(['jenis_produk', 'hasil_evaluasi', 'is_final']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_penandaan');
        Schema::dropIfExists('data_mentah_penandaan');
        Schema::dropIfExists('datasets_penandaan');
    }
};
