<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\SiptDatasetReader;
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
            'uploaded_by' => $this->evaluator->id,
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
            'kesimpulan_upt' => 'MK',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
            'kesesuaian_hasil_evaluasi' => 'Sesuai',
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
            'kesimpulan_upt' => 'TMK',
            'kesimpulan_pusat' => 'TMK - Critical',
            'kesesuaian_hasil_evaluasi' => 'Tidak Sesuai',
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
            ->whereHas('dataMentah', fn ($q) => $q->where('nomor_baris', 1))
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

        // Kesesuaian evaluasi dikonversi dari kesesuaian_hasil_evaluasi ('Sesuai' = 1), sedangkan 2 kolom manual NULL
        $this->assertEquals(1, $baris1->kesesuaian_evaluasi);
        $this->assertNull($baris1->kualitas_laporan);
        $this->assertNull($baris1->ketepatan_waktu);
    }

    public function test_standardisasi_aturan_nie_tmk_kritis_dan_tie(): void
    {
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        $baris2 = DataIklan::where('dataset_id', $this->dataset->id)
            ->whereHas('dataMentah', fn ($q) => $q->where('nomor_baris', 2))
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
            ->whereHas('dataMentah', fn ($q) => $q->where('nomor_baris', 3))
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
        DataMentah::where('dataset_id', $this->dataset->id)->update([
            'evaluator_id' => $this->evaluator->id,
            'kesesuaian_evaluasi' => 1,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        app(StandardizationService::class)->standardizeDataset($this->dataset);

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
        DataMentah::where('dataset_id', $this->dataset->id)->update([
            'evaluator_id' => $this->evaluator->id,
            'kesesuaian_evaluasi' => 1,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        app(StandardizationService::class)->standardizeDataset($this->dataset);
        app(StandardizationService::class)->finalizeDataset($this->dataset);

        $response = $this->actingAs($this->evaluator)->get('/data-tersimpan');
        $response->assertStatus(200);
        $response->assertSee('Data Terstandarisasi');

        // Test Export
        $exportResponse = $this->actingAs($this->evaluator)->get('/data-tersimpan/export?tahun=2026');
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $exportResponse->headers->get('content-type'));
    }

    public function test_standardisasi_membawa_nilai_manual_dari_data_mentah_tanpa_input_manual_di_standarisasi(): void
    {
        // Isi nilai manual pada level data mentah
        DataMentah::where('dataset_id', $this->dataset->id)
            ->whereNotNull('kesimpulan_pusat')
            ->where('kesimpulan_pusat', '!=', '')
            ->update([
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 2,
            ]);

        // Jalankan standardisasi
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        // Verifikasi bahwa nilai masuk ke data_iklan
        $baris1 = DataIklan::where('dataset_id', $this->dataset->id)
            ->whereHas('dataMentah', fn ($q) => $q->where('nomor_baris', 1))
            ->first();

        $this->assertEquals(1, $baris1->kesesuaian_evaluasi);
        $this->assertEquals(1, $baris1->kualitas_laporan);
        $this->assertEquals(2, $baris1->ketepatan_waktu);

        // Akses halaman standardisasi
        $response = $this->actingAs($this->evaluator)->get("/standarisasi/{$this->dataset->id}");
        $response->assertStatus(200);

        // Header kolom ada tetapi tidak ada badge "Input manual"
        $response->assertSee('KESESUAIAN EVALUASI');
        $response->assertDontSee('Input manual');

        // Nilai siap ditampilkan, tidak ada lagi select input untuk cell manual
        $response->assertDontSee('saveCell(');
    }

    public function test_cross_column_inference_dan_pembuat_iklan_dash_terstandar_otomatis(): void
    {
        $raw = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 99,
            'upt' => 'BALAI POM DI BALIKPAPAN',
            'tanggal_pengambilan_iklan' => '10 Feb 2026',
            'jenis_produk' => '',
            'nama_produk' => 'HERBA ASIMOR',
            'nomor_izin_edar' => '-',
            'asal_produk' => '',
            'kategori_media' => '',
            'media_iklan' => 'MEDIASOSIAL',
            'tempat_pengambilan_iklan' => 'Tiktok',
            'pembuat_iklan' => '-',
            'kesimpulan_upt' => 'MK',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
            'kesesuaian_hasil_evaluasi' => 'Sesuai',
        ]);

        $service = app(StandardizationService::class);
        $result = $service->standardizeRow($this->dataset, $raw);

        // Kolom yang kosong/strip di SIPT tidak boleh diisi tebakan otomatis (harus null)
        $this->assertNull($result['kategori_media']);
        $this->assertNull($result['jenis_produk']);
        $this->assertNull($result['asal_produk']);
        $this->assertNull($result['pembuat_iklan']);
        // Periode bulan dari Tanggal Pengambilan Iklan (10 Feb 2026 -> 2)
        $this->assertEquals(2, $result['periode_bulan']);
        // Pencantuman NIE dari PENCANTUMAN NOMOR IZIN EDAR (kosong/null/strip di raw SIPT -> null)
        $this->assertNull($result['pencantuman_nie']);
        // Kesesuaian NIE (karena pencantuman NIE null -> null)
        $this->assertNull($result['kesesuaian_nie']);
        $this->assertEquals(1, $result['kesesuaian_evaluasi']);
        // Status standardisasi harus terstandar
        $this->assertEquals('terstandar', $result['status_standardisasi']);
    }

    public function test_pencantuman_nie_diambil_dari_kolom_pencantuman_nomor_izin_edar_dan_null_jika_kosong(): void
    {
        $service = app(StandardizationService::class);

        // 1. Jika pencantuman_nomor_izin_edar null, meskipun nomor_izin_edar terisi valid -> pencantuman_nie null
        $rawNull = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 101,
            'upt' => 'Balai Besar POM di Jakarta',
            'tanggal_pengambilan_iklan' => '10 Jan 2026',
            'nomor_izin_edar' => 'QD142612781E',
            'pencantuman_nomor_izin_edar' => null,
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
        ]);
        $resNull = $service->standardizeRow($this->dataset, $rawNull);
        $this->assertNull($resNull['pencantuman_nie']);
        $this->assertNull($resNull['kesesuaian_nie']);

        // 2. Jika pencantuman_nomor_izin_edar berisi '-' (strip / kosong di SIPT) -> pencantuman_nie null
        $rawStrip = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 102,
            'upt' => 'Balai Besar POM di Jakarta',
            'tanggal_pengambilan_iklan' => '10 Jan 2026',
            'nomor_izin_edar' => 'QD142612781E',
            'pencantuman_nomor_izin_edar' => '-',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
        ]);
        $resStrip = $service->standardizeRow($this->dataset, $rawStrip);
        $this->assertNull($resStrip['pencantuman_nie']);
        $this->assertNull($resStrip['kesesuaian_nie']);

        // 3. Jika pencantuman_nomor_izin_edar terisi 'Ada (Sesuai)' -> pencantuman_nie = 1, kesesuaian_nie = 1
        $rawAdaSesuai = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 103,
            'upt' => 'Balai Besar POM di Jakarta',
            'tanggal_pengambilan_iklan' => '10 Jan 2026',
            'nomor_izin_edar' => 'QD142612781E',
            'pencantuman_nomor_izin_edar' => 'Ada (Sesuai)',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
        ]);
        $resAdaSesuai = $service->standardizeRow($this->dataset, $rawAdaSesuai);
        $this->assertEquals(1, $resAdaSesuai['pencantuman_nie']);
        $this->assertEquals(1, $resAdaSesuai['kesesuaian_nie']);

        // 4. Jika pencantuman_nomor_izin_edar terisi 'Ada (Tidak Sesuai)' -> pencantuman_nie = 1, kesesuaian_nie = 2
        $rawAdaTidakSesuai = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 104,
            'upt' => 'Balai Besar POM di Jakarta',
            'tanggal_pengambilan_iklan' => '10 Jan 2026',
            'nomor_izin_edar' => 'QD142612781E',
            'pencantuman_nomor_izin_edar' => 'Ada (Tidak Sesuai)',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
        ]);
        $resAdaTidakSesuai = $service->standardizeRow($this->dataset, $rawAdaTidakSesuai);
        $this->assertEquals(1, $resAdaTidakSesuai['pencantuman_nie']);
        $this->assertEquals(2, $resAdaTidakSesuai['kesesuaian_nie']);

        // 5. Jika pencantuman_nomor_izin_edar terisi 'Tidak Ada' / 'Tidak Mencantumkan' -> pencantuman_nie = 2, kesesuaian_nie = null
        $rawTidakAda = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 105,
            'upt' => 'Balai Besar POM di Jakarta',
            'tanggal_pengambilan_iklan' => '10 Jan 2026',
            'nomor_izin_edar' => '-',
            'pencantuman_nomor_izin_edar' => 'Tidak Mencantumkan NIE',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
        ]);
        $resTidakAda = $service->standardizeRow($this->dataset, $rawTidakAda);
        $this->assertEquals(2, $resTidakAda['pencantuman_nie']);
        $this->assertNull($resTidakAda['kesesuaian_nie']);
    }

    public function test_sipt_reader_mengabaikan_baris_semu_dan_merge_multiline_cell(): void
    {
        $rows = [
            // Row 1: Valid
            ['nama_produk' => 'PRODUK A', 'nomor_izin_edar' => 'TR12345', 'tindak_lanjut' => 'Peringatan 1'],
            // Row 2: Continuation row (no product, only tindak lanjut)
            ['nama_produk' => '', 'nomor_izin_edar' => '', 'upt' => '', 'media_iklan' => '', 'tindak_lanjut' => 'Peringatan 2'],
            // Row 3: Completely phantom empty row
            ['nama_produk' => '', 'nomor_izin_edar' => '', 'upt' => '', 'media_iklan' => '', 'tindak_lanjut' => ''],
        ];

        $this->assertTrue(SiptDatasetReader::isAdDataRow($rows[0]));
        $this->assertFalse(SiptDatasetReader::isAdDataRow($rows[1]));
        $this->assertFalse(SiptDatasetReader::isAdDataRow($rows[2]));
    }

    public function test_menu_standarisasi_data_dapat_diakses_langsung(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('standarisasi.index'));

        $response->assertOk();
        $response->assertViewIs('dataset.standardisasi');
        $response->assertSee('Data Terstandarisasi');
        $response->assertSee('sample_standardisasi.xls');
    }

    public function test_menu_standarisasi_data_dengan_spesifik_dataset_id(): void
    {
        $dataset2 = Dataset::create([
            'nama_file_asli' => 'sample_sk.xlsx',
            'path_file' => 'private/datasets/sk.xlsx',
            'komoditi' => 'SK',
            'periode_tahun' => 2026,
            'periode_bulan' => 2,
            'status' => 'Valid',
            'uploaded_by' => $this->evaluator->id,
            'jumlah_baris' => 1,
        ]);

        $response = $this->actingAs($this->evaluator)->get(route('standarisasi.index', ['dataset' => $dataset2->id]));

        $response->assertOk();
        $response->assertSee('sample_sk.xlsx');
        $response->assertSee('Suplemen Kesehatan');
    }

    public function test_menu_standarisasi_data_menampilkan_empty_state_jika_tidak_ada_dataset(): void
    {
        DataMentah::query()->delete();
        DataIklan::query()->delete();
        Dataset::query()->delete();

        $response = $this->actingAs($this->evaluator)->get(route('standarisasi.index'));

        $response->assertOk();
        $response->assertViewIs('dataset.standardisasi-empty');
        $response->assertSee('Belum Ada Data Terstandarisasi');
    }

    public function test_manual_cell_update_rejects_cross_dataset_idor(): void
    {
        $service = app(StandardizationService::class);
        $service->standardizeDataset($this->dataset);

        $dataset2 = Dataset::create([
            'nama_file_asli' => 'dataset_lain.xlsx',
            'path_file' => 'private/datasets/lain.xlsx',
            'komoditi' => 'SK',
            'periode_tahun' => 2026,
            'periode_bulan' => 5,
            'status' => 'Valid',
            'jumlah_baris' => 1,
        ]);

        $raw2 = DataMentah::create([
            'dataset_id' => $dataset2->id,
            'nomor_baris' => 1,
            'no' => '1',
            'upt' => 'Balai Besar POM di Banda Aceh',
            'jenis_produk' => 'Suplemen Kesehatan',
            'nama_produk' => 'Produk Lain',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
        ]);

        $service->standardizeDataset($dataset2);
        $item2 = DataIklan::where('dataset_id', $dataset2->id)->firstOrFail();

        // Coba manipulasi IDOR: request ke $this->dataset tapi payload target $item2 milik $dataset2
        $response = $this->actingAs($this->evaluator)
            ->postJson(route('dataset.manual-cell.update', ['dataset' => $this->dataset->id, 'item' => $item2->id]), [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]);

        $response->assertStatus(404);
    }

    public function test_manual_cell_update_rejects_modification_on_final_dataset(): void
    {
        $service = app(StandardizationService::class);
        $service->standardizeDataset($this->dataset);

        $this->dataset->update(['status' => 'Final']);
        $item = DataIklan::where('dataset_id', $this->dataset->id)->firstOrFail();

        $response = $this->actingAs($this->evaluator)
            ->postJson(route('dataset.manual-cell.update', ['dataset' => $this->dataset->id, 'item' => $item->id]), [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]);

        $response->assertStatus(403);
    }

    public function test_evaluator_can_save_mapping_rule_and_immediately_resolves_unmapped_row(): void
    {
        $service = app(StandardizationService::class);

        // Buat DataMentah yang memiliki kombinasi TMK belum terpetakan
        $rawTmk = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 4,
            'no' => '4',
            'upt' => 'Balai Besar POM di Banda Aceh',
            'jenis_produk' => 'Obat Tradisional',
            'nama_produk' => 'Tolak Angin Anak',
            'asal_produk' => 'Lokal',
            'kategori_media' => 'Visual',
            'media_iklan' => 'TV Nasional',
            'tempat_pengambilan_iklan' => 'RCTI',
            'pembuat_iklan' => 'Pelaku Usaha',
            'kesimpulan_pusat' => 'TMK - Mayor',
            'kategori_tmk' => 'Mempunyai persetujuan iklan',
            'rincian_tmk' => '-',
            'kesesuaian_hasil_evaluasi' => 'Sesuai',
        ]);

        $service->standardizeDataset($this->dataset);

        $itemTmk = DataIklan::where('data_mentah_id', $rawTmk->id)->firstOrFail();
        $this->assertNull($itemTmk->rincian_tmk);
        $this->assertEquals('terstandar', $itemTmk->status_standardisasi);

        // Bila rincian_tmk di SIPT diisi nilai baru dan dipetakan via saveMappingRule
        $rawTmk->update(['rincian_tmk' => 'Memberikan hadiah khusus']);
        $this->dataset->update(['uploaded_by' => $this->evaluator->id]);

        $response = $this->actingAs($this->evaluator)->post(route('dataset.mapping.save', $this->dataset), [
            'kolom_sumber' => 'RINCIAN TMK',
            'nilai_sipt' => 'Memberikan hadiah khusus',
            'kode' => '2',
            'grup' => 'rincian_tmk',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $itemTmk->refresh();
        $this->assertEquals(2, $itemTmk->rincian_tmk);
        $this->assertEquals('terstandar', $itemTmk->status_standardisasi);
        $this->assertNull($itemTmk->catatan_tidak_terpetakan);
    }

    public function test_jumlah_baris_dan_urutan_no_standarisasi_sama_persis_dengan_data_mentah_tanpa_offset_acak(): void
    {
        $service = app(StandardizationService::class);
        $service->standardizeDataset($this->dataset); // Dataset 1 (3 baris, status Terstandardisasi)

        // Buat Dataset kedua (misal OK, 4 baris, status Valid) di tahun yang sama
        $datasetOk = Dataset::create([
            'nama_file_asli' => 'SIPT_1 Jan sd 31 Jan 2026_OK.xls',
            'path_file' => 'private/datasets/ok.xls',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 4,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            DataMentah::create([
                'dataset_id' => $datasetOk->id,
                'nomor_baris' => $i,
                'no' => (string) $i,
                'upt' => 'Balai Besar POM di Jakarta',
                'jenis_produk' => 'Obat Kuasi',
                'nama_produk' => "Produk OK Baris {$i}",
                'asal_produk' => 'Lokal',
                'kategori_media' => 'Visual',
                'media_iklan' => 'E-Commerce',
                'tempat_pengambilan_iklan' => 'Shopee',
                'pencantuman_nomor_izin_edar' => 'Ada (Sesuai)',
                'pembuat_iklan' => 'Pelaku Usaha',
                'kesimpulan_pusat' => 'Memenuhi Ketentuan',
                'kesesuaian_hasil_evaluasi' => 'Sesuai',
            ]);
        }

        // Ketika user langsung membuka menu /standarisasi, harus membuka dataset terbaru ($datasetOk), BUKAN dataset lama
        $response = $this->actingAs($this->evaluator)->get(route('standarisasi.index'));
        $response->assertOk();
        $response->assertSee('SIPT_1 Jan sd 31 Jan 2026_OK.xls');

        // Jumlah baris data_iklan harus sama persis dengan data_mentah (4 baris)
        $mentahRows = DataMentah::where('dataset_id', $datasetOk->id)->orderBy('nomor_baris', 'asc')->get();
        $iklanRows = DataIklan::where('dataset_id', $datasetOk->id)->orderBy('no', 'asc')->get();

        $this->assertCount(4, $mentahRows);
        $this->assertCount(4, $iklanRows);

        // Urutan NO dan relasi data_mentah_id harus 1..4 sama persis dengan urutan Data Mentah (bukan 4..7)
        foreach ($iklanRows as $idx => $iklan) {
            $expectedNo = $idx + 1;
            $this->assertEquals($expectedNo, $iklan->no);
            $this->assertEquals($mentahRows[$idx]->id, $iklan->data_mentah_id);
            $this->assertEquals("Produk OK Baris {$expectedNo}", $iklan->dataMentah->nama_produk);
        }
    }

    public function test_halaman_data_terstandarisasi_dan_ekspor_excel_dengan_filter(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'Administrator',
            'username' => 'admin_test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $evaluator1 = \App\Models\User::create([
            'name' => 'Evaluator BPOM 1',
            'username' => 'evaluator1_test',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_Januari_2026_OK.xlsx',
            'path_file' => 'private/datasets/sipt_jan_ok.xlsx',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'uploaded_by' => $admin->id,
            'jumlah_baris' => 2,
        ]);

        $dm1 = DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 1,
            'no' => '1',
            'upt' => 'Balai Besar POM di Jakarta',
            'evaluator_id' => $evaluator1->id,
            'jenis_produk' => 'Obat Kuasi',
            'nama_produk' => 'Balsem Super Jakarta',
            'pencantuman_nomor_izin_edar' => 'Ada (Sesuai)',
            'pembuat_iklan' => 'Pelaku Usaha',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
            'kesesuaian_hasil_evaluasi' => 'Sesuai',
        ]);

        $dm2 = DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 2,
            'no' => '2',
            'upt' => 'Balai Besar POM di Bandung',
            'evaluator_id' => $this->evaluator->id,
            'jenis_produk' => 'Obat Kuasi',
            'nama_produk' => 'Minyak Angin Bandung',
            'pencantuman_nomor_izin_edar' => 'Ada (Sesuai)',
            'pembuat_iklan' => 'Pelaku Usaha',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
            'kesesuaian_hasil_evaluasi' => 'Sesuai',
        ]);

        DataIklan::create([
            'dataset_id' => $dataset->id,
            'data_mentah_id' => $dm1->id,
            'no' => 1,
            'upt' => 1, // Jakarta
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 3,
            'evaluator_id' => $evaluator1->id,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        DataIklan::create([
            'dataset_id' => $dataset->id,
            'data_mentah_id' => $dm2->id,
            'no' => 2,
            'upt' => 2, // Bandung
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 3,
            'evaluator_id' => $this->evaluator->id,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        // 1. Periksa halaman Data Terstandarisasi oleh Admin
        $response = $this->actingAs($admin)->get(route('dataset.standardisasi', $dataset));
        $response->assertOk();
        $response->assertSee('Data Terstandarisasi');
        $response->assertSee('Tabel Data Terstandarisasi');
        $response->assertDontSee('Konversi Ulang');
        $response->assertDontSee('Ekspor Keseluruhan');
        $response->assertDontSee('Data Tersimpan');
        $response->assertDontSee('Simpan Final');
        $response->assertSee('Rekap &rarr;', false);

        // 2. Ekspor Excel tanpa filter (default keseluruhan)
        $exportAll = $this->actingAs($admin)->get(route('dataset.export', $dataset));
        $exportAll->assertOk();
        $this->assertStringContainsString('Data_Terstandarisasi_2026_Januari_OK.xlsx', $exportAll->headers->get('content-disposition'));

        // 3. Ekspor Excel dengan filter UPT
        $exportUpt = $this->actingAs($admin)->get(route('dataset.export', ['dataset' => $dataset->id, 'upt' => 1]));
        $exportUpt->assertOk();
        $this->assertStringContainsString('Data_Terstandarisasi_2026_Januari_OK_UPT_1.xlsx', $exportUpt->headers->get('content-disposition'));

        // 4. Ekspor Excel dengan filter Evaluator
        $exportEv = $this->actingAs($admin)->get(route('dataset.export', ['dataset' => $dataset->id, 'evaluator_id' => $evaluator1->id]));
        $exportEv->assertOk();
        $this->assertStringContainsString('evaluator_bpom_1.xlsx', $exportEv->headers->get('content-disposition'));
    }
}
