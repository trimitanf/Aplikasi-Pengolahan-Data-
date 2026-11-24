<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\TargetIkuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataTersimpanTest extends TestCase
{
    use RefreshDatabase;

    protected User $evaluator;

    protected Dataset $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterKodeSeeder::class);
        $this->seed(TargetIkuSeeder::class);

        $this->evaluator = User::create([
            'name' => 'Evaluator Tester',
            'username' => 'evaluator_test',
            'password' => bcrypt('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_TEST.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Final',
            'path_file' => 'datasets/test.xlsx',
            'jumlah_baris' => 3,
            'uploaded_at' => now(),
            'finalized_at' => now(),
        ]);

        $dm1 = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 1,
            'no' => '1',
            'nama_produk' => 'Produk MK',
        ]);

        $dm2 = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 2,
            'no' => '2',
            'nama_produk' => 'Produk Klaim Berlebihan',
        ]);

        $dm3 = DataMentah::create([
            'dataset_id' => $this->dataset->id,
            'nomor_baris' => 3,
            'no' => '3',
            'nama_produk' => 'Produk TIE',
        ]);

        // MK record
        DataIklan::create([
            'dataset_id' => $this->dataset->id,
            'data_mentah_id' => $dm1->id,
            'no' => 1,
            'upt' => 1,
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 1,
            'asal_produk' => 1, // Lokal
            'kategori_media' => 2, // Visual
            'kategori_iklan' => 1, // E-Commerce
            'pembuat_iklan' => 1, // Pelaku Usaha
            'hasil_evaluasi' => 1,
            'is_final' => true,
        ]);

        // TMK Klaim Berlebihan (code 3)
        DataIklan::create([
            'dataset_id' => $this->dataset->id,
            'data_mentah_id' => $dm2->id,
            'no' => 2,
            'upt' => 1,
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 1,
            'asal_produk' => 1, // Lokal
            'kategori_media' => 3, // Audiovisual
            'kategori_iklan' => 3, // Media Sosial
            'pembuat_iklan' => 1, // Pelaku Usaha
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 3,
            'is_final' => true,
        ]);

        // TMK TIE (code 7)
        DataIklan::create([
            'dataset_id' => $this->dataset->id,
            'data_mentah_id' => $dm3->id,
            'no' => 3,
            'upt' => 1,
            'periode_bulan' => 1,
            'periode_tahun' => 2026,
            'jenis_produk' => 1,
            'asal_produk' => 2, // Impor
            'kategori_media' => 2, // Visual
            'kategori_iklan' => 2, // Website
            'pembuat_iklan' => 2, // Perseorangan
            'hasil_evaluasi' => 2,
            'rincian_tmk' => 7,
            'is_final' => true,
        ]);
    }

    public function test_can_filter_data_tersimpan_by_pelanggaran_klaim_berlebihan(): void
    {
        $response = $this->actingAs($this->evaluator)
            ->get(route('data-tersimpan.index', [
                'tahun' => 2026,
                'pelanggaran' => 'klaim_berlebihan',
            ]));

        $response->assertOk();
        $response->assertSee('Menampilkan Baris Terindikasi TMK');
        $response->assertSee('Mencantumkan Klaim Berlebihan');
        // Total data should be 1
        $this->assertEquals(1, $response->viewData('totalFinal'));
        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals(2, $items->first()->no);
        $this->assertEquals(3, $items->first()->rincian_tmk);
    }

    public function test_can_filter_data_tersimpan_by_pelanggaran_tie(): void
    {
        $response = $this->actingAs($this->evaluator)
            ->get(route('data-tersimpan.index', [
                'tahun' => 2026,
                'pelanggaran' => 'tie',
            ]));

        $response->assertOk();
        $response->assertSee('Produk Tidak Terdaftar (TIE)');
        $this->assertEquals(1, $response->viewData('totalFinal'));
        $items = $response->viewData('items');
        $this->assertEquals(3, $items->first()->no);
        $this->assertEquals(7, $items->first()->rincian_tmk);
    }

    public function test_can_filter_data_tersimpan_by_dimension_and_highlight_column(): void
    {
        // Klik bagian TMK (Merah, hasil_evaluasi=2) pada Asal Produk "Lokal" (asal_produk=1)
        $response = $this->actingAs($this->evaluator)
            ->get(route('data-tersimpan.index', [
                'tahun' => 2026,
                'hasil_evaluasi' => 2,
                'asal_produk' => 1,
                'highlight_col' => 'asal_produk',
            ]));

        $response->assertOk();
        $response->assertSee('Asal Produk: Lokal');
        $response->assertSee('Khusus Tidak Memenuhi Ketentuan / TMK');
        $response->assertSee('id="th-col-asal_produk"', false);
        $response->assertSee('th-col-asal_produk', false);

        // Hanya record #2 (Lokal & TMK)
        $this->assertEquals(1, $response->viewData('totalFinal'));
        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals(2, $items->first()->no);
        $this->assertEquals(1, $items->first()->asal_produk);
        $this->assertEquals(2, $items->first()->hasil_evaluasi);
    }

    public function test_can_filter_data_tersimpan_by_dimension_mk(): void
    {
        // Klik bagian MK (Hijau, hasil_evaluasi=1) pada Asal Produk "Lokal" (asal_produk=1)
        $response = $this->actingAs($this->evaluator)
            ->get(route('data-tersimpan.index', [
                'tahun' => 2026,
                'hasil_evaluasi' => 1,
                'asal_produk' => 1,
                'highlight_col' => 'asal_produk',
            ]));

        $response->assertOk();
        $response->assertSee('Asal Produk: Lokal');
        $response->assertSee('Khusus Memenuhi Ketentuan / MK');

        // Hanya record #1 (Lokal & MK)
        $this->assertEquals(1, $response->viewData('totalFinal'));
        $items = $response->viewData('items');
        $this->assertCount(1, $items);
        $this->assertEquals(1, $items->first()->no);
        $this->assertEquals(1, $items->first()->asal_produk);
        $this->assertEquals(1, $items->first()->hasil_evaluasi);
    }

    public function test_can_export_filtered_pelanggaran_data(): void
    {
        $response = $this->actingAs($this->evaluator)
            ->get(route('data-tersimpan.export', [
                'tahun' => 2026,
                'pelanggaran' => 'klaim_berlebihan',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('Data_Standardisasi_2026_1-12_klaim_berlebihan.xlsx', $response->headers->get('content-disposition'));
    }
}
