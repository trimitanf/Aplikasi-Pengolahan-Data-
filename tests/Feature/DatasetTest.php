<?php

namespace Tests\Feature;

use App\Jobs\ProsesDatasetJob;
use App\Models\Dataset;
use App\Models\User;
use App\Services\DatasetService;
use App\Services\SiptDatasetReader;
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
                $rowsHtml .= '<td>' . htmlspecialchars($cell) . '</td>';
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
        $response = $this->actingAs($this->evaluator)->get('/dataset');

        $response->assertStatus(200);
        $response->assertSee('Unggah Dataset SIPT');
        $response->assertSee('Riwayat Dataset Terdaftar');
    }

    public function test_upload_file_sipt_berhasil_dan_membuat_record_dataset(): void
    {
        Queue::fake();

        $content = $this->generateSampleHtmlSipt();
        $file = UploadedFile::fake()->createWithContent('sipt_januari_2026.xls', $content);

        $response = $this->actingAs($this->evaluator)->post('/dataset', [
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
        $file = UploadedFile::fake()->createWithContent('new_attempt.xls', $content);

        $response = $this->actingAs($this->evaluator)->post('/dataset', [
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('komoditi');
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

    public function test_proses_dataset_mendeteksi_warning_tanggal_luar_periode_dan_baris_identik(): void
    {
        // Baris 1: tanggal 15 Feb 2026 (di luar bulan Januari) -> warning
        // Baris 2 & 3: identik -> warning baris identik
        $customRows = [
            [
                '1', 'Balai Besar POM di Banda Aceh', '15 Feb 2026', '18 Feb 2026', '20 Feb 2026',
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

    public function test_dataset_yang_belum_final_dapat_dihapus(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'delete_me.xls',
            'path_file' => 'private/datasets/delete_me.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
        ]);

        $response = $this->actingAs($this->evaluator)->delete("/dataset/{$dataset->id}");

        $response->assertRedirect('/dataset');
        $this->assertDatabaseMissing('datasets', ['id' => $dataset->id]);
    }

    public function test_dataset_yang_sudah_final_tidak_bisa_dihapus(): void
    {
        $dataset = Dataset::create([
            'nama_file_asli' => 'final_locked.xls',
            'path_file' => 'private/datasets/final_locked.xls',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
        ]);

        $response = $this->actingAs($this->evaluator)->delete("/dataset/{$dataset->id}");

        $response->assertRedirect('/dataset');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('datasets', ['id' => $dataset->id]);
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
        $response->assertSee('name="tahun"', false);
        $response->assertSee('name="bulan"', false);
        $response->assertSee('name="cari"', false);
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
}

