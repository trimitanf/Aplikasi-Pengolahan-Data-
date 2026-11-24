<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Services\Iklan\DashboardService;
use App\Services\Iklan\DatasetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProsesDatasetJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Dataset $dataset
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DatasetService $datasetService): void
    {
        $datasetService->process($this->dataset);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        try {
            $pesan = $exception
                ? 'Gagal memproses file pada antrean sistem: ' . $exception->getMessage()
                : 'Pemrosesan berkas dataset pada antrean sistem gagal.';

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
            Log::error('Gagal memperbarui status dataset pada failed ProsesDatasetJob: ' . $e->getMessage());
        }
    }
}
