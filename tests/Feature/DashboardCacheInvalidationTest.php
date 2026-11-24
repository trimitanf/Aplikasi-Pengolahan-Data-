<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\PemetaanNilai;
use App\Models\TargetIku;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\TargetIkuSeeder;
use Database\Seeders\UptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $evaluator;
    protected Dataset $dataset;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Cache::flush();

        $this->seed([
            UptSeeder::class,
            MasterKodeSeeder::class,
            TargetIkuSeeder::class,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_cache',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->evaluator = User::create([
            'name' => 'Evaluator Test',
            'username' => 'eval_cache',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->dataset = Dataset::create([
            'uploaded_by' => $this->evaluator->id,
            'nama_file_asli' => 'dataset_cache_test.xls',
            'path_file' => 'private/datasets/cache_test.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 1,
        ]);
    }

    public function test_bump_cache_version_increments_consistently(): void
    {
        $v1 = DashboardService::getCacheVersion();
        $v2 = DashboardService::bumpCacheVersion();
        $this->assertEquals($v1 + 1, $v2);
        $this->assertEquals($v2, DashboardService::getCacheVersion());
    }

    public function test_target_iku_mutations_bump_cache_version(): void
    {
        $vStart = DashboardService::getCacheVersion();

        // 1. Store
        $this->actingAs($this->admin)->post('/target-iku', [
            'tahun' => 2027,
            'kelompok' => 'OBA',
            'target' => 75.5,
        ]);
        $this->assertEquals($vStart + 1, DashboardService::getCacheVersion());

        $targetRecord = TargetIku::where('tahun', 2027)->where('kelompok', 'OBA')->firstOrFail();

        // 2. Update
        $this->actingAs($this->admin)->put("/target-iku/{$targetRecord->id}", [
            'target' => 80.0,
        ]);
        $this->assertEquals($vStart + 2, DashboardService::getCacheVersion());

        // 3. Destroy
        $this->actingAs($this->admin)->delete("/target-iku/{$targetRecord->id}");
        $this->assertEquals($vStart + 3, DashboardService::getCacheVersion());
    }

    public function test_aturan_pemetaan_mutations_bump_cache_version(): void
    {
        $vStart = DashboardService::getCacheVersion();

        // 1. Store
        $this->actingAs($this->admin)->post('/aturan-pemetaan', [
            'grup' => 'klaim',
            'kolom_sumber' => 'klasifikasi_klaim',
            'nilai_sipt' => 'KLAIM TEST CACHE',
            'kode' => 1,
            'is_active' => true,
        ]);
        $this->assertEquals($vStart + 1, DashboardService::getCacheVersion());

        $pemetaan = PemetaanNilai::where('nilai_sipt', 'KLAIM TEST CACHE')->firstOrFail();

        // 2. Update
        $this->actingAs($this->admin)->put("/aturan-pemetaan/{$pemetaan->id}", [
            'kode' => 2,
            'is_active' => true,
        ]);
        $this->assertEquals($vStart + 2, DashboardService::getCacheVersion());

        // 3. Destroy
        $this->actingAs($this->admin)->delete("/aturan-pemetaan/{$pemetaan->id}");
        $this->assertEquals($vStart + 3, DashboardService::getCacheVersion());
    }

    public function test_dataset_trash_and_restore_mutations_bump_cache_version(): void
    {
        $vStart = DashboardService::getCacheVersion();

        // 1. Delete (Soft delete to trash)
        $this->actingAs($this->evaluator)->delete("/dataset/{$this->dataset->id}");
        $this->assertEquals($vStart + 1, DashboardService::getCacheVersion());

        // 2. Restore
        $this->actingAs($this->evaluator)->post("/dataset/{$this->dataset->id}/restore");
        $this->assertEquals($vStart + 2, DashboardService::getCacheVersion());

        // 3. Force Delete
        $this->actingAs($this->evaluator)->delete("/dataset/{$this->dataset->id}");
        $vAfterTrash = DashboardService::getCacheVersion();
        $this->actingAs($this->evaluator)->delete("/dataset/{$this->dataset->id}/force");
        $this->assertEquals($vAfterTrash + 1, DashboardService::getCacheVersion());
    }

    public function test_standardisasi_mutations_bump_cache_version(): void
    {
        $ds = Dataset::create([
            'uploaded_by' => $this->evaluator->id,
            'nama_file_asli' => 'std_test.xls',
            'path_file' => 'private/datasets/std.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Terstandardisasi',
            'jumlah_baris' => 1,
        ]);

        $raw = DataMentah::create([
            'dataset_id' => $ds->id,
            'nomor_baris' => 1,
            'upt' => 'Balai Besar POM di Jakarta',
            'kualitas_laporan' => null,
            'ketepatan_waktu' => null,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $iklan = DataIklan::create([
            'dataset_id' => $ds->id,
            'data_mentah_id' => $raw->id,
            'no' => 1,
            'upt' => 1,
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 1,
            'hasil_evaluasi' => 1,
            'kualitas_laporan' => null,
            'ketepatan_waktu' => null,
            'evaluator_id' => $this->evaluator->id,
            'status_standardisasi' => 'terstandar',
            'is_final' => false,
        ]);

        $vStart = DashboardService::getCacheVersion();

        // 1. Run conversion
        $this->actingAs($this->evaluator)->post(route('dataset.konversi.ulang', $ds));
        $this->assertEquals($vStart + 1, DashboardService::getCacheVersion());

        // 2. Update manual cell kualitas_laporan
        $this->actingAs($this->evaluator)->post("/dataset/{$ds->id}/manual-cell/{$iklan->id}", [
            'kolom' => 'kualitas_laporan',
            'nilai' => 1,
        ]);
        $this->assertEquals($vStart + 2, DashboardService::getCacheVersion());

        // 3. Update manual cell ketepatan_waktu
        $this->actingAs($this->evaluator)->post("/dataset/{$ds->id}/manual-cell/{$iklan->id}", [
            'kolom' => 'ketepatan_waktu',
            'nilai' => 1,
        ]);
        $this->assertEquals($vStart + 3, DashboardService::getCacheVersion());

        // 4. Simpan final
        $this->actingAs($this->evaluator)->post("/dataset/{$ds->id}/simpan-final");
        $this->assertEquals($vStart + 4, DashboardService::getCacheVersion());
    }

    public function test_dashboard_metrics_cache_and_invalidation(): void
    {
        $service = app(DashboardService::class);

        // Compute metrics (caches under current version)
        $m1 = $service->getMetrics(2026, 'SEMUA');
        $this->assertIsArray($m1);

        $v1 = DashboardService::getCacheVersion();
        $cacheKey = "dashboard_metrics_v{$v1}_2026_SEMUA_all";
        $this->assertTrue(Cache::has($cacheKey));

        // Bump version
        DashboardService::bumpCacheVersion();
        $v2 = DashboardService::getCacheVersion();
        $this->assertEquals($v1 + 1, $v2);

        $newCacheKey = "dashboard_metrics_v{$v2}_2026_SEMUA_all";
        $this->assertFalse(Cache::has($newCacheKey));

        // Calling getMetrics again caches under the new version key
        $m2 = $service->getMetrics(2026, 'SEMUA');
        $this->assertIsArray($m2);
        $this->assertTrue(Cache::has($newCacheKey));
    }
}
