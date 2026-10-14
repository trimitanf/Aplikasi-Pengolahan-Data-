<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_id')->constrained('datasets')->cascadeOnDelete();
            $table->unsignedInteger('nomor_baris')->nullable();
            $table->string('kolom', 100)->nullable();
            $table->string('tingkat', 20); // error, warning
            $table->text('pesan');
            $table->timestamps();

            $table->index(['dataset_id', 'tingkat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_issues');
    }
};
