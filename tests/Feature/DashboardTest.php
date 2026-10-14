<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\TargetIku;
use App\Models\Upt;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $evaluator;
    protected Dataset $dataset;
    protected DataMentah $dataMentah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\MasterKodeSeeder::class);
        $this->seed(\Database\Seeders\TargetIkuSeeder::class);

        $this->evaluator = User::create([
            'name' => 'Evaluator Dashboard',
            'username' => 'evaluator_dash',
            'password' => bcrypt('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_DASH_2026.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 7,
            'status' => 'Final',
            'path_file' => 'datasets/dash_test.xlsx',
            'jumlah_baris' => 10,
            'uploaded_at' => now(),
            'finalized_at' => now(),
        ]);

        $this->dataMentah = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 1,
            'no' => '1',
            'upt' => 'UPT 1',
            'jenis_produk' => '1',
        ]);
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_evaluator_can_view_dashboard_with_all_components(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Pengawasan Iklan OTSK');
        $response->assertSee('Total Pengawasan');
        $response->assertSee('Memenuhi (MK)');
        $response->assertSee('Tidak Memenuhi (TMK)');
        $response->assertSee('Menunggu Evaluasi');
        $response->assertSee('Data Final Terkunci');
        $response->assertSee('Obat Bahan Alam (OBA)');
        $response->assertSee('Suplemen Kesehatan (SK)');
        $response->assertSee('monthlyTrendChart');
        $response->assertSee('topTmkChart');
        $response->assertSee('dimensionStackedChart');
        $response->assertSee('Ringkasan Pencantuman', false);
        $response->assertSee('Panduan Alur Sistem Pengolahan Data Iklan');
    }

    public function test_evaluator_can_apply_filters_on_dashboard(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('dashboard', [
            'tahun' => 2026,
            'komoditi' => 'OBA',
            'upt' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Dashboard Pengawasan Iklan OTSK');
    }

    public function test_dashboard_service_computes_accurate_metrics(): void
    {
        // Insert sample finalized records:
        // 1. OBA MK (jenis_produk = 1, hasil_evaluasi = 1, asal_produk = 1, media = 2, iklan = 1 [ecom], nie = 1, kesesuaian = 1)
        DataIklan::create([
            'dataset_id' => $this->dataset->id,
            'data_mentah_id' => $this->dataMentah->id,
            'no' => 1,
            'upt' => 1,
            'periode_bulan' => 7,
            'periode_tahun' => 2026,
            'jenis_produk' => 1,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 1,
            'pencantuman_nie' => 1,
            'kesesuaian_nie' => 1,
            'hasil_evaluasi' => 1,
            'pembuat_iklan' => 1,
            'status_standardisasi' => 'terstandar',
            'is_final' => true,
        ]);

        // 2. SK TMK (jenis_produk = 2, hasil_evaluasi = 2, rincian_tmk = 7 [TIE], iklan = 1 [ecom], nie = 2)
        $mentah2 = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 2,
            'no' => '2',
            'upt' => 'UPT 1',
            'jenis_produk' => '2',
        ]);

        DataIklan::create([
            'dataset_id' => $this->dataset->id,
            'data_mentah_id' => $mentah2->id,
            'no' => 2,
            'upt' => 1,
            'periode_bulan' => 7,
            'periode_tahun' => 2026,
            'jenis_produk' => 2,
            'asal_produk' => 2,
            'kategori_media' => 3,
            'kategori_iklan' => 1,
            'pencantuman_nie' => 2,
            'kesesuaian_nie' => null,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 7,
            'pembuat_iklan' => 2,
            'status_standardisasi' => 'terstandar',
            'is_final' => true,
        ]);

        // 3. Menunggu evaluasi row
        $mentah3 = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 3,
            'no' => '3',
            'upt' => 'UPT 1',
            'jenis_produk' => '1',
        ]);

        DataIklan::create([
            'dataset_id' => $this->dataset->id,
            'data_mentah_id' => $mentah3->id,
            'no' => 3,
            'upt' => 1,
            'periode_bulan' => 7,
            'periode_tahun' => 2026,
            'jenis_produk' => 1,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'hasil_evaluasi' => null, // Menunggu evaluasi
            'status_standardisasi' => 'menunggu_evaluasi',
            'is_final' => true,
        ]);

        $service = app(DashboardService::class);
        $metrics = $service->getMetrics(2026, 'SEMUA');

        // Total Final = 3, Menunggu Evaluasi = 1, Dievaluasi = 2
        $this->assertEquals(3, $metrics['kpi']['total_final']);
        $this->assertEquals(1, $metrics['kpi']['menunggu_evaluasi']);
        $this->assertEquals(2, $metrics['kpi']['total_pengawasan']);
        $this->assertEquals(1, $metrics['kpi']['mk']);
        $this->assertEquals(1, $metrics['kpi']['tmk']);
        $this->assertEquals(50.0, $metrics['kpi']['persen_mk']);
        $this->assertEquals(50.0, $metrics['kpi']['persen_tmk']);

        // OBA performance
        $this->assertEquals(1, $metrics['iku']['oba']['total']);
        $this->assertEquals(1, $metrics['iku']['oba']['mk']);
        $this->assertEquals(100.0, $metrics['iku']['oba']['realisasi_persen_mk']);
        $this->assertTrue($metrics['iku']['oba']['tercapai']);

        // SK performance
        $this->assertEquals(1, $metrics['iku']['sk']['total']);
        $this->assertEquals(0, $metrics['iku']['sk']['mk']);
        $this->assertEquals(0.0, $metrics['iku']['sk']['realisasi_persen_mk']);
        $this->assertFalse($metrics['iku']['sk']['tercapai']);

        // Monthly trend has 12 items
        $this->assertCount(12, $metrics['monthly_trend']['labels']);
        $this->assertCount(12, $metrics['monthly_trend']['oba']);
        $this->assertEquals(100.0, $metrics['monthly_trend']['oba'][6]); // Month 7 index 6

        // Dimensions
        $this->assertArrayHasKey('asal_produk', $metrics['dimensions']);
        $this->assertArrayHasKey('kategori_media', $metrics['dimensions']);
        $this->assertArrayHasKey('kategori_iklan', $metrics['dimensions']);
        $this->assertArrayHasKey('pembuat_iklan', $metrics['dimensions']);

        // Top TMK: 1 TIE violation
        $this->assertEquals(1, $metrics['top_tmk']['total_tmk']);

        // NIE E-Commerce summary
        $this->assertEquals(1, $metrics['nie_ecommerce']['oba']['total']);
        $this->assertEquals(1, $metrics['nie_ecommerce']['oba']['nie_ada']);
        $this->assertEquals(1, $metrics['nie_ecommerce']['oba']['nie_sesuai']);
        $this->assertEquals(1, $metrics['nie_ecommerce']['sk']['total']);
        $this->assertEquals(0, $metrics['nie_ecommerce']['sk']['nie_ada']);
        $this->assertEquals(1, $metrics['nie_ecommerce']['sk']['nie_tidak_ada']);
    }
}
