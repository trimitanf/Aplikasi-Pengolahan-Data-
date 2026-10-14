<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_kodes', function (Blueprint $table) {
            $table->id();
            $table->string('grup', 50);
            $table->unsignedSmallInteger('kode');
            $table->text('nama');
            $table->string('kelompok_rekap', 50)->nullable();
            $table->unique(['grup', 'kode']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_kodes');
    }
};
