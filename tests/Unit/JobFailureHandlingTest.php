<?php

namespace Tests\Unit;

use App\Jobs\ProsesDatasetJob;
use App\Jobs\StandardizeDatasetJob;
use App\Models\Dataset;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class JobFailureHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_proses_dataset_job_failed_method_updates_status_to_gagal_and_logs_issue(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'sipt_test_failed.xls',
            'path_file' => 'private/datasets/failed.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diproses',
        ]);

        $vStart = DashboardService::getCacheVersion();

        $job = new ProsesDatasetJob($dataset);
        $exception = new RuntimeException('Koneksi database worker terputus saat membaca file SIPT.');
        $job->failed($exception);

        $dataset->refresh();

        $this->assertEquals('Gagal', $dataset->status);
        $this->assertStringContainsString('Koneksi database worker terputus', $dataset->catatan);

        $issue = $dataset->issues()->where('tingkat', 'error')->first();
        $this->assertNotNull($issue);
        $this->assertStringContainsString('Koneksi database worker terputus', $issue->pesan);

        $this->assertEquals($vStart + 1, DashboardService::getCacheVersion());
    }

    public function test_standardize_dataset_job_failed_method_updates_status_to_gagal_and_logs_issue(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'sipt_std_failed.xls',
            'path_file' => 'private/datasets/std_failed.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diproses',
        ]);

        $vStart = DashboardService::getCacheVersion();

        $job = new StandardizeDatasetJob($dataset);
        $exception = new RuntimeException('Alokasi memori habis saat standardisasi tabel besar.');
        $job->failed($exception);

        $dataset->refresh();

        $this->assertEquals('Gagal', $dataset->status);
        $this->assertStringContainsString('Alokasi memori habis', $dataset->catatan);

        $issue = $dataset->issues()->where('tingkat', 'error')->first();
        $this->assertNotNull($issue);
        $this->assertStringContainsString('Alokasi memori habis', $issue->pesan);

        $this->assertEquals($vStart + 1, DashboardService::getCacheVersion());
    }
}
