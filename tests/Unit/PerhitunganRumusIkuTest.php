<?php

namespace Tests\Unit;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\TargetIku;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerhitunganRumusIkuTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardService::class);
    }

    /**
     * Uji rumus perhitungan matematis IKU:
     * - % MK = (Total MK / Total Pengawasan) * 100
     * - Capaian terhadap Target = (% MK / Target IKU) * 100
     * - Target Tercapai jika Realisasi % MK >= Target IKU (67% untuk OBA, 83% untuk SK)
     */
    public function test_perhitungan_rumus_persen_mk_dan_capaian_target_iku(): void
    {
        $user = User::create([
            'name' => 'Evaluator Test',
            'username' => 'eval_test_iku',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        // 1. Set Target IKU resmi: OBA 67% dan SK 83%
        TargetIku::create([
            'tahun' => 2026,
            'kelompok' => 'OBA',
            'target' => 67.00,
        ]);
        TargetIku::create([
            'tahun' => 2026,
            'kelompok' => 'SK',
            'target' => 83.00,
        ]);

        $dataset = Dataset::create([
            'uploaded_by' => $user->id,
            'nama_file_asli' => 'sipt_test_iku.xls',
            'path_file' => 'private/datasets/test.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
            'jumlah_baris' => 10,
        ]);

        // Skenario OBA: 10 iklan, 7 MK (70%), 3 TMK (30%)
        // 70% >= 67% -> Target IKU OBA Tercapai
        for ($i = 1; $i <= 10; $i++) {
            $mentah = DataMentah::create([
                'dataset_id' => $dataset->id,
                'nomor_baris' => $i,
                'no' => (string) $i,
                'upt' => 'UPT 1',
                'jenis_produk' => '1',
            ]);
            DataIklan::create([
                'dataset_id' => $dataset->id,
                'data_mentah_id' => $mentah->id,
                'uploaded_by' => $user->id,
                'no' => $i,
                'upt' => 1,
                'periode_bulan' => 1,
                'periode_tahun' => 2026,
                'jenis_produk' => 1, // OBA
                'hasil_evaluasi' => $i <= 7 ? 1 : 2, // 7 MK, 3 TMK
                'is_final' => true,
            ]);
        }

        // Skenario SK: 10 iklan, 8 MK (80%), 2 TMK (20%)
        // 80% < 83% -> Target IKU SK Belum Tercapai (Dalam Proses)
        for ($i = 11; $i <= 20; $i++) {
            $mentah = DataMentah::create([
                'dataset_id' => $dataset->id,
                'nomor_baris' => $i,
                'no' => (string) $i,
                'upt' => 'UPT 1',
                'jenis_produk' => '2',
            ]);
            DataIklan::create([
                'dataset_id' => $dataset->id,
                'data_mentah_id' => $mentah->id,
                'uploaded_by' => $user->id,
                'no' => $i,
                'upt' => 1,
                'periode_bulan' => 1,
                'periode_tahun' => 2026,
                'jenis_produk' => 2, // SK
                'hasil_evaluasi' => $i <= 18 ? 1 : 2, // 8 MK, 2 TMK
                'is_final' => true,
            ]);
        }

        $metrics = $this->dashboardService->getMetrics(2026, 'SEMUA');
        $ikuOba = $metrics['iku']['oba'];
        $ikuSk = $metrics['iku']['sk'];

        // Verifikasi Perhitungan OBA
        $this->assertEquals(67.00, $ikuOba['target']);
        $this->assertEquals(10, $ikuOba['total']);
        $this->assertEquals(7, $ikuOba['mk']);
        $this->assertEquals(3, $ikuOba['tmk']);
        $this->assertEquals(70.0, $ikuOba['realisasi_persen_mk'], 'Realisasi OBA harus 7 / 10 * 100 = 70%');
        
        // Rumus Capaian: (70 / 67) * 100 = 104.4776%
        $expectedCapaianOba = (70.0 / 67.0) * 100;
        $this->assertEqualsWithDelta($expectedCapaianOba, $ikuOba['capaian_persen'], 0.01);
        $this->assertTrue($ikuOba['tercapai'], 'OBA 70% harus berstatus target tercapai karena >= 67%');

        // Verifikasi Perhitungan SK
        $this->assertEquals(83.00, $ikuSk['target']);
        $this->assertEquals(10, $ikuSk['total']);
        $this->assertEquals(8, $ikuSk['mk']);
        $this->assertEquals(2, $ikuSk['tmk']);
        $this->assertEquals(80.0, $ikuSk['realisasi_persen_mk'], 'Realisasi SK harus 8 / 10 * 100 = 80%');

        // Rumus Capaian: (80 / 83) * 100 = 96.3855%
        $expectedCapaianSk = (80.0 / 83.0) * 100;
        $this->assertEqualsWithDelta($expectedCapaianSk, $ikuSk['capaian_persen'], 0.01);
        $this->assertFalse($ikuSk['tercapai'], 'SK 80% belum tercapai karena < 83%');
    }

    /**
     * Uji penanganan kondisi batas ketika total pengawasan adalah 0 (mencegah error division by zero).
     */
    public function test_perhitungan_iku_aman_saat_data_kosong_zero_division(): void
    {
        $metrics = $this->dashboardService->getMetrics(2026, 'SEMUA');

        $this->assertEquals(0, $metrics['kpi']['total_pengawasan']);
        $this->assertEquals(0.0, $metrics['kpi']['persen_mk']);
        $this->assertEquals(0.0, $metrics['kpi']['persen_tmk']);

        $this->assertEquals(0, $metrics['iku']['oba']['total']);
        $this->assertEquals(0.0, $metrics['iku']['oba']['realisasi_persen_mk']);
        $this->assertEquals(0.0, $metrics['iku']['oba']['capaian_persen']);
        $this->assertFalse($metrics['iku']['oba']['tercapai']);

        $this->assertEquals(0, $metrics['iku']['sk']['total']);
        $this->assertEquals(0.0, $metrics['iku']['sk']['realisasi_persen_mk']);
        $this->assertEquals(0.0, $metrics['iku']['sk']['capaian_persen']);
        $this->assertFalse($metrics['iku']['sk']['tercapai']);
    }
}
