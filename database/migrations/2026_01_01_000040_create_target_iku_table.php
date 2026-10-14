<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_iku', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->string('kelompok', 10); // OBA or SK
            $table->decimal('target', 5, 2); // e.g. 67.00
            $table->unique(['tahun', 'kelompok']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_iku');
    }
};
