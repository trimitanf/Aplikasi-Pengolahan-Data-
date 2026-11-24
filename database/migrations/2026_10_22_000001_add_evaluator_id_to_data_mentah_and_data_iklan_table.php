<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_mentah', function (Blueprint $table) {
            $table->foreignId('evaluator_id')
                ->nullable()
                ->after('dataset_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['dataset_id', 'evaluator_id']);
        });

        Schema::table('data_iklan', function (Blueprint $table) {
            $table->foreignId('evaluator_id')
                ->nullable()
                ->after('data_mentah_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['dataset_id', 'evaluator_id']);
        });

        // Distribusikan baris yang sudah ada secara round-robin ke evaluator aktif jika ada
        $evaluators = DB::table('users')
            ->where('role', 'evaluator')
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();

        $evaluatorCount = count($evaluators);

        if ($evaluatorCount > 0) {
            $datasetIds = DB::table('data_mentah')->distinct()->pluck('dataset_id');
            foreach ($datasetIds as $datasetId) {
                $rows = DB::table('data_mentah')
                    ->where('dataset_id', $datasetId)
                    ->orderBy('nomor_baris', 'asc')
                    ->select('id')
                    ->get();

                foreach ($rows as $index => $row) {
                    $assignedEvalId = $evaluators[$index % $evaluatorCount];
                    DB::table('data_mentah')
                        ->where('id', $row->id)
                        ->update(['evaluator_id' => $assignedEvalId]);

                    DB::table('data_iklan')
                        ->where('data_mentah_id', $row->id)
                        ->update(['evaluator_id' => $assignedEvalId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('data_iklan', function (Blueprint $table) {
            $table->dropForeign(['evaluator_id']);
            $table->dropIndex(['dataset_id', 'evaluator_id']);
            $table->dropColumn('evaluator_id');
        });

        Schema::table('data_mentah', function (Blueprint $table) {
            $table->dropForeign(['evaluator_id']);
            $table->dropIndex(['dataset_id', 'evaluator_id']);
            $table->dropColumn('evaluator_id');
        });
    }
};
