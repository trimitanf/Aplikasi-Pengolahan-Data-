<?php

namespace Tests\Feature;

use App\Jobs\ProsesDatasetJob;
use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\DatasetService;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\PemetaanNilaiSeeder;
use Database\Seeders\TargetIkuSeeder;
use Database\Seeders\UptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatasetTest extends TestCase
{
    use RefreshDatabase;

    protected User $evaluator;

    protected User $admin;

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

        $this->admin = User::create([
            'name' => 'Administrator BPOM',
            'username' => 'admin_test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    protected function generateSampleHtmlSipt(string $komoditi = 'Obat Tradisional (OT)', string $periodeMulai = '01 Jan 2026', string $periodeAkhir = '31 Jan 2026', array $customRows = []): string
    {
        $rowsHtml = '';
        if (empty($customRows)) {
            $customRows = [
                [
                    '1', 'Balai Besar POM di Banda Aceh', '15 Jan 2026', '18 Jan 2026', '20 Jan 2026',
                    'Obat Tradisional', 'Minyak Herbal Alami', 'TR123456781', 'Membantu menghangatkan', 'PT Maju Lancar',
                    'Lokal', 'Visual', 'E-Commerce', 'Shopee', 'Ada (Sesuai)', 'https://shopee.co.id/item1',
                    'Klaim herbal berkhasiat', 'Membantu memelihara kesehatan tubuh, membantu memelihara daya tahan tubuh, membantu menghangatkan badan, membantu menyegarkan badan',
                    'Pelaku Usaha', 'MK', 'Memenuhi Ketentuan', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
                ],
                [
                    '2', 'Balai POM di Palu', '18 Jan 2026', '20 Jan 2026', '25 Jan 2026',
                    'Obat Tradisional', 'Kapsul Kuat Perkasa', 'TR876543211', 'Stamina pria', 'PT Herbal Prima',
                    'Lokal', 'Visual', 'E-Commerce', 'Tokopedia', 'Ada (Tidak Sesuai)', 'https://tokopedia.com/item2',
                    'Dahsyat tahan lama', 'Membantu memelihara kesehatan pria, membantu memelihara stamina pria',
                    'Pelaku Usaha', 'TMK', 'TMK - Critical', 'TMK - Critical', 'Sesuai', 'Produk Tanpa Izin Edar', 'Klaim berlebihan', 'Takedown',
                ],
                [
                    '3', 'Balai Besar POM di Jakarta', '20 Jan 2026', '22 Jan 2026', '',
                    'Obat Tradisional', 'Salep Pegal Linu', 'TR999999999', 'Meredakan pegal', 'PT Sehat Terus',
                    'Lokal', 'Visual', 'Website', 'www.sehat.com', '-', '-',
                    'Pegal hilang seketika', 'membantu meredakan sakit kepala, membantu meredakan pegal linu, membantu meringankan bengkak atau memar, membantu meredakan sakit gigi, sebagai parem untuk membantu meredakan pegal linu atau bengkak, membantu meredakakan demam',
                    'Perseorangan', 'MK', 'Memenuhi Ketentuan', '', 'Sesuai', '-', '-', '-',
                ],
            ];
        }

        foreach ($customRows as $row) {
            $rowsHtml .= '<tr>';
            foreach ($row as $cell) {
                $rowsHtml .= '<td>'.htmlspecialchars($cell).'</td>';
            }
            $rowsHtml .= '</tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Export SIPT</title></head>
<body>
    <table>
        <tr><td>UNIT</td><td>:</td><td>Direktorat Pengawasan OTSK</td></tr>
        <tr><td>KOMODITI</td><td>:</td><td>{$komoditi}</td></tr>
        <tr><td>PERIODE MULAI</td><td>:</td><td>{$periodeMulai}</td></tr>
        <tr><td>PERIODE AKHIR</td><td>:</td><td>{$periodeAkhir}</td></tr>
    </table>
    <br/>
    <table>
        <tr>
            <th>NO</th>
            <th>UPT</th>
            <th>TANGGAL PENGAMBILAN IKLAN</th>
            <th>TGL KIRIM KA UPT KE PUSAT</th>
            <th>TANGGAL EVALUASI PUSAT</th>
            <th>JENIS PRODUK</th>
            <th>NAMA PRODUK</th>
            <th>NOMOR IZIN EDAR</th>
            <th>KHASIAT/INDIKASI</th>
            <th>PENDAFTAR</th>
            <th>ASAL PRODUK</th>
            <th>KATEGORI MEDIA</th>
            <th>MEDIA IKLAN</th>
            <th>TEMPAT PENGAMBILAN IKLAN</th>
            <th>PENCANTUMAN NOMOR IZIN EDAR</th>
            <th>LINK URL IKLAN</th>
            <th>KLAIM DALAM PROMOSI/IKLAN</th>
            <th>KLASIFIKASI KLAIM</th>
            <th>PEMBUAT IKLAN</th>
            <th>HASIL PENILAIAN IKLAN UPT</th>
            <th>KESIMPULAN UPT</th>
            <th>KESIMPULAN PUSAT</th>
            <th>KESESUAIAN HASIL EVALUASI</th>
            <th>KATEGORI TMK</th>
            <th>RINCIAN TMK</th>
            <th>TINDAK LANJUT</th>
        </tr>
        {$rowsHtml}
    </table>
</body>
</html>
HTML;
    }

    public function test_halaman_index_dataset_dapat_diakses(): void
    {
        $response = $this->actingAs($this->admin)->get('/dataset');

        $response->assertStatus(200);
        $response->assertSee('Unggah Dataset SIPT');
        $response->assertSee('Daftar Berkas Master Dataset SIPT');
        $response->assertDontSee('Buka Kotak Sampah untuk melihat atau memulihkan dataset yang dihapus');
    }

    public function test_upload_file_sipt_berhasil_dan_membuat_record_dataset(): void
    {
        Queue::fake();

        $content = $this->generateSampleHtmlSipt();
        $file = UploadedFile::fake()->createWithContent('sipt_januari_2026.xls', $content);

        $response = $this->actingAs($this->admin)->post('/dataset', [
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'file' => $file,
        ]);

        $this->assertDatabaseHas('datasets', [
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'nama_file_asli' => 'sipt_januari_2026.xls',
        ]);

        $dataset = Dataset::first();
        $response->assertRedirect(route('dataset.show', $dataset));

        Queue::assertPushed(ProsesDatasetJob::class, function ($job) use ($dataset) {
            return $job->dataset->id === $dataset->id;
        });
    }

    public function test_upload_ditolak_jika_dataset_final_sudah_ada(): void
    {
        Dataset::create([
            'nama_file_asli' => 'existing_final.xls',
            'path_file' => 'private/datasets/existing.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
        ]);

        $content = $this->generateSampleHtmlSipt();
        $file = UploadedFile::fake()->createWithContent('existing_final.xls', $content);

        $response = $this->actingAs($this->admin)->post('/dataset', [
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_dataset_dalam_bulan_yang_sama_namun_tanggal_berbeda_berhasil_diterima(): void
    {
        // Dataset Final batch 1: 01 Jan 2026 s/d 10 Jan 2026
        Dataset::create([
            'nama_file_asli' => 'batch1_jan.xls',
            'path_file' => 'private/datasets/batch1_jan.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'periode_mulai' => '2026-01-01',
            'periode_akhir' => '2026-01-10',
            'status' => 'Final',
        ]);

        // Upload batch 2 di bulan yang sama (Januari 2026) namun rentang tanggal berbeda (11 Jan s/d 20 Jan 2026)
        $content = $this->generateSampleHtmlSipt(
            komoditi: 'Obat Tradisional (OT)',
            periodeMulai: '11 Jan 2026',
            periodeAkhir: '20 Jan 2026'
        );
        Storage::disk('private')->put('datasets/batch2_jan.xls', $content);

        $dataset2 = Dataset::create([
            'nama_file_asli' => 'batch2_jan.xls',
            'path_file' => 'private/datasets/batch2_jan.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset2);

        $dataset2->refresh();
        $this->assertEquals('Valid', $dataset2->status);
        $this->assertEquals('2026-01-11', $dataset2->periode_mulai->toDateString());
        $this->assertEquals('2026-01-20', $dataset2->periode_akhir->toDateString());
    }

    public function test_dataset_ditolak_jika_rentang_tanggal_identik_dengan_dataset_final(): void
    {
        // Dataset Final: 15 Jan 2026 s/d 25 Jan 2026
        Dataset::create([
            'nama_file_asli' => 'dataset_lama.xls',
            'path_file' => 'private/datasets/dataset_lama.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'periode_mulai' => '2026-01-15',
            'periode_akhir' => '2026-01-25',
            'status' => 'Final',
        ]);

        // Upload dataset baru dengan tanggal yang sama persis (15 Jan s/d 25 Jan 2026)
        $content = $this->generateSampleHtmlSipt(
            komoditi: 'Obat Tradisional (OT)',
            periodeMulai: '15 Jan 2026',
            periodeAkhir: '25 Jan 2026'
        );
        Storage::disk('private')->put('datasets/dataset_duplikat_tanggal.xls', $content);

        $datasetDuplikat = Dataset::create([
            'nama_file_asli' => 'dataset_duplikat_tanggal.xls',
            'path_file' => 'private/datasets/dataset_duplikat_tanggal.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($datasetDuplikat);

        $datasetDuplikat->refresh();
        $this->assertEquals('Ditolak', $datasetDuplikat->status);
        $this->assertTrue($datasetDuplikat->issues()->where('tingkat', 'error')->exists());
    }

    public function test_proses_dataset_valid_berhasil_menyimpan_data_mentah(): void
    {
        $content = $this->generateSampleHtmlSipt();
        Storage::disk('private')->put('datasets/test_sipt.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'test_sipt.xls',
            'path_file' => 'private/datasets/test_sipt.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $dataset->refresh();
        $this->assertEquals('Valid', $dataset->status);
        $this->assertEquals(3, $dataset->jumlah_baris);
        $this->assertEquals(1, $dataset->jumlah_menunggu_evaluasi); // Baris 3 kesimpulan pusat kosong

        $this->assertDatabaseCount('data_mentah', 3);
        $this->assertDatabaseHas('data_mentah', [
            'dataset_id' => $dataset->id,
            'nomor_baris' => 1,
            'nama_produk' => 'Minyak Herbal Alami',
        ]);
    }

    public function test_proses_dataset_mendeteksi_header_komoditi_tidak_sesuai(): void
    {
        // Header says Suplemen Kesehatan (SK) but upload selection was OT
        $content = $this->generateSampleHtmlSipt(komoditi: 'Suplemen Kesehatan (SK)');
        Storage::disk('private')->put('datasets/mismatch.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'mismatch.xls',
            'path_file' => 'private/datasets/mismatch.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $dataset->refresh();
        $this->assertEquals('Ditolak', $dataset->status);
        $this->assertDatabaseHas('dataset_issues', [
            'dataset_id' => $dataset->id,
            'tingkat' => 'error',
            'kolom' => 'HEADER KOMODITI',
        ]);
    }

    public function test_proses_dataset_mendeteksi_warning_format_tanggal_dan_baris_identik(): void
    {
        // Baris 1: format tanggal tidak baku -> warning
        // Baris 2 & 3: identik -> warning baris identik
        $customRows = [
            [
                '1', 'Balai Besar POM di Banda Aceh', 'Format Tanggal Rusak', '18 Feb 2026', '20 Feb 2026',
                'Obat Tradisional', 'Minyak Telon', 'TR111', 'Khasiat', 'PT A',
                'Lokal', 'Visual', 'E-Commerce', 'Shopee', 'Ada', 'http://a', 'Klaim', 'Klaim', 'Pelaku Usaha', 'MK', 'MK', 'MK', 'Sesuai', '-', '-', '-',
            ],
            [
                '2', 'Balai Besar POM di Banda Aceh', '10 Jan 2026', '12 Jan 2026', '15 Jan 2026',
                'Obat Tradisional', 'Minyak Kayu Putih', 'TR222', 'Khasiat', 'PT B',
                'Lokal', 'Visual', 'E-Commerce', 'Shopee', 'Ada', 'http://b', 'Klaim', 'Klaim', 'Pelaku Usaha', 'MK', 'MK', 'MK', 'Sesuai', '-', '-', '-',
            ],
            [
                '3', 'Balai Besar POM di Banda Aceh', '10 Jan 2026', '12 Jan 2026', '15 Jan 2026',
                'Obat Tradisional', 'Minyak Kayu Putih', 'TR222', 'Khasiat', 'PT B',
                'Lokal', 'Visual', 'E-Commerce', 'Shopee', 'Ada', 'http://b', 'Klaim', 'Klaim', 'Pelaku Usaha', 'MK', 'MK', 'MK', 'Sesuai', '-', '-', '-',
            ],
        ];

        $content = $this->generateSampleHtmlSipt(customRows: $customRows);
        Storage::disk('private')->put('datasets/warning_test.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'warning_test.xls',
            'path_file' => 'private/datasets/warning_test.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $dataset->refresh();
        // Warning tidak memblokir: status tetap Valid!
        $this->assertEquals('Valid', $dataset->status);

        $this->assertDatabaseHas('dataset_issues', [
            'dataset_id' => $dataset->id,
            'tingkat' => 'warning',
            'nomor_baris' => 1,
            'kolom' => 'TANGGAL PENGAMBILAN IKLAN',
        ]);

        $this->assertDatabaseHas('dataset_issues', [
            'dataset_id' => $dataset->id,
            'tingkat' => 'warning',
            'nomor_baris' => 3,
            'kolom' => 'BARIS IDENTIK',
        ]);
    }

    public function test_halaman_data_mentah_menampilkan_26_kolom_sipt(): void
    {
        $content = $this->generateSampleHtmlSipt();
        Storage::disk('private')->put('datasets/view_test.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'view_test.xls',
            'path_file' => 'private/datasets/view_test.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $response = $this->actingAs($this->evaluator)->get("/dataset/{$dataset->id}/data-mentah");

        $response->assertStatus(200);
        $response->assertSee('Data Mentah SIPT');
        $response->assertSee('Minyak Herbal Alami');
        $response->assertSee('Kapsul Kuat Perkasa');
        $response->assertSee('TR123456781');
    }

    public function test_dataset_dapat_dipindahkan_ke_kotak_sampah(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'delete_me.xls',
            'path_file' => 'private/datasets/delete_me.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
        ]);

        $response = $this->actingAs($this->admin)->delete("/dataset/{$dataset->id}");

        $response->assertRedirect('/dataset');
        $this->assertSoftDeleted('datasets', ['id' => $dataset->id]);
    }

    public function test_dataset_yang_sudah_final_dapat_dipindahkan_ke_sampah(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'final_locked.xls',
            'path_file' => 'private/datasets/final_locked.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
        ]);

        $response = $this->actingAs($this->admin)->delete("/dataset/{$dataset->id}");

        $response->assertRedirect('/dataset');
        $response->assertSessionHas('status');
        $this->assertSoftDeleted('datasets', ['id' => $dataset->id]);
    }

    public function test_halaman_kotak_sampah_menampilkan_dataset_yang_dihapus(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'in_trash.xls',
            'path_file' => 'private/datasets/in_trash.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
        ]);
        $dataset->delete();

        $response = $this->actingAs($this->admin)->get('/dataset/trash');

        $response->assertStatus(200);
        $response->assertSee('Kotak Sampah Dataset');
        $response->assertSee('in_trash.xls');
    }

    public function test_dataset_di_kotak_sampah_dapat_dipulihkan(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'restore_me.xls',
            'path_file' => 'private/datasets/restore_me.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
        ]);
        $dataset->delete();
        $this->assertSoftDeleted('datasets', ['id' => $dataset->id]);

        $response = $this->actingAs($this->admin)->post("/dataset/{$dataset->id}/restore");

        $response->assertSessionHas('status');
        $this->assertNotSoftDeleted('datasets', ['id' => $dataset->id]);
    }

    public function test_dataset_di_kotak_sampah_dapat_dihapus_permanen(): void
    {
        Storage::disk('private')->put('datasets/permanent_delete.xls', 'test content');

        $dataset = Dataset::create([
            'nama_file_asli' => 'permanent_delete.xls',
            'path_file' => 'private/datasets/permanent_delete.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
        ]);
        $dataset->delete();

        $response = $this->actingAs($this->admin)->delete("/dataset/{$dataset->id}/force");

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
        $this->assertFalse(Storage::disk('private')->exists('datasets/permanent_delete.xls'));
    }

    public function test_kotak_sampah_dapat_dikosongkan(): void
    {
        Storage::disk('private')->put('datasets/empty_1.xls', 'test content 1');
        Storage::disk('private')->put('datasets/empty_2.xls', 'test content 2');

        $dataset1 = Dataset::create([
            'nama_file_asli' => 'empty_1.xls',
            'path_file' => 'private/datasets/empty_1.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
        ]);
        $dataset2 = Dataset::create([
            'nama_file_asli' => 'empty_2.xls',
            'path_file' => 'private/datasets/empty_2.xls',
            'komoditi' => 'SK',
            'periode_tahun' => 2026,
            'periode_bulan' => 2,
            'status' => 'Final',
        ]);
        $dataset1->delete();
        $dataset2->delete();

        $this->assertTrue(Storage::disk('private')->exists('datasets/empty_1.xls'));
        $this->assertTrue(Storage::disk('private')->exists('datasets/empty_2.xls'));

        $response = $this->actingAs($this->admin)->post('/dataset/trash/empty');

        $response->assertRedirect('/dataset/trash');
        $this->assertDatabaseMissing('datasets', ['id' => $dataset1->id]);
        $this->assertDatabaseMissing('datasets', ['id' => $dataset2->id]);

        $this->assertFalse(Storage::disk('private')->exists('datasets/empty_1.xls'));
        $this->assertFalse(Storage::disk('private')->exists('datasets/empty_2.xls'));
    }

    public function test_model_force_delete_menghapus_file_fisik_secara_otomatis(): void
    {
        Storage::disk('private')->put('datasets/direct_force_delete.xlsx', 'excel binary content');

        $dataset = Dataset::create([
            'nama_file_asli' => 'direct_force_delete.xlsx',
            'path_file' => 'private/datasets/direct_force_delete.xlsx',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 3,
            'status' => 'Final',
        ]);

        $this->assertTrue(Storage::disk('private')->exists('datasets/direct_force_delete.xlsx'));

        // Panggil forceDelete langsung pada Model
        $dataset->forceDelete();

        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
        $this->assertFalse(Storage::disk('private')->exists('datasets/direct_force_delete.xlsx'));
    }

    public function test_service_force_delete_menghapus_file_fisik_dan_data_database(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('datasets/test_excel.xlsx', 'excel binary data');

        $dataset = Dataset::create([
            'nama_file_asli' => 'test_excel.xlsx',
            'path_file' => 'datasets/test_excel.xlsx',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 5,
            'status' => 'Final',
        ]);

        $this->assertTrue(Storage::disk('local')->exists('datasets/test_excel.xlsx'));

        /** @var DatasetService $service */
        $service = app(DatasetService::class);
        $result = $service->forceDelete($dataset);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
        $this->assertFalse(Storage::disk('local')->exists('datasets/test_excel.xlsx'));
    }

    public function test_service_force_delete_berjalan_lancar_meskipun_file_fisik_sudah_hilang(): void
    {
        Storage::fake('local');

        $dataset = Dataset::create([
            'nama_file_asli' => 'file_sudah_hilang.xlsx',
            'path_file' => 'datasets/file_sudah_hilang.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 5,
            'status' => 'Final',
        ]);

        // File fisik tidak ada di storage
        $this->assertFalse(Storage::disk('local')->exists('datasets/file_sudah_hilang.xlsx'));

        /** @var DatasetService $service */
        $service = app(DatasetService::class);
        $result = $service->forceDelete($dataset);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
    }

    public function test_halaman_show_dataset_langsung_menampilkan_data_mentah_26_kolom_dan_filter(): void
    {
        $content = $this->generateSampleHtmlSipt();
        Storage::disk('private')->put('datasets/view_direct_raw.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'view_direct_raw.xls',
            'path_file' => 'private/datasets/view_direct_raw.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $response = $this->actingAs($this->evaluator)->get("/dataset/{$dataset->id}");

        $response->assertStatus(200);
        $response->assertSee('Data Mentah Hasil Penarikan SIPT (26 Kolom Asli)');
        $response->assertSee('Minyak Herbal Alami');
        $response->assertSee('TR123456781');
        $response->assertSee('Shopee');
        $response->assertSee('name="komoditi"', false);
        $response->assertSee('name="upt"', false);
        $response->assertSee('name="cari"', false);
    }

    public function test_filter_dropdown_upt_pada_halaman_index_dataset(): void
    {
        $response = $this->actingAs($this->evaluator)->get('/dataset');

        $response->assertStatus(200);
        $response->assertSee('name="upt"', false);
        $response->assertSee('Semua UPT');
    }

    public function test_filter_dropdown_upt_pada_halaman_show_dataset(): void
    {
        $content = $this->generateSampleHtmlSipt();
        Storage::disk('private')->put('datasets/filter_upt_test.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'filter_upt_test.xls',
            'path_file' => 'private/datasets/filter_upt_test.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        // UPT 1 = Balai Besar POM di Banda Aceh (baris 1: Minyak Herbal Alami)
        // UPT 62 = Balai POM di Palu (baris 2: Kapsul Kuat Perkasa)
        $responseBandaAceh = $this->actingAs($this->evaluator)->get("/dataset/{$dataset->id}?upt=1");
        $responseBandaAceh->assertStatus(200);
        $responseBandaAceh->assertSee('Minyak Herbal Alami');
        $responseBandaAceh->assertDontSee('Kapsul Kuat Perkasa');

        $responsePalu = $this->actingAs($this->evaluator)->get("/dataset/{$dataset->id}?upt=62");
        $responsePalu->assertStatus(200);
        $responsePalu->assertSee('Kapsul Kuat Perkasa');
        $responsePalu->assertDontSee('Minyak Herbal Alami');
    }

    public function test_filter_halaman_show_dapat_berpindah_ke_dataset_sesuai_komoditi_tahun_bulan(): void
    {
        $datasetOT = Dataset::create([
            'nama_file_asli' => 'data_ot.xls',
            'path_file' => 'private/datasets/data_ot.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 10,
        ]);

        $datasetSK = Dataset::create([
            'nama_file_asli' => 'data_sk.xls',
            'path_file' => 'private/datasets/data_sk.xls',
            'komoditi' => 'SK',
            'periode_tahun' => 2026,
            'periode_bulan' => 2,
            'status' => 'Valid',
            'jumlah_baris' => 15,
        ]);

        // Dari dataset OT, filter ke SK bulan 2 tahun 2026
        $response = $this->actingAs($this->evaluator)->get("/dataset/{$datasetOT->id}?komoditi=SK&tahun=2026&bulan=2");

        $response->assertRedirect(route('dataset.show', ['dataset' => $datasetSK->id]));
    }

    public function test_evaluator_can_download_original_dataset_file(): void
    {
        $content = '<table><tr><td>TEST SIPT</td></tr></table>';
        Storage::disk('private')->put('datasets/test_download.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'data_test_download.xls',
            'path_file' => 'private/datasets/test_download.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 1,
        ]);

        $response = $this->actingAs($this->evaluator)->get(route('dataset.download', $dataset));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-disposition', 'attachment; filename=data_test_download.xls'));
    }

    public function test_download_returns_404_if_physical_file_missing(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'missing.xls',
            'path_file' => 'private/datasets/non_existent.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 1,
        ]);

        $response = $this->actingAs($this->evaluator)->get(route('dataset.download', $dataset));

        $response->assertStatus(404);
    }

    public function test_evaluator_dapat_mengisi_manual_cell_pada_data_mentah_via_autosave(): void
    {
        $content = $this->generateSampleHtmlSipt();
        Storage::disk('private')->put('datasets/manual_cell_test.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'manual_cell_test.xls',
            'path_file' => 'private/datasets/manual_cell_test.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $item = DataMentah::where('dataset_id', $dataset->id)->first();

        $response = $this->actingAs($this->evaluator)->postJson(
            "/dataset/{$dataset->id}/data-mentah/manual-cell/{$item->id}",
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('data_mentah', [
            'id' => $item->id,
            'kesesuaian_evaluasi' => 1,
        ]);
    }

    public function test_evaluator_dapat_isi_massal_kolom_manual_pada_data_mentah(): void
    {
        $content = $this->generateSampleHtmlSipt();
        Storage::disk('private')->put('datasets/bulk_manual_test.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'bulk_manual_test.xls',
            'path_file' => 'private/datasets/bulk_manual_test.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $response = $this->actingAs($this->evaluator)->post(
            "/dataset/{$dataset->id}/data-mentah/bulk-manual",
            [
                'kolom' => 'kualitas_laporan',
                'nilai' => 1,
                'cakupan' => 'semua_dievaluasi',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Baris yang dievaluasi (kesimpulan pusat tidak kosong) harus terisi 1
        $evaluatedCount = DataMentah::where('dataset_id', $dataset->id)
            ->whereNotNull('kesimpulan_pusat')
            ->where('kesimpulan_pusat', '!=', '')
            ->where('kesimpulan_pusat', '!=', '-')
            ->where('kualitas_laporan', 1)
            ->count();
        $this->assertEquals(2, $evaluatedCount);

        // Baris menunggu evaluasi (kesimpulan pusat kosong) tetap null
        $waitingRow = DataMentah::where('dataset_id', $dataset->id)
            ->where(function ($q) {
                $q->whereNull('kesimpulan_pusat')->orWhere('kesimpulan_pusat', '')->orWhere('kesimpulan_pusat', '-');
            })
            ->first();
        $this->assertNull($waitingRow->kualitas_laporan);
    }

    public function test_halaman_show_dataset_menampilkan_3_kolom_manual_evaluator(): void
    {
        $content = $this->generateSampleHtmlSipt();
        Storage::disk('private')->put('datasets/view_manual_cols.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'view_manual_cols.xls',
            'path_file' => 'private/datasets/view_manual_cols.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $response = $this->actingAs($this->evaluator)->get("/dataset/{$dataset->id}");

        $response->assertStatus(200);
        $response->assertSee('KESESUAIAN HASIL EVALUASI');
        $response->assertSee('KUALITAS LAPORAN');
        $response->assertSee('KETEPATAN WAKTU');
        $response->assertSee('Isi Massal Manual');
        $response->assertDontSee('bg-[#FFCDD2]');
        $response->assertDontSee('Input manual');
    }

    public function test_kolom_manual_tetap_dapat_diisi_dan_tersinkron_meski_dataset_final(): void
    {
        $content = $this->generateSampleHtmlSipt();
        Storage::disk('private')->put('datasets/final_manual_test.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'final_manual_test.xls',
            'path_file' => 'private/datasets/final_manual_test.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);
        $dataset->update(['status' => 'Final']);

        $item = DataMentah::where('dataset_id', $dataset->id)->first();

        // 1. Verifikasi halaman show menampilkan input select meskipun Final
        // (Kesesuaian Evaluasi kini otomatis dari SIPT; kolom manual yang tersisa: Kualitas Laporan & Ketepatan Waktu)
        $viewResponse = $this->actingAs($this->evaluator)->get("/dataset/{$dataset->id}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('saveCell(' . $item->id . ', \'kualitas_laporan\'', false);

        // 2. Verifikasi update manual cell tetap berhasil disimpan
        $updateResponse = $this->actingAs($this->evaluator)->postJson(
            "/dataset/{$dataset->id}/data-mentah/manual-cell/{$item->id}",
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]
        );

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('data_mentah', [
            'id' => $item->id,
            'kesesuaian_evaluasi' => 1,
        ]);
    }

    public function test_menu_dropdown_dataset_dan_kotak_sampah_terrender_untuk_seluruh_role(): void
    {
        // 1. Admin melihat dropdown menu Dataset dan Kotak Sampah
        $responseAdmin = $this->actingAs($this->admin)->get('/dashboard');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Kotak Sampah');
        $responseAdmin->assertSee(route('dataset.index'));
        $responseAdmin->assertSee(route('dataset.trash'));

        // 2. Evaluator melihat menu Dataset langsung tanpa dropdown Kotak Sampah
        $responseEval = $this->actingAs($this->evaluator)->get('/dashboard');
        $responseEval->assertStatus(200);
        $responseEval->assertSee('Dataset');
        $responseEval->assertDontSee('Kotak Sampah');
        $responseEval->assertSee(route('dataset.index'));

        // 3. Pada halaman Kotak Sampah, menu aktif dan dapat diakses oleh Admin
        $trashResponse = $this->actingAs($this->admin)->get(route('dataset.trash'));
        $trashResponse->assertStatus(200);
        $trashResponse->assertSee('Kotak Sampah');
    }

    public function test_sipt_raw_export_dengan_komoditi_ot_atau_ok_diterima_sebagai_oba_tanpa_warning_atau_error(): void
    {
        // File SIPT mentah hasil ekspor ber-header 'Obat Kuasi (OK)' dan baris berisi OT & OK
        $customRows = [
            [
                '1', 'Balai Besar POM di Banda Aceh', '15 Jan 2026', '18 Jan 2026', '20 Jan 2026',
                'Obat Tradisional', 'Minyak Herbal Alami', 'TR123456781', 'Khasiat', 'PT Maju Lancar',
                'Lokal', 'Visual', 'E-Commerce', 'Shopee', 'Ada (Sesuai)', 'https://shopee.co.id/item1',
                'Klaim herbal berkhasiat', 'Membantu memelihara kesehatan tubuh', 'Pelaku Usaha', 'MK', 'Memenuhi Ketentuan', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
            ],
            [
                '2', 'Balai POM di Palu', '18 Jan 2026', '20 Jan 2026', '25 Jan 2026',
                'Obat Kuasi', 'Krim Kuasi Sehat', 'QD876543211', 'Khasiat Kuasi', 'PT Sehat Farma',
                'Lokal', 'Visual', 'E-Commerce', 'Tokopedia', 'Ada (Sesuai)', 'https://tokopedia.com/item2',
                'Klaim kuasi', 'Membantu menyegarkan badan', 'Pelaku Usaha', 'MK', 'Memenuhi Ketentuan', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
            ],
            [
                '3', 'Balai Besar POM di Jakarta', '20 Jan 2026', '22 Jan 2026', '24 Jan 2026',
                'OK', 'Salep Kuasi Kulit', 'QI999999999', 'Khasiat Kuasi Impor', 'PT Impor Kuasi',
                'Impor', 'Visual', 'Website', 'www.kuasi.com', '-', '-',
                'Klaim kuasi impor', 'Membantu menyegarkan kulit', 'Pelaku Usaha', 'MK', 'Memenuhi Ketentuan', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
            ],
            [
                '4', 'Balai POM di Batam', '22 Jan 2026', '24 Jan 2026', '26 Jan 2026',
                'OT', 'Madu Herbal Murni', 'TI111222333', 'Madu stamina', 'PT Herbal Batam',
                'Lokal', 'Visual', 'Media Sosial', 'Instagram', '-', '-',
                'Klaim madu', 'Membantu memelihara daya tahan tubuh', 'Pelaku Usaha', 'MK', 'Memenuhi Ketentuan', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
            ],
        ];

        $content = $this->generateSampleHtmlSipt(
            komoditi: 'Obat Kuasi (OK)',
            periodeMulai: '01 Jan 2026',
            periodeAkhir: '31 Jan 2026',
            customRows: $customRows
        );

        Storage::disk('private')->put('datasets/sipt_ot_ok_mix.xls', $content);

        // Upload/buat dataset dengan komoditi OBA
        $dataset = Dataset::create([
            'uploaded_by' => $this->evaluator->id,
            'nama_file_asli' => 'sipt_ot_ok_mix.xls',
            'path_file' => 'private/datasets/sipt_ot_ok_mix.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $dataset->refresh();

        // 1. Status dataset harus Valid
        $this->assertEquals('Valid', $dataset->status);
        $this->assertEquals(4, $dataset->jumlah_baris);
        $this->assertEquals('OBA', $dataset->komoditi);

        // 2. Pastikan TIDAK ADA warning atau error sama sekali di dataset_issues
        $issues = $dataset->issues()->get();
        $this->assertCount(0, $issues, 'Tidak boleh ada warning atau error untuk file SIPT OT/OK yang diunggah ke komoditi OBA');

        // 3. Jalankan standarisasi untuk memastikan pemetaan jenis_produk akurat (1 untuk OT, 3 untuk OK)
        app(\App\Services\StandardizationService::class)->standardizeDataset($dataset);

        $dataIklan = DataIklan::where('dataset_id', $dataset->id)->orderBy('no')->get();
        $this->assertCount(4, $dataIklan);

        // Baris 1: Obat Tradisional -> jenis_produk 1
        $this->assertEquals(1, $dataIklan[0]->jenis_produk);
        // Baris 2: Obat Kuasi -> jenis_produk 3
        $this->assertEquals(3, $dataIklan[1]->jenis_produk);
        // Baris 3: OK -> jenis_produk 3
        $this->assertEquals(3, $dataIklan[2]->jenis_produk);
        // Baris 4: OT -> jenis_produk 1
        $this->assertEquals(1, $dataIklan[3]->jenis_produk);

        // Semua baris terstandar tanpa catatan unmapped pada jenis_produk
        foreach ($dataIklan as $iklan) {
            $unmapped = $iklan->catatan_tidak_terpetakan ?? [];
            $this->assertArrayNotHasKey('jenis_produk', $unmapped);
        }
    }

    public function test_upload_dataset_dengan_komoditi_ot_dan_ok_disimpan_sesuai_pilihan(): void
    {
        Queue::fake();

        $contentOt = $this->generateSampleHtmlSipt(komoditi: 'Obat Tradisional (OT)');
        $fileOt = UploadedFile::fake()->createWithContent('sipt_tradisional.xls', $contentOt);

        $this->actingAs($this->admin)->post('/dataset', [
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 5,
            'file' => $fileOt,
        ]);

        $this->assertDatabaseHas('datasets', [
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 5,
            'nama_file_asli' => 'sipt_tradisional.xls',
        ]);

        $contentOk = $this->generateSampleHtmlSipt(komoditi: 'Obat Kuasi (OK)');
        $fileOk = UploadedFile::fake()->createWithContent('sipt_kuasi.xls', $contentOk);

        $this->actingAs($this->admin)->post('/dataset', [
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 5,
            'file' => $fileOk,
        ]);

        $this->assertDatabaseHas('datasets', [
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 5,
            'nama_file_asli' => 'sipt_kuasi.xls',
        ]);
    }

    public function test_filter_tiga_komoditi_pada_halaman_index_dataset(): void
    {
        Dataset::create([
            'nama_file_asli' => 'data_ot.xls',
            'path_file' => 'private/datasets/data_ot.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 5,
        ]);

        Dataset::create([
            'nama_file_asli' => 'data_ok.xls',
            'path_file' => 'private/datasets/data_ok.xls',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 7,
        ]);

        Dataset::create([
            'nama_file_asli' => 'data_sk.xls',
            'path_file' => 'private/datasets/data_sk.xls',
            'komoditi' => 'SK',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 9,
        ]);

        // Filter OT
        $resOt = $this->actingAs($this->evaluator)->get('/dataset?komoditi=OT');
        $resOt->assertStatus(200);
        $resOt->assertSee('data_ot.xls');
        $resOt->assertDontSee('data_ok.xls');
        $resOt->assertDontSee('data_sk.xls');

        // Filter OK
        $resOk = $this->actingAs($this->evaluator)->get('/dataset?komoditi=OK');
        $resOk->assertStatus(200);
        $resOk->assertSee('data_ok.xls');
        $resOk->assertDontSee('data_ot.xls');
        $resOk->assertDontSee('data_sk.xls');

        // Filter SK
        $resSk = $this->actingAs($this->evaluator)->get('/dataset?komoditi=SK');
        $resSk->assertStatus(200);
        $resSk->assertSee('data_sk.xls');
        $resSk->assertDontSee('data_ot.xls');
        $resSk->assertDontSee('data_ok.xls');
    }

    public function test_filter_tiga_komoditi_pada_halaman_show_dataset_berpindah_ke_ok(): void
    {
        $datasetOT = Dataset::create([
            'nama_file_asli' => 'data_ot.xls',
            'path_file' => 'private/datasets/data_ot.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 10,
        ]);

        $datasetOK = Dataset::create([
            'nama_file_asli' => 'data_ok.xls',
            'path_file' => 'private/datasets/data_ok.xls',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 12,
        ]);

        // Dari dataset OT, filter ke OK bulan 1 tahun 2026
        $response = $this->actingAs($this->evaluator)->get("/dataset/{$datasetOT->id}?komoditi=OK&tahun=2026&bulan=1");

        $response->assertRedirect(route('dataset.show', ['dataset' => $datasetOK->id]));
    }

    public function test_detail_data_mentah_oba_render_sempurna_setelah_upload_dan_filter_berfungsi(): void
    {
        $content = $this->generateSampleHtmlSipt('Obat Bahan Alam (OBA)', '01 Okt 2026', '31 Okt 2026');
        Storage::disk('private')->put('datasets/test_oba_render.xls', $content);

        $dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_OBA_Oktober_2026.xls',
            'path_file' => 'private/datasets/test_oba_render.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 10,
            'status' => 'Diunggah',
        ]);

        app(DatasetService::class)->process($dataset);

        $response = $this->actingAs($this->evaluator)->get(route('dataset.show', $dataset));

        $response->assertOk();

        // Memastikan tabel data mentah SIPT dan filter UPT/Komoditi muncul dengan benar
        $response->assertSee('Data Mentah Hasil Penarikan SIPT');
        $response->assertSee('Minyak Herbal Alami');
        $response->assertSee('Kapsul Kuat Perkasa');
        $response->assertSee('Obat Bahan Alam (OBA)');
        $response->assertSee('name="upt"', false);
        $response->assertSee('name="komoditi"', false);

        // Uji filter UPT berfungsi
        $responseUpt = $this->actingAs($this->evaluator)->get(route('dataset.show', ['dataset' => $dataset->id, 'upt' => '1']));
        $responseUpt->assertOk();
        $responseUpt->assertSee('Minyak Herbal Alami');
        $responseUpt->assertDontSee('Kapsul Kuat Perkasa');
    }

    /**
     * Memastikan form upload dan seluruh filter komoditi hanya memuat 3 komoditi:
     * Obat Bahan Alam (OBA), Obat Kuasi (OK), dan Suplemen Kesehatan (SK).
     */
    public function test_pilihan_komoditi_hanya_tiga_komoditi_oba_ok_sk(): void
    {
        $responseIndex = $this->actingAs($this->evaluator)->get(route('dataset.index'));
        $responseIndex->assertOk();

        // Cek pada modal upload dan filter di dataset/index
        $responseIndex->assertSee('Obat Bahan Alam (OBA)');
        $responseIndex->assertSee('Obat Kuasi (OK)');
        $responseIndex->assertSee('Suplemen Kesehatan (SK)');
        $responseIndex->assertDontSee('Obat Tradisional (OT)');

        // Cek pada dataset/show
        $dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_OBA_Test.xls',
            'path_file' => 'datasets/test.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 10,
            'status' => 'Diunggah',
        ]);
        $responseShow = $this->actingAs($this->evaluator)->get(route('dataset.show', $dataset));
        $responseShow->assertOk();
        $responseShow->assertSee('Obat Bahan Alam (OBA)');
        $responseShow->assertSee('Obat Kuasi (OK)');
        $responseShow->assertSee('Suplemen Kesehatan (SK)');
        $responseShow->assertDontSee('Obat Tradisional (OT)');

        // Cek pada data-tersimpan
        $responseDataTersimpan = $this->actingAs($this->evaluator)->get(route('data-tersimpan.index'));
        $responseDataTersimpan->assertOk();
        $responseDataTersimpan->assertSee('Obat Bahan Alam (OBA)');
        $responseDataTersimpan->assertSee('Obat Kuasi (OK)');
        $responseDataTersimpan->assertSee('Suplemen Kesehatan (SK)');
        $responseDataTersimpan->assertDontSee('Obat Tradisional (OT)');
    }

    public function test_upload_dataset_dengan_hasil_penilaian_iklan_upt_panjang_berhasil(): void
    {
        $longText = str_repeat('- Iklan memenuhi ketentuan, Obat Tradisional (OT), VISUAL, catatan panjang UPT. ', 5); // > 350 chars

        $customRow = [
            '1', 'Balai POM di Balikpapan', '28 Des 2025', '02 Jan 2026', '31 Des 2025',
            'Obat Tradisional (OT)', 'HERBA ASIMOR', 'TR182519081', 'Membantu melancarkan ASI', 'PT DEXA MEDICA',
            'Lokal', 'Visual', 'MEDIASOSIAL', 'Tiktok', 'Ada (Sesuai)', '-',
            'Herba Asimor pelancar ASI lebih deras', 'Klaim lainnya',
            'Pelaku Usaha', $longText, 'MK', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
        ];

        $htmlContent = $this->generateSampleHtmlSipt('Obat Bahan Alam (OBA)', '01 Jan 2026', '31 Jan 2026', [$customRow]);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('sipt_long_text.xls', $htmlContent);

        $response = $this->actingAs($this->admin)->post('/dataset', [
            'file' => $file,
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $dataset = Dataset::where('nama_file_asli', 'sipt_long_text.xls')->firstOrFail();
        $this->assertEquals('Valid', $dataset->status);

        $dataMentah = \App\Models\DataMentah::where('dataset_id', $dataset->id)->firstOrFail();
        $this->assertEquals(trim($longText), $dataMentah->hasil_penilaian_iklan_upt);
    }

    public function test_admin_melihat_dataset_dikelompokkan_lintas_sesi_evaluator_tanpa_duplikasi(): void
    {
        $admin = User::create([
            'name' => 'Administrator BPOM',
            'username' => 'admin_group_test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $eval1 = $this->evaluator;
        $eval3 = User::create([
            'name' => 'Evaluator BPOM 3',
            'username' => 'evaluator3_test',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);
        $eval4 = User::create([
            'name' => 'Evaluator BPOM 4',
            'username' => 'evaluator4_test',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        foreach ([$eval1, $eval3, $eval4] as $ev) {
            Dataset::create([
                'uploaded_by' => $ev->id,
                'nama_file_asli' => 'SIPT_1 Jan sd 31 Jan 2026_OK.xls',
                'path_file' => 'private/datasets/sipt_ok.xls',
                'komoditi' => 'OK',
                'periode_tahun' => 2026,
                'periode_bulan' => 1,
                'jumlah_baris' => 53,
                'status' => 'Valid',
                'uploaded_at' => now(),
            ]);
        }

        // 1. Admin membuka halaman Dataset: harus dikelompokkan menjadi 1 Berkas & 53 Baris (bukan 3 Berkas & 159 Baris)
        $resAdmin = $this->actingAs($admin)->get(route('dataset.index'));
        $resAdmin->assertOk();
        $stats = $resAdmin->viewData('stats');
        $this->assertEquals(1, $stats['total_dataset']);
        $this->assertEquals(53, $stats['total_baris']);
        $this->assertEquals(1, $stats['total_valid']);

        $datasets = $resAdmin->viewData('datasets');
        $this->assertCount(1, $datasets);
        $resAdmin->assertSee('Januari 2026');
        $resAdmin->assertDontSee('3 Evaluator:');
        $resAdmin->assertDontSee('name="upt"', false);
        $resAdmin->assertDontSee('name="uploaded_by"', false);
        $resAdmin->assertSee(route('dataset.trash'));
        $resAdmin->assertSee('Unggah Dataset SIPT');

        // 2. Masing-masing evaluator tetap melihat sesinya sendiri secara terpisah
        $resEval3 = $this->actingAs($eval3)->get(route('dataset.index'));
        $resEval3->assertOk();
        $this->assertCount(1, $resEval3->viewData('datasets'));
        $resEval3->assertDontSee('3 Evaluator:');
        $resEval3->assertSee('Evaluator BPOM 3');
        $resEval3->assertDontSee(route('dataset.trash'));
    }

    public function test_admin_melihat_dan_memfilter_pekerjaan_evaluator_di_dataset_show(): void
    {
        $admin = User::create([
            'name' => 'Admin BPOM',
            'username' => 'admin_test_show',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $eval1 = User::create([
            'name' => 'Evaluator BPOM 1',
            'username' => 'evaluator1_test',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $eval2 = User::create([
            'name' => 'Evaluator BPOM 2',
            'username' => 'evaluator2_test',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $ds1 = Dataset::create([
            'uploaded_by' => $eval1->id,
            'nama_file_asli' => 'SIPT_Februari_OK.xls',
            'path_file' => 'private/datasets/sipt_feb.xls',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 2,
            'jumlah_baris' => 2,
            'status' => 'Valid',
            'uploaded_at' => now(),
        ]);

        $ds2 = Dataset::create([
            'uploaded_by' => $eval2->id,
            'nama_file_asli' => 'SIPT_Februari_OK.xls',
            'path_file' => 'private/datasets/sipt_feb.xls',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 2,
            'jumlah_baris' => 2,
            'status' => 'Valid',
            'uploaded_at' => now(),
        ]);

        // ds1 memiliki baris 1 dan baris 2
        $row1Ds1 = DataMentah::create([
            'dataset_id' => $ds1->id,
            'nomor_baris' => 1,
            'upt' => 'Balai POM di Serang',
            'nama_produk' => 'Produk A',
            'evaluator_id' => $eval1->id,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);
        $row2Ds1 = DataMentah::create([
            'dataset_id' => $ds1->id,
            'nomor_baris' => 2,
            'upt' => 'Balai POM di Bandung',
            'nama_produk' => 'Produk B',
        ]);

        // ds2 (eval2) mengerjakan baris 2
        $row1Ds2 = DataMentah::create([
            'dataset_id' => $ds2->id,
            'nomor_baris' => 1,
            'upt' => 'Balai POM di Serang',
            'nama_produk' => 'Produk A',
        ]);
        $row2Ds2 = DataMentah::create([
            'dataset_id' => $ds2->id,
            'nomor_baris' => 2,
            'upt' => 'Balai POM di Bandung',
            'nama_produk' => 'Produk B',
            'evaluator_id' => $eval2->id,
            'kualitas_laporan' => 2,
            'ketepatan_waktu' => 2,
        ]);

        // Admin membuka dataset: melihat kedua baris terintegrasi (baris 1 dari eval1, baris 2 dari eval2)
        $res = $this->actingAs($admin)->get(route('dataset.show', $ds1));
        $res->assertOk();
        $res->assertSeeText('1 (Lengkap & Benar)');
        $res->assertSeeText('2 (Tidak Lengkap)');
        $res->assertSeeText('Evaluator BPOM 1');
        $res->assertSeeText('Evaluator BPOM 2');

        // Admin memfilter evaluator 1: hanya baris 1 yang tampil
        $resFilter = $this->actingAs($admin)->get(route('dataset.show', ['dataset' => $ds1->id, 'evaluator_id' => $eval1->id]));
        $resFilter->assertOk();
        $resFilter->assertSee('Produk A');
        $resFilter->assertDontSee('Produk B');
        $resFilter->assertSee('Balai POM di Serang');
    }

    public function test_filter_komoditi_belum_diunggah_tidak_menampilkan_data_dan_tanpa_notif_banner(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_komoditi_test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $datasetOK = Dataset::create([
            'nama_file_asli' => 'SIPT_1 Jan sd 31 Jan 2026_OK.xls',
            'path_file' => 'private/datasets/ok.xls',
            'komoditi' => 'OK',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 1,
            'uploaded_by' => $admin->id,
        ]);

        DataMentah::create([
            'dataset_id' => $datasetOK->id,
            'nomor_baris' => 1,
            'upt' => 'Balai Besar POM di Palu',
            'nama_produk' => 'BALSEM AKTIV KHAS OK',
        ]);

        // Filter komoditi OBA yang belum pernah diunggah
        $response = $this->actingAs($admin)->get(route('dataset.show', [
            'dataset' => $datasetOK->id,
            'komoditi' => 'OBA',
        ]));

        $response->assertOk();
        // 1. Tidak menampilkan data mentah dari OK
        $response->assertDontSee('BALSEM AKTIV KHAS OK');
        // 2. Tidak menampilkan notifikasi banner kuning
        $response->assertDontSee('Belum ditemukan file dataset untuk komoditi');
        $response->assertDontSee('Unggah Dataset Baru');
        // 3. Menampilkan indikasi 0 baris dan pesan tabel kosong
        $response->assertSee('0 Baris');
        $response->assertSee('Belum ada dataset yang diunggah untuk komoditi Obat Bahan Alam (OBA) pada periode ini.');
    }

    public function test_command_cleanup_storage_mendeteksi_dan_menghapus_file_yatim_piatu(): void
    {
        $dir = storage_path('app/private/datasets');
        if (! \Illuminate\Support\Facades\File::isDirectory($dir)) {
            \Illuminate\Support\Facades\File::makeDirectory($dir, 0777, true);
        }

        $activePath = $dir . '/test_aktif.xlsx';
        $orphanPath = $dir . '/test_yatim_piatu.xlsx';

        file_put_contents($activePath, 'konten dataset aktif');
        file_put_contents($orphanPath, 'konten dataset yatim piatu');

        // Buat dataset aktif di database
        Dataset::create([
            'nama_file_asli' => 'test_aktif.xlsx',
            'path_file' => 'private/datasets/test_aktif.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
        ]);

        $this->assertFileExists($activePath);
        $this->assertFileExists($orphanPath);

        // Jalankan perintah cleanup dengan --force
        $this->artisan('dataset:cleanup-storage', ['--force' => true])
            ->expectsOutputToContain('Ditemukan 1 berkas yang tidak memiliki relasi/catatan aktif di database')
            ->expectsOutputToContain('Berhasil membersihkan 1 berkas yatim piatu')
            ->assertSuccessful();

        // File aktif tetap ada, file orphan terhapus
        $this->assertFileExists($activePath);
        $this->assertFileDoesNotExist($orphanPath);

        // Bersihkan file aktif tes
        if (file_exists($activePath)) {
            @unlink($activePath);
        }
    }
}
