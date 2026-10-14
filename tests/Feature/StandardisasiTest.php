<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\DatasetService;
use App\Services\StandardizationService;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\PemetaanNilaiSeeder;
use Database\Seeders\TargetIkuSeeder;
use Database\Seeders\UptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StandardisasiTest extends TestCase
{
    use RefreshDatabase;

    protected User $evaluator;
    protected Dataset $dataset;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $this->seed([
            UptSeeder::class,
            MasterKodeSeeder::class,
            PemetaanNilaiSeeder::class,
            TargetIkuSeeder::class,
        ]);

        $this->evaluator = User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->dataset = Dataset::create([
            'nama_file_asli' => 'sample_standardisasi.xls',
            'path_file' => 'private/datasets/sample.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 3,
        ]);

        // Create 3 DataMentah rows
        DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 1,
            'no' => '1',
            'upt' => 'Balai Besar POM di Banda Aceh',
            'tanggal_pengambilan_iklan' => '15 Jan 2026',
            'jenis_produk' => 'Obat Tradisional',
            'nama_produk' => 'Minyak Herbal Alami',
            'nomor_izin_edar' => 'TR123456781',
            'asal_produk' => 'Lokal',
            'kategori_media' => 'Visual',
            'media_iklan' => 'E-Commerce',
            'tempat_pengambilan_iklan' => 'Shopee',
            'pencantuman_nomor_izin_edar' => 'Ada (Sesuai) dengan izin edar',
            'klaim_dalam_promosi_iklan' => 'Membantu memelihara kesehatan tubuh',
            'klasifikasi_klaim' => 'Membantu memelihara kesehatan tubuh, membantu memelihara daya tahan tubuh, membantu menghangatkan badan, membantu menyegarkan badan',
            'pembuat_iklan' => 'Pelaku Usaha',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
        ]);

        DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 2,
            'no' => '2',
            'upt' => 'Balai POM di Palu',
            'tanggal_pengambilan_iklan' => '18 Jan 2026',
            'jenis_produk' => 'Obat Tradisional',
            'nama_produk' => 'Kapsul Kuat Perkasa',
            'nomor_izin_edar' => 'TR876543211',
            'asal_produk' => 'Lokal',
            'kategori_media' => 'Visual',
            'media_iklan' => 'E-Commerce',
            'tempat_pengambilan_iklan' => 'Tokopedia',
            'pencantuman_nomor_izin_edar' => 'Ada (Tidak Sesuai)',
            'klaim_dalam_promosi_iklan' => 'Stamina pria',
            'klasifikasi_klaim' => 'Membantu memelihara kesehatan pria, membantu memelihara stamina pria',
            'pembuat_iklan' => 'Pelaku Usaha',
            'kesimpulan_pusat' => 'TMK - Critical',
            'kategori_tmk' => 'Produk Tanpa Izin Edar',
            'rincian_tmk' => 'TIE',
        ]);

        DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 3,
            'no' => '3',
            'upt' => 'Balai Besar POM di Jakarta',
            'tanggal_pengambilan_iklan' => '20 Jan 2026',
            'jenis_produk' => 'Obat Tradisional',
            'nama_produk' => 'Salep Pegal',
            'nomor_izin_edar' => 'TR999999999',
            'asal_produk' => 'Lokal',
            'kategori_media' => 'Visual',
            'media_iklan' => 'Website',
            'tempat_pengambilan_iklan' => 'www.sehat.com',
            'pencantuman_nomor_izin_edar' => '-',
            'pembuat_iklan' => 'Perseorangan',
            'kesimpulan_pusat' => '', // Menunggu evaluasi
        ]);
    }

    public function test_standardisasi_mengonversi_data_mentah_menjadi_kode_angka(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        $this->assertDatabaseCount('data_iklan', 3);

        $baris1 = DataIklan::where('dataset_id', $this->dataset->id)
            ->whereHas('dataMentah', fn($q) => $q->where('nomor_baris', 1))
            ->first();

        $this->assertNotNull($baris1);
        $this->assertEquals(1, $baris1->upt); // Banda Aceh
        $this->assertEquals(1, $baris1->periode_bulan);
        $this->assertEquals(2026, $baris1->periode_tahun);
        $this->assertEquals(1, $baris1->jenis_produk); // OT = 1
        $this->assertEquals(1, $baris1->asal_produk); // Lokal = 1
        $this->assertEquals(1, $baris1->klaim); // Klaim 1
        $this->assertEquals(2, $baris1->kategori_media); // Visual = 2
        $this->assertEquals(1, $baris1->kategori_iklan); // E-Commerce = 1
        $this->assertEquals(1, $baris1->nama_media); // Shopee = 1
        $this->assertEquals(1, $baris1->pencantuman_nie); // Ada = 1
        $this->assertEquals(1, $baris1->kesesuaian_nie); // Sesuai = 1
        $this->assertEquals(1, $baris1->hasil_evaluasi); // MK = 1
        $this->assertEquals(1, $baris1->pembuat_iklan); // Pelaku Usaha = 1
        $this->assertNull($baris1->rincian_tmk); // MK kosong
        $this->assertNull($baris1->jenis_tmk); // MK kosong

        // Tiga kolom manual HARUS NULL (tidak pernah diisi otomatis)
        $this->assertNull($baris1->kesesuaian_evaluasi);
        $this->assertNull($baris1->kualitas_laporan);
        $this->assertNull($baris1->ketepatan_waktu);
    }

    public function test_standardisasi_aturan_nie_tmk_kritis_dan_tie(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        $baris2 = DataIklan::where('dataset_id', $this->dataset->id)
            ->whereHas('dataMentah', fn($q) => $q->where('nomor_baris', 2))
            ->first();

        $this->assertNotNull($baris2);
        $this->assertEquals(62, $baris2->upt); // Palu = 62
        $this->assertEquals(1, $baris2->pencantuman_nie); // Ada = 1
        $this->assertEquals(2, $baris2->kesesuaian_nie); // Tidak Sesuai = 2
        $this->assertEquals(2, $baris2->hasil_evaluasi); // TMK = 2
        $this->assertEquals(1, $baris2->jenis_tmk); // Critical = 1
        $this->assertEquals(7, $baris2->rincian_tmk); // TIE = 7
    }

    public function test_standardisasi_baris_menunggu_evaluasi(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        $baris3 = DataIklan::where('dataset_id', $this->dataset->id)
            ->whereHas('dataMentah', fn($q) => $q->where('nomor_baris', 3))
            ->first();

        $this->assertNotNull($baris3);
        $this->assertNull($baris3->hasil_evaluasi);
        $this->assertEquals('menunggu_evaluasi', $baris3->status_standardisasi);
        $this->assertNull($baris3->nama_media); // Website -> non-marketplace -> null (-)
    }

    public function test_evaluator_dapat_mengisi_manual_cell_via_autosave(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        $item = DataIklan::where('dataset_id', $this->dataset->id)->first();

        $response = $this->actingAs($this->evaluator)->postJson(
            "/dataset/{$this->dataset->id}/manual-cell/{$item->id}",
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('data_iklan', [
            'id' => $item->id,
            'kesesuaian_evaluasi' => 1,
        ]);
    }

    public function test_evaluator_dapat_isi_massal_kolom_manual(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        $response = $this->actingAs($this->evaluator)->post(
            "/dataset/{$this->dataset->id}/bulk-manual",
            [
                'kolom' => 'kualitas_laporan',
                'nilai' => 1,
                'cakupan' => 'semua_dievaluasi',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Baris 1 & 2 (yang dievaluasi) harus terisi 1
        $this->assertEquals(2, DataIklan::where('dataset_id', $this->dataset->id)->where('kualitas_laporan', 1)->count());
        // Baris 3 (menunggu evaluasi) tetap null
        $baris3 = DataIklan::where('dataset_id', $this->dataset->id)->whereNull('hasil_evaluasi')->first();
        $this->assertNull($baris3->kualitas_laporan);
    }

    public function test_simpan_final_gagal_jika_kolom_manual_belum_lengkap(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        $response = $this->actingAs($this->evaluator)->post("/dataset/{$this->dataset->id}/simpan-final");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertFalse($this->dataset->fresh()->isFinal());
    }

    public function test_simpan_final_berhasil_menerbitkan_nomor_urut_resmi(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        // Isi ketiga kolom manual untuk baris 1 dan 2
        DataIklan::where('dataset_id', $this->dataset->id)
            ->whereNotNull('hasil_evaluasi')
            ->update([
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 1,
            ]);

        $response = $this->actingAs($this->evaluator)->post("/dataset/{$this->dataset->id}/simpan-final");

        $response->assertRedirect(route('dataset.show', $this->dataset));
        $response->assertSessionHas('status');

        $this->dataset->refresh();
        $this->assertEquals('Final', $this->dataset->status);
        $this->assertNotNull($this->dataset->finalized_at);

        // Cek nomor urut resmi
        $this->assertEquals(1, DataIklan::where('dataset_id', $this->dataset->id)->orderBy('id')->first()->no);
        $this->assertTrue(DataIklan::where('dataset_id', $this->dataset->id)->first()->is_final);
    }

    public function test_halaman_data_tersimpan_dan_export_excel(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        DataIklan::where('dataset_id', $this->dataset->id)
            ->whereNotNull('hasil_evaluasi')
            ->update([
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 1,
            ]);

        app(StandardizationService::class)->finalizeDataset($this->dataset);

        $response = $this->actingAs($this->evaluator)->get('/data-tersimpan');
        $response->assertStatus(200);
        $response->assertSee('Data Tersimpan (Standardisasi Final)');

        // Test Export
        $exportResponse = $this->actingAs($this->evaluator)->get('/data-tersimpan/export?tahun=2026');
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $exportResponse->headers->get('content-type'));
    }
}
