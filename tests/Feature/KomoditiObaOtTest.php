<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\DatasetService;
use App\Services\RekapService;
use App\Services\StandardizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KomoditiObaOtTest extends TestCase
{
    use RefreshDatabase;

    protected User $evaluator;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->evaluator = User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator_oba',
            'password' => bcrypt('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin BPOM',
            'username' => 'admin_oba',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Storage::fake('private');
    }

    protected function generateSampleHtml(string $headerKomoditi, array $rows): string
    {
        $rowsHtml = '';
        foreach ($rows as $r) {
            $tds = array_map(fn ($val) => "<td>{$val}</td>", $r);
            $rowsHtml .= '<tr>' . implode('', $tds) . '</tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
    <table>
        <tr><td>KOMODITI</td><td>:</td><td>{$headerKomoditi}</td></tr>
        <tr><td>PERIODE MULAI</td><td>:</td><td>01 Jan 2026</td></tr>
        <tr><td>PERIODE AKHIR</td><td>:</td><td>31 Jan 2026</td></tr>
    </table>
    <table>
        <tr>
            <th>NO</th><th>UPT</th><th>TANGGAL PENGAWASAN</th><th>TANGGAL EVALUASI UPT</th><th>TANGGAL EVALUASI PUSAT</th>
            <th>JENIS PRODUK</th><th>NAMA PRODUK</th><th>NOMOR IZIN EDAR</th><th>KLAIM PRODUK</th><th>PENDAFTAR</th>
            <th>ASAL PRODUK</th><th>KATEGORI MEDIA</th><th>MEDIA IKLAN</th><th>TEMPAT PENGAMBILAN IKLAN</th><th>PENCANTUMAN NOMOR IZIN EDAR</th>
            <th>LINK URL IKLAN</th><th>KLAIM DALAM PROMOSI/IKLAN</th><th>KLASIFIKASI KLAIM</th><th>PEMBUAT IKLAN</th><th>HASIL PENILAIAN IKLAN UPT</th>
            <th>KESIMPULAN UPT</th><th>KESIMPULAN PUSAT</th><th>KESESUAIAN HASIL EVALUASI</th><th>KATEGORI TMK</th><th>RINCIAN TMK</th><th>TINDAK LANJUT</th>
        </tr>
        {$rowsHtml}
    </table>
</body>
</html>
HTML;
    }

    public function test_dataset_oba_menerima_baris_jenis_produk_obat_tradisional_tanpa_warning(): void
    {
        $rows = [
            [
                '1', 'Balai Besar POM di Banda Aceh', '10 Jan 2026', '12 Jan 2026', '15 Jan 2026',
                'Obat Tradisional (OT)', 'Minyak Gosok Alami', 'TR123456789', 'Meredakan pegal', 'PT Sehat',
                'Lokal', 'Visual', 'E-Commerce', 'Shopee', 'Ada',
                'https://shopee.co.id/item1', 'Klaim alami', 'Membantu memelihara kesehatan tubuh, membantu memelihara daya tahan tubuh, membantu menghangatkan badan, membantu menyegarkan badan',
                'Pelaku Usaha', 'MK', 'Memenuhi Ketentuan', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
            ],
            [
                '2', 'Balai Besar POM di Banda Aceh', '10 Jan 2026', '12 Jan 2026', '15 Jan 2026',
                'OBAT TRADISIONAL', 'Kapsul Kunyit', 'TR987654321', 'Meredakan radang', 'PT Herbal',
                'Lokal', 'Visual', 'E-Commerce', 'Tokopedia', 'Ada',
                'https://tokopedia.co.id/item2', 'Klaim jamu', 'Membantu memelihara kesehatan tubuh, membantu memelihara daya tahan tubuh, membantu menghangatkan badan, membantu menyegarkan badan',
                'Pelaku Usaha', 'MK', 'Memenuhi Ketentuan', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
            ],
            [
                '3', 'Balai Besar POM di Banda Aceh', '10 Jan 2026', '12 Jan 2026', '15 Jan 2026',
                'OT', 'Teh Celup Herbal', 'TR112233445', 'Kebugaran', 'PT Asli',
                'Lokal', 'Visual', 'E-Commerce', 'Lazada', 'Ada',
                'https://lazada.co.id/item3', 'Klaim teh', 'Membantu memelihara kesehatan tubuh, membantu memelihara daya tahan tubuh, membantu menghangatkan badan, membantu menyegarkan badan',
                'Pelaku Usaha', 'MK', 'Memenuhi Ketentuan', 'Memenuhi Ketentuan', 'Sesuai', '-', '-', '-',
            ],
        ];

        $html = $this->generateSampleHtml('Obat Tradisional (OT)', $rows);
        $file = UploadedFile::fake()->createWithContent('sipt_oba_test.xls', $html);

        $response = $this->actingAs($this->admin)->post(route('dataset.store'), [
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'file' => $file,
        ]);

        $response->assertRedirect();

        $dataset = Dataset::where('nama_file_asli', 'sipt_oba_test.xls')->firstOrFail();
        $this->assertEquals('OBA', $dataset->komoditi);
        $this->assertEquals('Valid', $dataset->status);
        $this->assertEquals(3, $dataset->jumlah_baris);

        // Pastikan tidak ada warning terkait jenis produk
        $jenisProdukWarnings = $dataset->issues()->where('kolom', 'JENIS PRODUK')->count();
        $this->assertEquals(0, $jenisProdukWarnings, 'Tidak boleh ada warning keselarasan jenis produk untuk Obat Tradisional (OT) dalam komoditi OBA');

        // Jalankan standardisasi dan verifikasi jenis_produk menjadi kode 1 (OBA)
        app(StandardizationService::class)->standardizeDataset($dataset);

        $items = DataIklan::where('dataset_id', $dataset->id)->get();
        $this->assertCount(3, $items);
        foreach ($items as $item) {
            $this->assertEquals(1, $item->jenis_produk, 'Baris Obat Tradisional harus distandarisasi ke kode 1');
            $this->assertEquals('terstandar', $item->status_standardisasi);
        }
    }

    public function test_perhitungan_rekapitulasi_oba_menggabungkan_kode_1_dan_kode_3(): void
    {
        $dataset = Dataset::create([
            'uploaded_by' => $this->evaluator->id,
            'nama_file_asli' => 'dataset_rekap_test.xls',
            'path_file' => 'private/datasets/dataset_rekap_test.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 3,
            'status' => 'Final',
            'jumlah_baris' => 2,
        ]);

        $mentah1 = DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 1,
            'no' => '1',
            'upt' => 'Balai Besar POM di Banda Aceh',
            'jenis_produk' => 'Obat Tradisional (OT)',
            'evaluator_id' => $this->evaluator->id,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        $mentah2 = DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 2,
            'no' => '2',
            'upt' => 'Balai Besar POM di Banda Aceh',
            'jenis_produk' => 'Obat Kuasi (OK)',
            'evaluator_id' => $this->evaluator->id,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        // Baris 1: OBA / OT (jenis_produk = 1, hasil_evaluasi = 1 / MK)
        DataIklan::create([
            'dataset_id' => $dataset->id,
            'data_mentah_id' => $mentah1->id,
            'no' => 1,
            'upt' => 1,
            'periode_bulan' => 3,
            'periode_tahun' => 2026,
            'jenis_produk' => 1,
            'asal_produk' => 1,
            'klaim' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 1,
            'nama_media' => 1,
            'pencantuman_nie' => 1,
            'kesesuaian_nie' => 1,
            'hasil_evaluasi' => 1, // MK
            'pembuat_iklan' => 1,
            'status_standardisasi' => 'terstandar',
            'is_final' => true,
        ]);

        // Baris 2: OK (jenis_produk = 3, hasil_evaluasi = 2 / TMK)
        DataIklan::create([
            'dataset_id' => $dataset->id,
            'data_mentah_id' => $mentah2->id,
            'no' => 2,
            'upt' => 1,
            'periode_bulan' => 3,
            'periode_tahun' => 2026,
            'jenis_produk' => 3,
            'asal_produk' => 1,
            'klaim' => 1,
            'kategori_media' => 2,
            'kategori_iklan' => 1,
            'nama_media' => 1,
            'pencantuman_nie' => 1,
            'kesesuaian_nie' => 1,
            'hasil_evaluasi' => 2, // TMK
            'rincian_tmk' => 1,
            'jenis_tmk' => 1,
            'pembuat_iklan' => 1,
            'status_standardisasi' => 'terstandar',
            'is_final' => true,
        ]);

        $rekapService = app(RekapService::class);
        $rekap = $rekapService->calculate(2026, 1, 12);

        // Tabel OBA harus menggabungkan baris jenis_produk 1 dan 3
        $this->assertEquals(2, $rekap['oba']['total_pengawasan']['total']);
        $this->assertEquals(1, $rekap['oba']['total_pengawasan']['mk']);
        $this->assertEquals(1, $rekap['oba']['total_pengawasan']['tmk']);
        $this->assertEquals(50.0, $rekap['oba']['total_pengawasan']['persen_mk']);
        $this->assertEquals(50.0, $rekap['oba']['total_pengawasan']['persen_tmk']);
    }
}
