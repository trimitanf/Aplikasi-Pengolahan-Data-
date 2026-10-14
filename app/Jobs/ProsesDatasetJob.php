<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Services\DatasetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProsesDatasetJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

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
}
