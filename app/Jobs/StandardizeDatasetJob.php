<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Services\Iklan\DashboardService;
use App\Services\Iklan\StandardizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class StandardizeDatasetJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    public function __construct(
        public Dataset $dataset
    ) {}

    public function handle(StandardizationService $standardizationService): void
    {
        $standardizationService->standardizeDataset($this->dataset);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        try {
            $pesan = $exception
                ? 'Gagal menjalankan standardisasi dataset pada antrean: ' . $exception->getMessage()
                : 'Proses konversi standardisasi pada antrean sistem gagal.';

            $pesanClean = Str::limit($pesan, 5000);

            $this->dataset->issues()->create([
                'tingkat' => 'error',
                'pesan' => $pesanClean,
            ]);

            $this->dataset->update([
                'status' => 'Gagal',
                'catatan' => $pesanClean,
            ]);

            DashboardService::bumpCacheVersion();
        } catch (Throwable $e) {
            Log::error('Gagal memperbarui status dataset pada failed StandardizeDatasetJob: ' . $e->getMessage());
        }
    }
}
