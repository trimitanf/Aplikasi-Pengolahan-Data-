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
        Schema::table('datasets', function (Blueprint $table) {
            $table->softDeletes()->after('finalized_at');
        });

        Schema::table('data_mentah', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('data_iklan', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('dataset_issues', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dataset_issues', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('data_iklan', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('data_mentah', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('datasets', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
