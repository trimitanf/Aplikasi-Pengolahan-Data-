<?php

namespace Tests\Unit;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\RekapService;
use App\Services\StandardizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KonversiKomoditiObaTest extends TestCase
{
    use RefreshDatabase;

    protected StandardizationService $standardizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->standardizationService = app(StandardizationService::class);
    }

    /**
     * Uji pemetaan string mentah komoditi SIPT menjadi kode standar jenis produk:
     * 1: OBA / Obat Tradisional (OT)
     * 2: Suplemen Kesehatan (SK)
     * 3: Obat Kuasi (OK)
     */
    public function test_standardization_service_resolve_jenis_produk_menjadi_kode_standar(): void
    {
        // 1. Variasi Obat Tradisional / Bahan Alam -> Kode 1 (OBA/OT)
        $this->assertEquals(1, $this->standardizationService->resolveJenisProduk('OT'));
        $this->assertEquals(1, $this->standardizationService->resolveJenisProduk('OBA'));
        $this->assertEquals(1, $this->standardizationService->resolveJenisProduk('Obat Tradisional'));
        $this->assertEquals(1, $this->standardizationService->resolveJenisProduk('Obat Bahan Alam'));
        $this->assertEquals(1, $this->standardizationService->resolveJenisProduk('Jamu Herbal'));
        $this->assertEquals(1, $this->standardizationService->resolveJenisProduk('Fitofarmaka Ekstrak'));

        // 2. Variasi Obat Kuasi -> Kode 3 (OK)
        $this->assertEquals(3, $this->standardizationService->resolveJenisProduk('OK'));
        $this->assertEquals(3, $this->standardizationService->resolveJenisProduk('Obat Kuasi'));
        $this->assertEquals(3, $this->standardizationService->resolveJenisProduk('KUASI'));

        // 3. Variasi Suplemen Kesehatan -> Kode 2 (SK)
        $this->assertEquals(2, $this->standardizationService->resolveJenisProduk('SK'));
        $this->assertEquals(2, $this->standardizationService->resolveJenisProduk('Suplemen Kesehatan'));
        $this->assertEquals(2, $this->standardizationService->resolveJenisProduk('SUPLEMEN'));

        // 4. Input kosong / null
        $this->assertNull($this->standardizationService->resolveJenisProduk(null));
        $this->assertNull($this->standardizationService->resolveJenisProduk('   '));
    }

    /**
     * Uji logika RekapService: Tabel OBA secara bisnis mengakumulasi komoditi OT (kode 1) dan OK (kode 3).
     */
    public function test_rekap_service_menggabungkan_ot_dan_ok_ke_dalam_kelompok_oba(): void
    {
        $user = User::create([
            'name' => 'Evaluator Test',
            'username' => 'eval_test_oba',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);
        $dataset = Dataset::create([
            'uploaded_by' => $user->id,
            'nama_file_asli' => 'sipt_januari_2026.xls',
            'path_file' => 'private/datasets/test.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
            'jumlah_baris' => 3,
        ]);

        // Baris 1: Obat Tradisional (kode 1) - MK (1)
        $mentah1 = DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 1,
            'no' => '1',
            'upt' => 'UPT 1',
            'jenis_produk' => '1',
        ]);
        DataIklan::create([
            'dataset_id' => $dataset->id,
            'data_mentah_id' => $mentah1->id,
            'uploaded_by' => $user->id,
            'no' => 1,
            'upt' => 1,
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 1, // OT / OBA
            'asal_produk' => 1,
            'hasil_evaluasi' => 1, // MK
            'is_final' => true,
        ]);

        // Baris 2: Obat Kuasi (kode 3) - TMK (2)
        $mentah2 = DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 2,
            'no' => '2',
            'upt' => 'UPT 1',
            'jenis_produk' => '3',
        ]);
        DataIklan::create([
            'dataset_id' => $dataset->id,
            'data_mentah_id' => $mentah2->id,
            'uploaded_by' => $user->id,
            'no' => 2,
            'upt' => 1,
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 3, // OK
            'asal_produk' => 1,
            'hasil_evaluasi' => 2, // TMK
            'is_final' => true,
        ]);

        // Baris 3: Suplemen Kesehatan (kode 2) - MK (1)
        $mentah3 = DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 3,
            'no' => '3',
            'upt' => 'UPT 1',
            'jenis_produk' => '2',
        ]);
        DataIklan::create([
            'dataset_id' => $dataset->id,
            'data_mentah_id' => $mentah3->id,
            'uploaded_by' => $user->id,
            'no' => 3,
            'upt' => 1,
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 2, // SK
            'asal_produk' => 1,
            'hasil_evaluasi' => 1, // MK
            'is_final' => true,
        ]);

        $rekapService = app(RekapService::class);
        $rekap = $rekapService->calculate(2026, 1, 1);

        // Verifikasi kelompok OBA (OT kode 1 + OK kode 3)
        $this->assertEquals(2, $rekap['oba']['total_pengawasan']['total'], 'OBA harus mencakup akumulasi OT (1) + OK (1) = 2 iklan');
        $this->assertEquals(1, $rekap['oba']['total_pengawasan']['mk'], 'OBA MK harus bernilai 1');
        $this->assertEquals(1, $rekap['oba']['total_pengawasan']['tmk'], 'OBA TMK harus bernilai 1');
        $this->assertEquals(50.0, $rekap['oba']['total_pengawasan']['persen_mk'], 'Persentase % MK OBA harus bernilai 50%');

        // Verifikasi kelompok SK (hanya kode 2)
        $this->assertEquals(1, $rekap['sk']['total_pengawasan']['total'], 'SK harus terisolasi hanya jenis_produk = 2');
        $this->assertEquals(1, $rekap['sk']['total_pengawasan']['mk']);
        $this->assertEquals(100.0, $rekap['sk']['total_pengawasan']['persen_mk']);
    }
}
