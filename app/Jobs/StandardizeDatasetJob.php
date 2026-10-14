<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Services\StandardizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StandardizeDatasetJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Dataset $dataset
    ) {}

    public function handle(StandardizationService $standardizationService): void
    {
        $standardizationService->standardizeDataset($this->dataset);
    }
}
