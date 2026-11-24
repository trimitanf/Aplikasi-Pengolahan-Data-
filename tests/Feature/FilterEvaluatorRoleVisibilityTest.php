<?php

namespace Tests\Feature;

use App\Models\Dataset;
use App\Models\User;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\TargetIkuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterEvaluatorRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $evaluator;
    protected Dataset $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterKodeSeeder::class);
        $this->seed(TargetIkuSeeder::class);

        $this->admin = User::create([
            'name' => 'Administrator BPOM',
            'username' => 'admin_vis_test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->evaluator = User::create([
            'name' => 'Evaluator BPOM 1',
            'username' => 'evaluator_vis_test',
            'password' => bcrypt('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_VIS_TEST.xlsx',
            'path_file' => 'private/datasets/test.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Terstandardisasi',
            'jumlah_baris' => 1,
            'uploaded_by' => $this->evaluator->id,
            'uploaded_at' => now(),
        ]);
    }

    public function test_filter_semua_evaluator_tidak_tampil_di_laman_evaluator(): void
    {
        // 1. Laman Detail Dataset (dataset.show)
        $resShow = $this->actingAs($this->evaluator)->get(route('dataset.show', $this->dataset));
        $resShow->assertOk();
        $resShow->assertDontSee('Semua Evaluator');

        // 2. Laman Standarisasi (dataset.standardisasi)
        $resStd = $this->actingAs($this->evaluator)->get(route('dataset.standardisasi', $this->dataset));
        $resStd->assertOk();
        $resStd->assertDontSee('Semua Evaluator');

        // 3. Laman Data Tersimpan (data-tersimpan.index)
        $resSaved = $this->actingAs($this->evaluator)->get(route('data-tersimpan.index'));
        $resSaved->assertOk();
        $resSaved->assertDontSee('Semua Evaluator');

        // 4. Laman Report Evaluator tidak dapat diakses evaluator (403)
        $resReport = $this->actingAs($this->evaluator)->get(route('report-evaluator.index'));
        $resReport->assertForbidden();
    }

    public function test_filter_semua_evaluator_tetap_tampil_di_laman_admin(): void
    {
        // 1. Laman Detail Dataset (dataset.show)
        $resShow = $this->actingAs($this->admin)->get(route('dataset.show', $this->dataset));
        $resShow->assertOk();
        $resShow->assertSee('Semua Evaluator');

        // 2. Laman Standarisasi (dataset.standardisasi)
        $resStd = $this->actingAs($this->admin)->get(route('dataset.standardisasi', $this->dataset));
        $resStd->assertOk();
        $resStd->assertSee('Semua Evaluator');

        // 3. Laman Data Tersimpan (data-tersimpan.index)
        $resSaved = $this->actingAs($this->admin)->get(route('data-tersimpan.index'));
        $resSaved->assertOk();
        $resSaved->assertSee('Semua Evaluator');

        // 4. Laman Report Evaluator (report-evaluator.index)
        $resReport = $this->actingAs($this->admin)->get(route('report-evaluator.index'));
        $resReport->assertOk();
        $resReport->assertSee('Semua Evaluator');
    }
}
