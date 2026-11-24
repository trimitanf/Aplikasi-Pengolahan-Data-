<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\TargetIkuSeeder;
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

        $this->seed(MasterKodeSeeder::class);
        $this->seed(TargetIkuSeeder::class);

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
        $response->assertSee('Proporsi Evaluasi Kepatuhan');
        $response->assertSee('IKU OBA');
        $response->assertSee('IKU SK');
        $response->assertSee('UPT DENGAN PELANGGARAN TMK TERTINGGI');
        $response->assertSee('mkTmkPieChart');
        $response->assertSee('topUptTmkChart');
        $response->assertSee('monthlyTrendChart');
        $response->assertSee('topTmkChart');
        $response->assertSee('dimAsalProdukChart');
        $response->assertSee('dimKategoriMediaChart');
        $response->assertSee('dimKategoriIklanChart');
        $response->assertSee('dimPembuatIklanChart');
        $response->assertSee('Panduan Alur Sistem Pengolahan Data Iklan');
        $response->assertSee('Panduan Alur');
        $response->assertDontSee('Panduan Alur Sistem (User Guide)');
        $response->assertSee('Semua Komoditi');
        $response->assertSee('Obat Bahan Alam (OBA)');
        $response->assertSee('Obat Kuasi (OK)');
        $response->assertSee('Suplemen Kesehatan (SK)');
        $response->assertSee('Tahun Pengawasan');
        $response->assertSee('selectTahunPengawasan');
        $response->assertSee('2026');
        $response->assertSee('2020');

        // Verify landscape report components and ensure analytical tables and signature are removed from dashboard
        $response->assertSee('size: A4 landscape');
        $response->assertDontSee('Matriks Tren Kepatuhan Pengawasan Bulanan');
        $response->assertDontSee('Distribusi Kepatuhan Kategori Media Iklan (8 Kanal)');
        $response->assertDontSee('Profil Kepatuhan 3 Karakteristik Iklan');
        $response->assertDontSee('Catatan Analisis Eksekutif & Tindak Lanjut Pengawasan');
        $response->assertDontSee('Penanggung Jawab / Tim Evaluator Pengawasan Iklan');
    }

    public function test_evaluator_can_apply_filters_on_dashboard(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('dashboard', [
            'tahun' => 2026,
            'komoditi' => 'OT',
            'upt' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Dashboard Pengawasan Iklan OTSK');
        $response->assertSee('Obat Bahan Alam (OBA)');

        // Request kedua untuk menguji pengambilan metrik dari Cache (Cache Hit)
        // Memastikan dropdown UPT tidak melempar "Attempt to read property 'kode' on string"
        $cachedResponse = $this->actingAs($this->evaluator)->get(route('dashboard', [
            'tahun' => 2026,
            'komoditi' => 'OT',
            'upt' => 1,
        ]));
        $cachedResponse->assertOk();
        $cachedResponse->assertSee('Semua UPT (Nasional)');
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

        // Year breakdown
        $this->assertArrayHasKey('year_breakdown', $metrics);
        $this->assertNotEmpty($metrics['year_breakdown']);
        $year2026 = collect($metrics['year_breakdown'])->firstWhere('tahun', 2026);
        $this->assertNotNull($year2026);
        $this->assertEquals(3, $year2026['total_iklan_final']);
    }

    public function test_dashboard_and_rekap_ignore_soft_deleted_dataset_and_data_iklan(): void
    {
        // 1 Data Iklan Final di dataset aktif
        DataIklan::create([
            'dataset_id' => $this->dataset->id,
            'data_mentah_id' => $this->dataMentah->id,
            'evaluator_id' => $this->evaluator->id,
            'no' => 1,
            'upt' => 1,
            'periode_bulan' => 7,
            'periode_tahun' => 2026,
            'jenis_produk' => 1,
            'hasil_evaluasi' => 1,
            'is_final' => true,
        ]);

        $service = app(DashboardService::class);
        $rekapService = app(\App\Services\RekapService::class);

        $metricsBefore = $service->computeMetrics(2026);
        $rekapBefore = $rekapService->calculate(2026);

        $this->assertEquals(1, $metricsBefore['kpi']['total_final']);
        $this->assertEquals(1, $rekapBefore['oba']['total_pengawasan']['total']);

        // Soft delete dataset
        $this->dataset->delete();

        // DataIklan di tabel otomatis ikut ter-soft-delete (deleted_at terisi)
        $this->assertSoftDeleted('datasets', ['id' => $this->dataset->id]);
        $this->assertSoftDeleted('data_iklan', ['dataset_id' => $this->dataset->id]);

        // Dashboard dan Rekap harus mengabaikan data dari trash
        $metricsAfter = $service->computeMetrics(2026);
        $rekapAfter = $rekapService->calculate(2026);

        $this->assertEquals(0, $metricsAfter['kpi']['total_final']);
        $this->assertEquals(0, $metricsAfter['kpi']['total_pengawasan']);
        $this->assertEquals(0, $rekapAfter['oba']['total_pengawasan']['total']);

        // Restore dataset
        $this->dataset->restore();
        $this->assertNotSoftDeleted('datasets', ['id' => $this->dataset->id]);
        $this->assertNotSoftDeleted('data_iklan', ['dataset_id' => $this->dataset->id]);

        $metricsRestored = $service->computeMetrics(2026);
        $rekapRestored = $rekapService->calculate(2026);

        $this->assertEquals(1, $metricsRestored['kpi']['total_final']);
        $this->assertEquals(1, $rekapRestored['oba']['total_pengawasan']['total']);
    }

    public function test_evaluated_rows_without_explicit_evaluator_are_final_and_counted_in_dashboard(): void
    {
        $semarangDataset = Dataset::create([
            'nama_file_asli' => 'SIPT_OK_SEMARANG_2026.xlsx',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 7,
            'status' => 'Valid',
            'path_file' => 'datasets/semarang.xlsx',
            'jumlah_baris' => 2,
            'uploaded_by' => $this->evaluator->id,
            'uploaded_at' => now(),
        ]);

        $raw1 = DataMentah::create([
            'dataset_id' => $semarangDataset->id,
            'nomor_baris' => 1,
            'no' => '1',
            'upt' => 'BBPOM di Semarang',
            'jenis_produk' => '3',
            'hasil_penilaian_iklan_upt' => 'MK',
            'kesimpulan_pusat' => 'MEMENUHI KETENTUAN',
            'kesesuaian_evaluasi' => 1,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
            'evaluator_id' => $this->evaluator->id,
        ]);

        $raw2 = DataMentah::create([
            'dataset_id' => $semarangDataset->id,
            'nomor_baris' => 2,
            'no' => '2',
            'upt' => 'BBPOM di Semarang',
            'jenis_produk' => '3',
            'hasil_penilaian_iklan_upt' => 'MK',
            'kesimpulan_pusat' => 'TIDAK MEMENUHI KETENTUAN',
            'kesesuaian_evaluasi' => 2,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
            'evaluator_id' => null, // Evaluator null (misal diisi oleh Admin atau belum dipilih)
        ]);

        DataIklan::create([
            'dataset_id' => $semarangDataset->id,
            'data_mentah_id' => $raw1->id,
            'evaluator_id' => $this->evaluator->id,
            'no' => 1,
            'upt' => 29, // Semarang
            'periode_bulan' => 7,
            'periode_tahun' => 2026,
            'jenis_produk' => 3,
            'hasil_evaluasi' => 1, // MK
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
            'is_final' => true,
        ]);

        DataIklan::create([
            'dataset_id' => $semarangDataset->id,
            'data_mentah_id' => $raw2->id,
            'evaluator_id' => null,
            'no' => 2,
            'upt' => 29, // Semarang
            'periode_bulan' => 7,
            'periode_tahun' => 2026,
            'jenis_produk' => 3,
            'hasil_evaluasi' => 2, // TMK
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
            'is_final' => false,
        ]);

        $standardizationService = app(\App\Services\Iklan\StandardizationService::class);
        $standardizationService->syncEvaluatedRows($semarangDataset);

        $metrics = app(DashboardService::class)->computeMetrics(2026, 'OK', 29);

        // Kedua baris dari Semarang harus terhitung di dashboard: total 2, MK 1, TMK 1
        $this->assertEquals(2, $metrics['kpi']['total_pengawasan']);
        $this->assertEquals(1, $metrics['kpi']['mk']);
        $this->assertEquals(1, $metrics['kpi']['tmk']);

        $iklan2 = DataIklan::where('data_mentah_id', $raw2->id)->first();
        $this->assertTrue($iklan2->is_final);
        $this->assertEquals($this->evaluator->id, $iklan2->evaluator_id);
    }
}
