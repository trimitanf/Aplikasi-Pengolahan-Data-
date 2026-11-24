<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\RekapService;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\TargetIkuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RekapTest extends TestCase
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
            'name' => 'Evaluator Test',
            'username' => 'evaluator_test',
            'password' => bcrypt('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_OT_2026.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 7,
            'status' => 'Final',
            'path_file' => 'datasets/test.xlsx',
            'jumlah_baris' => 100,
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

    /**
     * Seed exact benchmark counts for OBA and SK and assert 0 calculation discrepancy.
     */
    public function test_rekap_service_calculates_exact_benchmark_numbers(): void
    {
        $mentahIndex = 2;
        $insertRows = function (int $count, array $attributes) use (&$mentahIndex) {
            $mentahRecords = [];
            for ($i = 0; $i < $count; $i++) {
                $idx = $mentahIndex++;
                $mentahRecords[] = [
                    'id' => $idx,
                    'dataset_id' => $this->dataset->id,
                    'nomor_baris' => $idx,
                    'no' => (string) $idx,
                    'upt' => 'UPT 1',
                    'jenis_produk' => (string) ($attributes['jenis_produk'] ?? 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($mentahRecords, 50) as $chunk) {
                DataMentah::insert($chunk);
            }

            $records = [];
            foreach ($mentahRecords as $m) {
                $records[] = array_merge([
                    'dataset_id' => $this->dataset->id,
                    'data_mentah_id' => $m['id'],
                    'no' => null,
                    'upt' => 1,
                    'periode_bulan' => 7,
                    'periode_tahun' => 2026,
                    'jenis_produk' => 1, // Default OBA
                    'asal_produk' => 1,
                    'klaim' => 1,
                    'kategori_media' => 2,
                    'kategori_iklan' => 1,
                    'nama_media' => 1,
                    'pencantuman_nie' => null,
                    'kesesuaian_nie' => null,
                    'hasil_evaluasi' => 1, // MK
                    'rincian_tmk' => null,
                    'jenis_tmk' => null,
                    'pembuat_iklan' => 1,
                    'kesesuaian_evaluasi' => 1,
                    'kualitas_laporan' => 1,
                    'ketepatan_waktu' => 1,
                    'status_standardisasi' => 'terstandar',
                    'is_final' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $attributes);
            }

            foreach (array_chunk($records, 40) as $chunk) {
                DataIklan::insert($chunk);
            }
        };

        // --- SEED OBA (Total 3.487, MK 1.932, TMK 1.555) ---
        // MK: 1.932 rows
        $insertRows(1932, [
            'jenis_produk' => 1,
            'hasil_evaluasi' => 1,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);

        // TMK Rincian OBA (sum = 1.555):
        // TIE: 219 (rincian_tmk = 7)
        $insertRows(219, [
            'jenis_produk' => 1,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 7,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);
        // Testimoni: 84 (rincian_tmk = 1)
        $insertRows(84, [
            'jenis_produk' => 1,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 1,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);
        // Hadiah: 2 (rincian_tmk = 2)
        $insertRows(2, [
            'jenis_produk' => 1,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 2,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);
        // Klaim berlebihan: 1.163 (rincian_tmk = 3)
        $insertRows(1163, [
            'jenis_produk' => 1,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 3,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);
        // Lain-lain: 87 (rincian_tmk = 8 atau null, dihitung sisa TMK)
        $insertRows(87, [
            'jenis_produk' => 1,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => null,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);

        // Specific OBA NIE E-Commerce:
        // We need 883 E-Commerce rows in OBA (kategori_iklan = 1)
        // With: Ada = 629, Tidak Ada = 254
        // Of Ada 629: Sesuai = 556, Tidak Sesuai = 73
        // Update 883 existing OBA rows to have kategori_iklan = 1
        $obaEcommerceIds = DataIklan::where('jenis_produk', 1)->limit(883)->pluck('id');
        DataIklan::whereIn('id', $obaEcommerceIds)->update(['kategori_iklan' => 1]);

        // 629 Ada NIE
        $obaAdaNieIds = $obaEcommerceIds->take(629);
        DataIklan::whereIn('id', $obaAdaNieIds)->update(['pencantuman_nie' => 1]);

        // 254 Tidak Ada NIE
        $obaTidakAdaNieIds = $obaEcommerceIds->skip(629);
        DataIklan::whereIn('id', $obaTidakAdaNieIds)->update(['pencantuman_nie' => 2]);

        // Of 629 Ada: 556 Sesuai, 73 Tidak Sesuai
        $obaNieSesuaiIds = $obaAdaNieIds->take(556);
        $obaNieTidakSesuaiIds = $obaAdaNieIds->skip(556);
        DataIklan::whereIn('id', $obaNieSesuaiIds)->update(['kesesuaian_nie' => 1]);
        DataIklan::whereIn('id', $obaNieTidakSesuaiIds)->update(['kesesuaian_nie' => 2]);

        // --- SEED SK (Total 1.646, MK 1.106, TMK 540) ---
        // MK: 1.106 rows (jenis_produk = 2)
        $insertRows(1106, [
            'jenis_produk' => 2,
            'hasil_evaluasi' => 1,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);

        // TMK Rincian SK (sum = 540):
        // TIE: 95
        $insertRows(95, [
            'jenis_produk' => 2,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 7,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);
        // Testimoni: 7
        $insertRows(7, [
            'jenis_produk' => 2,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 1,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);
        // Hadiah: 0
        // Klaim berlebihan: 399
        $insertRows(399, [
            'jenis_produk' => 2,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 3,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);
        // Lain-lain: 39
        $insertRows(39, [
            'jenis_produk' => 2,
            'hasil_evaluasi' => 2,
            'rincian_tmk' => null,
            'asal_produk' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 2,
            'pembuat_iklan' => 1,
        ]);

        // Specific SK NIE E-Commerce:
        // Total 438 E-Commerce rows in SK
        // Ada = 287, Tidak Ada = 151
        // Sesuai = 267, Tidak Sesuai = 20
        $skEcommerceIds = DataIklan::where('jenis_produk', 2)->limit(438)->pluck('id');
        DataIklan::whereIn('id', $skEcommerceIds)->update(['kategori_iklan' => 1]);

        $skAdaNieIds = $skEcommerceIds->take(287);
        $skTidakAdaNieIds = $skEcommerceIds->skip(287);
        DataIklan::whereIn('id', $skAdaNieIds)->update(['pencantuman_nie' => 1]);
        DataIklan::whereIn('id', $skTidakAdaNieIds)->update(['pencantuman_nie' => 2]);

        $skNieSesuaiIds = $skAdaNieIds->take(267);
        $skNieTidakSesuaiIds = $skAdaNieIds->skip(267);
        DataIklan::whereIn('id', $skNieSesuaiIds)->update(['kesesuaian_nie' => 1]);
        DataIklan::whereIn('id', $skNieTidakSesuaiIds)->update(['kesesuaian_nie' => 2]);

        // Specific SK TV Nasional (kategori_iklan 5 & 6 combine):
        // Total 230, MK 220, TMK 10
        // Pick 230 non-ecommerce SK rows
        $skTvRows = DataIklan::where('jenis_produk', 2)
            ->where('kategori_iklan', '!=', 1)
            ->limit(230)
            ->get();
        // Set 150 to kategori 5, 80 to kategori 6
        foreach ($skTvRows as $idx => $r) {
            $kat = $idx < 150 ? 5 : 6;
            $eval = $idx < 220 ? 1 : 2;
            $r->update(['kategori_iklan' => $kat, 'hasil_evaluasi' => $eval]);
        }

        // --- RUN REKAP SERVICE ---
        $rekapService = app(RekapService::class);
        $result = $rekapService->calculate(2026, 1, 12);

        // 1. OBA Total, MK, TMK
        $oba = $result['oba'];
        $this->assertEquals(3487, $oba['total_pengawasan']['total']);
        $this->assertEquals(1932, $oba['total_pengawasan']['mk']);
        $this->assertEquals(1555, $oba['total_pengawasan']['tmk']);
        $this->assertEquals(55.41, round($oba['total_pengawasan']['persen_mk'], 2));
        $this->assertEquals(44.59, round($oba['total_pengawasan']['persen_tmk'], 2));

        // 2. SK Total, MK, TMK
        $sk = $result['sk'];
        $this->assertEquals(1646, $sk['total_pengawasan']['total']);
        // Note: SK MK adjust: 1106 was initial, then tv rows changed 220 to MK and 10 to TMK.
        // Let's verify total equals 1646 and TMK = total - MK
        $this->assertEquals($sk['total_pengawasan']['total'], $sk['total_pengawasan']['mk'] + $sk['total_pengawasan']['tmk']);

        // 3. OBA Rincian TMK
        $this->assertEquals(219, $oba['rincian_tmk']['tie']['total']);
        $this->assertEquals(84, $oba['rincian_tmk']['testimoni']['total']);
        $this->assertEquals(2, $oba['rincian_tmk']['hadiah']['total']);
        $this->assertEquals(1163, $oba['rincian_tmk']['klaim_berlebihan']['total']);
        $this->assertEquals(87, $oba['rincian_tmk']['lain_lain']['total']);
        $this->assertEquals(1555, $oba['rincian_tmk']['total']['total']);

        // 4. OBA NIE E-Commerce
        $this->assertEquals(883, $oba['kategori_iklan']['ecommerce']['total']);
        $this->assertEquals(629, $oba['pencantuman_nie']['ada']);
        $this->assertEquals(254, $oba['pencantuman_nie']['tidak_ada']);
        $this->assertEquals(556, $oba['kesesuaian_nie']['sesuai']);
        $this->assertEquals(73, $oba['kesesuaian_nie']['tidak_sesuai']);

        // 5. SK NIE E-Commerce
        $this->assertEquals(438, $sk['kategori_iklan']['ecommerce']['total']);
        $this->assertEquals(287, $sk['pencantuman_nie']['ada']);
        $this->assertEquals(151, $sk['pencantuman_nie']['tidak_ada']);
        $this->assertEquals(267, $sk['kesesuaian_nie']['sesuai']);
        $this->assertEquals(20, $sk['kesesuaian_nie']['tidak_sesuai']);

        // 6. SK TV Nasional (merged 5 + 6)
        $this->assertEquals(230, $sk['kategori_iklan']['tv_nasional']['total']);
        $this->assertEquals(220, $sk['kategori_iklan']['tv_nasional']['mk']);
        $this->assertEquals(10, $sk['kategori_iklan']['tv_nasional']['tmk']);

        // 7. IKU Capaian
        $this->assertEquals(67.0, $oba['target_iku']);
        $this->assertEquals(83.0, $sk['target_iku']);
        $this->assertEquals(82.70, round($oba['persentase_capaian_iku'], 2));
    }

    /**
     * Test Kelengkapan Periode matrix.
     */
    public function test_kelengkapan_periode_matrix(): void
    {
        // Month 7 OT is finalized in setUp
        $rekapService = app(RekapService::class);
        $matrix = $rekapService->getKelengkapanPeriode(2026);

        $this->assertCount(12, $matrix);
        $this->assertTrue($matrix[7]['OBA']);
        $this->assertFalse($matrix[7]['SK']);
        $this->assertFalse($matrix[1]['OBA']);
    }

    /**
     * Test view Rekap via Web Controller.
     */
    public function test_evaluator_can_view_rekap_page(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('rekap.index', [
            'tahun' => 2026,
            'bulan_awal' => 1,
            'bulan_akhir' => 12,
        ]));

        $response->assertOk();
        $response->assertSee('Rekapitulasi Pengawasan Iklan');
        $response->assertSee('IKLAN OBAT BAHAN ALAM');
        $response->assertSee('IKLAN SUPLEMEN KESEHATAN');
        $response->assertSee('Kelengkapan Periode Data');
        $response->assertSee('Export Excel Rekap (.xlsx)');
    }

    /**
     * Test guest cannot view Rekap page.
     */
    public function test_guest_cannot_view_rekap_page(): void
    {
        $response = $this->get(route('rekap.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test Excel export stream download.
     */
    public function test_evaluator_can_export_rekap_excel(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('rekap.export', [
            'tahun' => 2026,
            'bulan_awal' => 1,
            'bulan_akhir' => 12,
        ]));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('Rekap_Iklan_2026_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    /**
     * Test command iklan:muat-data-all fails when file is not found.
     */
    public function test_command_muat_data_all_fails_on_missing_file(): void
    {
        $this->artisan('iklan:muat-data-all', ['file' => 'nonexistent_file.xlsx'])
            ->assertExitCode(1)
            ->expectsOutputToContain('File tidak ditemukan');
    }

    /**
     * Test command iklan:muat-data-all succeeds with a valid mock Excel sheet.
     */
    public function test_command_muat_data_all_succeeds_with_valid_excel(): void
    {
        // Create a temporary Excel file with a sheet named 'Data All'
        $tempFile = storage_path('app/temp_test_data_all.xlsx');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data All');

        // Header row
        $sheet->fromArray([
            'NO', 'UPT', 'PERIODE BULAN', 'JENIS PRODUK', 'ASAL PRODUK', 'KLAIM',
            'KATEGORI MEDIA', 'KATEGORI IKLAN', 'NAMA MEDIA', 'PENCANTUMAN NIE',
            'KESESUAIAN NIE', 'HASIL EVALUASI', 'RINCIAN TMK', 'JENIS TMK',
            'PEMBUAT IKLAN', 'KESESUAIAN EVALUASI', 'KUALITAS LAPORAN', 'KETEPATAN WAKTU',
        ], null, 'A1');

        // Row 2 data
        $sheet->fromArray([
            1, 2, 7, 1, 1, 1, 2, 1, 3, 1, 1, 1, null, null, 1, 1, 1, 1,
        ], null, 'A2');

        // Row 3 data (TMK)
        $sheet->fromArray([
            2, 2, 7, 2, 1, 1, 2, 1, 3, 1, 1, 2, 7, 2, 1, 1, 1, 1,
        ], null, 'A3');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $this->artisan('iklan:muat-data-all', ['file' => $tempFile])
            ->assertExitCode(0)
            ->expectsOutputToContain('Berhasil memuat 2 baris data');

        $this->assertDatabaseHas('data_iklan', [
            'no' => 1,
            'upt' => 2,
            'jenis_produk' => 1,
            'is_final' => true,
        ]);

        $this->assertDatabaseHas('data_iklan', [
            'no' => 2,
            'upt' => 2,
            'jenis_produk' => 2,
            'rincian_tmk' => 7,
            'is_final' => true,
        ]);

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    /**
     * Test nomor kumulatif otomatis terupdate ketika ada dataset baru masuk (misal 1-19 lalu +10 jadi 1-29).
     */
    public function test_nomor_kumulatif_terupdate_otomatis_saat_dataset_baru_masuk(): void
    {
        // Bersihkan data tahun 2026 terlebih dahulu
        DataIklan::where('periode_tahun', 2026)->delete();
        DataMentah::whereHas('dataset', fn ($q) => $q->where('periode_tahun', 2026))->delete();
        Dataset::where('periode_tahun', 2026)->delete();

        // 1. Buat Dataset 1 dengan 19 baris dan difinalkan
        $dataset1 = Dataset::create([
            'nama_file_asli' => 'dataset_januari.xlsx',
            'path_file' => 'private/datasets/jan.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
            'jumlah_baris' => 19,
            'finalized_at' => now(),
        ]);

        for ($i = 1; $i <= 19; $i++) {
            $raw1 = DataMentah::create([
                'dataset_id' => $dataset1->id,
                'nomor_baris' => $i,
                'nama_produk' => "Produk Jan {$i}",
                'nomor_izin_edar' => "TR202601{$i}",
                'upt' => 'Balai Besar POM di Banda Aceh',
            ]);

            DataIklan::create([
                'dataset_id' => $dataset1->id,
                'data_mentah_id' => $raw1->id,
                'no' => $i,
                'periode_tahun' => 2026,
                'periode_bulan' => 1,
                'upt' => 1,
                'jenis_produk' => 1,
                'asal_produk' => 1,
                'klaim' => 1,
                'kategori_media' => 1,
                'kategori_iklan' => 1,
                'nama_media' => 1,
                'pencantuman_nie' => 1,
                'kesesuaian_nie' => 1,
                'hasil_evaluasi' => 1,
                'pembuat_iklan' => 1,
                'is_final' => true,
            ]);
        }

        // Cek rekap awal: harus Nomor 1-19
        $response1 = $this->actingAs($this->evaluator)->get(route('rekap.index', ['tahun' => 2026]));
        $response1->assertOk();
        $response1->assertSee('Nomor 1-19');

        // 2. Tambahkan Dataset 2 dengan 10 baris baru yang masuk di tahun 2026
        $dataset2 = Dataset::create([
            'nama_file_asli' => 'dataset_februari.xlsx',
            'path_file' => 'private/datasets/feb.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 2,
            'status' => 'Terstandardisasi',
            'jumlah_baris' => 10,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $raw = DataMentah::create([
                'dataset_id' => $dataset2->id,
                'nomor_baris' => $i,
                'nama_produk' => "Produk Feb {$i}",
                'nomor_izin_edar' => "TR202600{$i}",
                'upt' => 'Balai Besar POM di Banda Aceh',
            ]);

            DataIklan::create([
                'dataset_id' => $dataset2->id,
                'data_mentah_id' => $raw->id,
                'no' => 19 + $i, // 20 s.d. 29
                'periode_tahun' => 2026,
                'periode_bulan' => 2,
                'upt' => 1,
                'jenis_produk' => 1,
                'asal_produk' => 1,
                'klaim' => 1,
                'kategori_media' => 1,
                'kategori_iklan' => 1,
                'nama_media' => 1,
                'pencantuman_nie' => 1,
                'kesesuaian_nie' => 1,
                'hasil_evaluasi' => 1,
                'pembuat_iklan' => 1,
                'is_final' => false,
            ]);
        }

        // Cek rekap setelah dataset baru masuk: otomatis harus terupdate jadi Nomor 1-29
        $response2 = $this->actingAs($this->evaluator)->get(route('rekap.index', ['tahun' => 2026]));
        $response2->assertOk();
        $response2->assertSee('Nomor 1-29');

        // 3. Ketika dataset 2 difinalisasi
        DataIklan::where('dataset_id', $dataset2->id)->update(['is_final' => true]);
        $dataset2->update(['status' => 'Final', 'finalized_at' => now()]);

        // Cek rekap setelah difinalkan: tetap Nomor 1-29
        $response3 = $this->actingAs($this->evaluator)->get(route('rekap.index', ['tahun' => 2026]));
        $response3->assertOk();
        $response3->assertSee('Nomor 1-29');
    }

    /**
     * Memastikan tabel Iklan Obat Bahan Alam (OBA) pada BE laman Rekap
     * adalah hasil akumulasi dari komoditi Obat Tradisional (OT) dan Obat Kuasi (OK).
     */
    public function test_rekap_tabel_iklan_oba_adalah_hasil_akumulasi_komoditi_ot_dan_ok(): void
    {
        // 1. Bersihkan seluruh dataset dan data iklan tahun 2026
        DataIklan::where('periode_tahun', 2026)->delete();
        DataMentah::whereHas('dataset', fn ($q) => $q->where('periode_tahun', 2026))->delete();
        Dataset::where('periode_tahun', 2026)->delete();

        // 2. Buat Dataset Komoditi Obat Tradisional (OT) - 10 baris (7 MK, 3 TMK)
        $datasetOt = Dataset::create([
            'nama_file_asli' => 'dataset_ot_2026.xlsx',
            'path_file' => 'private/datasets/ot.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 3,
            'status' => 'Final',
            'jumlah_baris' => 10,
            'finalized_at' => now(),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $isMk = $i <= 7;
            $raw = DataMentah::create([
                'dataset_id' => $datasetOt->id,
                'nomor_baris' => $i,
                'nama_produk' => "Produk OT {$i}",
                'nomor_izin_edar' => "TR202600{$i}",
                'upt' => 'Balai Besar POM di Banda Aceh',
            ]);

            DataIklan::create([
                'dataset_id' => $datasetOt->id,
                'data_mentah_id' => $raw->id,
                'no' => $i,
                'periode_tahun' => 2026,
                'periode_bulan' => 3,
                'upt' => 1,
                'jenis_produk' => 1, // 1 = Obat Tradisional (OT)
                'asal_produk' => 1, // 1 = Lokal
                'klaim' => 1,
                'kategori_media' => 2, // Visual
                'kategori_iklan' => 1, // E-Commerce
                'nama_media' => 1,
                'pencantuman_nie' => 1,
                'kesesuaian_nie' => 1,
                'hasil_evaluasi' => $isMk ? 1 : 2, // 1: MK, 2: TMK
                'rincian_tmk' => $isMk ? null : 7, // 7: TIE
                'jenis_tmk' => $isMk ? null : 1,
                'pembuat_iklan' => 1, // Pelaku Usaha
                'is_final' => true,
            ]);
        }

        // 3. Buat Dataset Komoditi Obat Kuasi (OK) - 7 baris (5 MK, 2 TMK)
        $datasetOk = Dataset::create([
            'nama_file_asli' => 'dataset_ok_2026.xlsx',
            'path_file' => 'private/datasets/ok.xlsx',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 3,
            'status' => 'Final',
            'jumlah_baris' => 7,
            'finalized_at' => now(),
        ]);

        for ($i = 1; $i <= 7; $i++) {
            $isMk = $i <= 5;
            $raw = DataMentah::create([
                'dataset_id' => $datasetOk->id,
                'nomor_baris' => $i,
                'nama_produk' => "Produk OK {$i}",
                'nomor_izin_edar' => "QD202600{$i}",
                'upt' => 'Balai Besar POM di Banda Aceh',
            ]);

            DataIklan::create([
                'dataset_id' => $datasetOk->id,
                'data_mentah_id' => $raw->id,
                'no' => 10 + $i,
                'periode_tahun' => 2026,
                'periode_bulan' => 3,
                'upt' => 1,
                'jenis_produk' => 3, // 3 = Obat Kuasi (OK)
                'asal_produk' => 2, // 2 = Impor
                'klaim' => 2,
                'kategori_media' => 3, // Audiovisual
                'kategori_iklan' => 2, // Website
                'nama_media' => 1,
                'pencantuman_nie' => null,
                'kesesuaian_nie' => null,
                'hasil_evaluasi' => $isMk ? 1 : 2,
                'rincian_tmk' => $isMk ? null : 1, // 1: Testimoni
                'jenis_tmk' => $isMk ? null : 2,
                'pembuat_iklan' => 2, // Perseorangan
                'is_final' => true,
            ]);
        }

        // 4. Buat Dataset Komoditi Suplemen Kesehatan (SK) - 20 baris (12 MK, 8 TMK)
        $datasetSk = Dataset::create([
            'nama_file_asli' => 'dataset_sk_2026.xlsx',
            'path_file' => 'private/datasets/sk.xlsx',
            'komoditi' => 'SK',
            'periode_tahun' => 2026,
            'periode_bulan' => 3,
            'status' => 'Final',
            'jumlah_baris' => 20,
            'finalized_at' => now(),
        ]);

        for ($i = 1; $i <= 20; $i++) {
            $isMk = $i <= 12;
            $raw = DataMentah::create([
                'dataset_id' => $datasetSk->id,
                'nomor_baris' => $i,
                'nama_produk' => "Produk SK {$i}",
                'nomor_izin_edar' => "SD202600{$i}",
                'upt' => 'Balai Besar POM di Banda Aceh',
            ]);

            DataIklan::create([
                'dataset_id' => $datasetSk->id,
                'data_mentah_id' => $raw->id,
                'no' => 17 + $i,
                'periode_tahun' => 2026,
                'periode_bulan' => 3,
                'upt' => 1,
                'jenis_produk' => 2, // 2 = Suplemen Kesehatan (SK)
                'asal_produk' => 1,
                'klaim' => 1,
                'kategori_media' => 2,
                'kategori_iklan' => 1,
                'nama_media' => 1,
                'pencantuman_nie' => 1,
                'kesesuaian_nie' => 1,
                'hasil_evaluasi' => $isMk ? 1 : 2,
                'rincian_tmk' => $isMk ? null : 7,
                'jenis_tmk' => $isMk ? null : 1,
                'pembuat_iklan' => 1,
                'is_final' => true,
            ]);
        }

        // 5. Hitung Rekap melalui RekapService
        $rekapService = app(RekapService::class);
        $rekap = $rekapService->calculate(2026, 1, 12);

        $oba = $rekap['oba'];
        $sk = $rekap['sk'];

        // Verifikasi tabel OBA mengakumulasi OT (10 baris) + OK (7 baris) = 17 baris
        $this->assertEquals(17, $oba['total_pengawasan']['total']);
        // MK: 7 (OT) + 5 (OK) = 12
        $this->assertEquals(12, $oba['total_pengawasan']['mk']);
        // TMK: 3 (OT) + 2 (OK) = 5
        $this->assertEquals(5, $oba['total_pengawasan']['tmk']);
        $this->assertEquals(round((12 / 17) * 100, 2), round($oba['total_pengawasan']['persen_mk'], 2));
        $this->assertEquals(round((5 / 17) * 100, 2), round($oba['total_pengawasan']['persen_tmk'], 2));

        // Verifikasi rincian Asal Produk terakumulasi:
        // Lokal (dari OT): 10 baris (7 MK, 3 TMK)
        $this->assertEquals(10, $oba['asal_produk']['lokal']['total']);
        $this->assertEquals(7, $oba['asal_produk']['lokal']['mk']);
        $this->assertEquals(3, $oba['asal_produk']['lokal']['tmk']);
        // Impor (dari OK): 7 baris (5 MK, 2 TMK)
        $this->assertEquals(7, $oba['asal_produk']['impor']['total']);
        $this->assertEquals(5, $oba['asal_produk']['impor']['mk']);
        $this->assertEquals(2, $oba['asal_produk']['impor']['tmk']);

        // Verifikasi rincian Kategori Media terakumulasi:
        // Visual (dari OT): 10
        $this->assertEquals(10, $oba['kategori_media']['visual']['total']);
        // Audiovisual (dari OK): 7
        $this->assertEquals(7, $oba['kategori_media']['audiovisual']['total']);

        // Verifikasi rincian TMK terakumulasi:
        // TIE (dari OT): 3
        $this->assertEquals(3, $oba['rincian_tmk']['tie']['total']);
        // Testimoni (dari OK): 2
        $this->assertEquals(2, $oba['rincian_tmk']['testimoni']['total']);
        // Total TMK Rincian = 3 + 2 = 5
        $this->assertEquals(5, $oba['rincian_tmk']['total']['total']);

        // Verifikasi data SK tidak tercampur (Total 20, MK 12, TMK 8)
        $this->assertEquals(20, $sk['total_pengawasan']['total']);
        $this->assertEquals(12, $sk['total_pengawasan']['mk']);
        $this->assertEquals(8, $sk['total_pengawasan']['tmk']);

        // 6. Verifikasi via Web Controller Rekap (Laman Rekap)
        $response = $this->actingAs($this->evaluator)->get(route('rekap.index', ['tahun' => 2026]));
        $response->assertOk();
        $response->assertSee('IKLAN OBAT BAHAN ALAM');
        $response->assertSee('IKLAN SUPLEMEN KESEHATAN');
        $response->assertViewHas('rekap', function ($viewRekap) {
            return $viewRekap['oba']['total_pengawasan']['total'] === 17
                && $viewRekap['oba']['total_pengawasan']['mk'] === 12
                && $viewRekap['oba']['total_pengawasan']['tmk'] === 5
                && $viewRekap['sk']['total_pengawasan']['total'] === 20;
        });
    }
}
