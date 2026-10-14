<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('datasets', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file_asli');
            $table->string('path_file');
            $table->string('komoditi', 10); // OT, OK, SK
            $table->unsignedSmallInteger('periode_tahun');
            $table->unsignedTinyInteger('periode_bulan'); // 1 - 12
            $table->date('periode_mulai')->nullable();
            $table->date('periode_akhir')->nullable();
            $table->unsignedInteger('jumlah_baris')->default(0);
            $table->unsignedInteger('jumlah_menunggu_evaluasi')->default(0);
            $table->string('status', 30)->default('Diunggah'); // Diunggah, Diproses, Valid, Ditolak, Gagal, Terstandardisasi, Final
            $table->text('catatan')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['komoditi', 'periode_tahun', 'periode_bulan']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datasets');
    }
};
