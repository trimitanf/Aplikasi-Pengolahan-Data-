<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemetaan_nilai', function (Blueprint $table) {
            $table->id();
            $table->string('grup', 50);
            $table->string('kolom_sumber', 50);
            $table->string('nilai_sipt', 190);
            $table->unsignedSmallInteger('kode');
            $table->boolean('is_active')->default(true);
            $table->unique(['kolom_sumber', 'nilai_sipt']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemetaan_nilai');
    }
};
