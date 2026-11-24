<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\StandardizationService;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\PemetaanNilaiSeeder;
use Database\Seeders\TargetIkuSeeder;
use Database\Seeders\UptSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkemaBEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected array $evaluators = [];
    protected Dataset $dataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            UserSeeder::class,
            UptSeeder::class,
            MasterKodeSeeder::class,
            PemetaanNilaiSeeder::class,
            TargetIkuSeeder::class,
        ]);

        $this->admin = User::where('username', 'admin')->first();
        $this->evaluators = User::where('role', 'evaluator')->orderBy('id', 'asc')->get()->all();

        // Buat 1 dataset milik Evaluator 1
        $this->dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_OT_OKTOBER_2026.xlsx',
            'path_file' => 'private/datasets/test.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 10,
            'jumlah_baris' => 6,
            'jumlah_menunggu_evaluasi' => 0,
            'status' => 'Valid',
            'uploaded_by' => $this->evaluators[0]->id,
            'uploaded_at' => now(),
        ]);

        for ($i = 1; $i <= 6; $i++) {
            DataMentah::create([
                'dataset_id' => $this->dataset->id,
                'evaluator_id' => $this->evaluators[0]->id,
                'nomor_baris' => $i,
                'no' => $i,
                'upt' => 'Balai Besar POM di Jakarta',
                'nama_produk' => "Produk Herbal {$i}",
                'nomor_izin_edar' => "TR2123456{$i}",
                'media_iklan' => 'SHOPEE',
                'hasil_penilaian_iklan_upt' => 'MK',
                'kesimpulan_upt' => 'MEMENUHI KETENTUAN',
                'kesimpulan_pusat' => 'MEMENUHI KETENTUAN',
            ]);
        }
    }

    public function test_seluruh_baris_dataset_milik_evaluator_pengunggah(): void
    {
        $evaluator1 = $this->evaluators[0];

        $this->assertEquals($evaluator1->id, $this->dataset->uploaded_by);
        $this->assertEquals($evaluator1->name, $this->dataset->uploader->name);

        $rowCount = DataMentah::where('dataset_id', $this->dataset->id)
            ->where('evaluator_id', $evaluator1->id)
            ->count();

        $this->assertEquals(6, $rowCount);
    }

    public function test_semua_pengguna_dapat_melihat_tetapi_hanya_pemilik_dan_admin_dapat_mengedit_sel_manual(): void
    {
        $owner = $this->evaluators[0];
        $otherEvaluator = $this->evaluators[1];
        $row = DataMentah::where('dataset_id', $this->dataset->id)->first();

        // 1. Evaluator lain dapat melihat halaman (200 OK)
        $responseView = $this->actingAs($otherEvaluator)->get("/dataset/{$this->dataset->id}");
        $responseView->assertStatus(200);
        $responseView->assertSee('Mode Lihat Saja');

        // 2. Evaluator lain mencoba mengubah sel manual -> Ditolak (403 Forbidden)
        $responseForbidden = $this->actingAs($otherEvaluator)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]
        );
        $responseForbidden->assertStatus(403);

        // 3. Pemilik dataset berhasil mengubah sel manual (200 OK)
        $responseOwner = $this->actingAs($owner)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]
        );
        $responseOwner->assertStatus(200);
        $responseOwner->assertJson(['success' => true]);

        // 4. Admin berhasil mengubah sel manual (200 OK)
        $responseAdmin = $this->actingAs($this->admin)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            [
                'kolom' => 'kualitas_laporan',
                'nilai' => 1,
            ]
        );
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertJson(['success' => true]);
    }

    public function test_evaluator_lain_tidak_dapat_menghapus_atau_menjalankan_aksi_dataset(): void
    {
        $otherEvaluator = $this->evaluators[1];

        // Coba hapus dataset (redirect dengan status error)
        $responseDelete = $this->actingAs($otherEvaluator)->delete("/dataset/{$this->dataset->id}");
        $responseDelete->assertRedirect();
        $responseDelete->assertSessionHas('error');

        // Coba konversi ulang (redirect dengan status error)
        $responseKonversi = $this->actingAs($otherEvaluator)->post("/dataset/{$this->dataset->id}/konversi/ulang");
        $responseKonversi->assertRedirect();
        $responseKonversi->assertSessionHas('error');

        // Coba simpan final (redirect dengan status error)
        $responseFinal = $this->actingAs($otherEvaluator)->post("/dataset/{$this->dataset->id}/simpan-final");
        $responseFinal->assertRedirect();
        $responseFinal->assertSessionHas('error');
    }

    public function test_admin_dapat_mengalihkan_kepemilikan_dataset_ke_evaluator_lain(): void
    {
        $evaluator1 = $this->evaluators[0];
        $evaluator2 = $this->evaluators[1];

        $response = $this->actingAs($this->admin)->post("/dataset/{$this->dataset->id}/transfer-ownership", [
            'new_owner_id' => $evaluator2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->dataset->refresh();
        $this->assertEquals($evaluator2->id, $this->dataset->uploaded_by);

        // Seluruh baris data mentah juga harus dialihkan ke evaluator 2
        $countEval2 = DataMentah::where('dataset_id', $this->dataset->id)
            ->where('evaluator_id', $evaluator2->id)
            ->count();
        $this->assertEquals(6, $countEval2);

        // Sekarang Evaluator 2 berwenang mengedit
        $row = DataMentah::where('dataset_id', $this->dataset->id)->first();
        $responseNewOwner = $this->actingAs($evaluator2)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]
        );
        $responseNewOwner->assertStatus(200);
    }

    public function test_progres_pemilik_dataset_terhitung_dengan_akurat(): void
    {
        $row1 = DataMentah::where('dataset_id', $this->dataset->id)->first();
        $row1->update([
            'kesesuaian_evaluasi' => 1,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        $stats = $this->dataset->progressStats();

        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(5, $stats['pending']);
        $this->assertEquals(16.7, $stats['percentage']);
        $this->assertFalse($stats['is_complete']);
    }

    public function test_banyak_evaluator_dapat_memfinalkan_dataset_pada_periode_yang_sama(): void
    {
        // Buat dataset kedua pada periode yang sama (Oktober 2026, OT) oleh Evaluator 2
        $dataset2 = Dataset::create([
            'nama_file_asli' => 'SIPT_OT_OKTOBER_2026_EV2.xlsx',
            'path_file' => 'private/datasets/test2.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 10,
            'jumlah_baris' => 2,
            'jumlah_menunggu_evaluasi' => 0,
            'status' => 'Valid',
            'uploaded_by' => $this->evaluators[1]->id,
            'uploaded_at' => now(),
        ]);

        // Buat data_iklan terstandarisasi untuk dataset 1 (lengkap)
        foreach ($this->dataset->dataMentah as $raw) {
            $raw->update([
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 1,
            ]);

            DataIklan::create([
                'dataset_id' => $this->dataset->id,
                'data_mentah_id' => $raw->id,
                'evaluator_id' => $raw->evaluator_id,
                'no' => null,
                'upt' => 1,
                'periode_bulan' => 10,
                'periode_tahun' => 2026,
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
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 1,
                'status_standardisasi' => 'terstandar',
                'is_final' => false,
            ]);
        }

        // Finalkan dataset 1
        $standardizationService = app(StandardizationService::class);
        $standardizationService->finalizeDataset($this->dataset);

        $this->dataset->refresh();
        $this->assertEquals('Final', $this->dataset->status);
        $this->assertEquals(6, DataIklan::where('dataset_id', $this->dataset->id)->where('is_final', true)->count());

        // Sekarang siapkan dataset 2 dengan 2 baris lengkap
        for ($j = 1; $j <= 2; $j++) {
            $raw2 = DataMentah::create([
                'dataset_id' => $dataset2->id,
                'evaluator_id' => $this->evaluators[1]->id,
                'nomor_baris' => $j,
                'no' => $j,
                'upt' => 'Balai Besar POM di Jakarta',
                'nama_produk' => "Produk Herbal EV2 {$j}",
                'nomor_izin_edar' => "TR2123456{$j}",
                'media_iklan' => 'SHOPEE',
                'hasil_penilaian_iklan_upt' => 'MK',
                'kesimpulan_upt' => 'MEMENUHI KETENTUAN',
                'kesimpulan_pusat' => 'MEMENUHI KETENTUAN',
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 1,
            ]);

            DataIklan::create([
                'dataset_id' => $dataset2->id,
                'data_mentah_id' => $raw2->id,
                'evaluator_id' => $raw2->evaluator_id,
                'no' => null,
                'upt' => 1,
                'periode_bulan' => 10,
                'periode_tahun' => 2026,
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
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 1,
                'status_standardisasi' => 'terstandar',
                'is_final' => false,
            ]);
        }

        // Finalkan dataset 2 (sebelumnya akan gagal jika hanya 1 final per bulan)
        $standardizationService->finalizeDataset($dataset2);

        $dataset2->refresh();
        $this->assertEquals('Final', $dataset2->status);

        // Verifikasi penomoran NO berlanjut dari 6 ke 7 dan 8
        $noValues = DataIklan::where('dataset_id', $dataset2->id)->orderBy('id')->pluck('no')->all();
        $this->assertEquals([7, 8], $noValues);
    }

    public function test_endpoint_sinkronisasi_versi_berjalan_cepat(): void
    {
        $response = $this->actingAs($this->evaluators[0])->getJson('/sync/versi');
        $response->assertStatus(200);
        $response->assertJsonStructure(['versi', 'datasets_count', 'timestamp']);
    }

    /**
     * Memastikan evaluator dapat mengubah penugasan evaluator per baris via dropdown (inline),
     * dan evaluator yang ditugaskan dapat mengedit 3 kolom manual evaluasi pada baris tersebut.
     */
    public function test_evaluator_dapat_mengubah_penugasan_evaluator_per_baris(): void
    {
        $evaluator1 = $this->evaluators[0];
        $evaluator2 = $this->evaluators[1];
        $row = DataMentah::where('dataset_id', $this->dataset->id)->first();
        $row->update(['evaluator_id' => null]);

        // 1. Evaluator 2 memilih baris ini untuk ditugaskan kepada dirinya sendiri
        $responseAssign = $this->actingAs($evaluator2)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            [
                'kolom' => 'evaluator_id',
                'nilai' => $evaluator2->id,
            ]
        );

        $responseAssign->assertOk();
        $responseAssign->assertJson([
            'success' => true,
            'evaluator_id' => $evaluator2->id,
            'evaluator_name' => $evaluator2->name,
        ]);

        $row->refresh();
        $this->assertEquals($evaluator2->id, $row->evaluator_id);

        // 2. Sekarang Evaluator 2 berhak mengedit kolom evaluasi manual pada baris ini
        $responseEdit = $this->actingAs($evaluator2)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 1,
            ]
        );
        $responseEdit->assertOk();
        $responseEdit->assertJson(['success' => true]);

        // 3. Evaluator 1 mencoba mengedit baris yang sudah ditugaskan ke Evaluator 2 -> Ditolak (403)
        $responseOther = $this->actingAs($evaluator1)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 2,
            ]
        );
        $responseOther->assertStatus(403);
    }

    /**
     * Memastikan fitur penugasan massal UPT (bulk assign UPT) dapat menugaskan seluruh baris pada UPT tertentu ke evaluator.
     */
    public function test_penugasan_massal_berdasarkan_upt(): void
    {
        $evaluator2 = $this->evaluators[1];

        // Jalankan penugasan massal UPT ke Evaluator 2 oleh Admin
        $response = $this->actingAs($this->admin)->post(route('dataset.assign-upt', $this->dataset), [
            'upt' => 'Balai Besar POM di Jakarta',
            'evaluator_id' => $evaluator2->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Seluruh 6 baris UPT Balai Besar POM di Jakarta harus terisi Evaluator 2
        $countAssigned = DataMentah::where('dataset_id', $this->dataset->id)
            ->where('evaluator_id', $evaluator2->id)
            ->count();
        $this->assertEquals(6, $countAssigned);
    }

    /**
     * Memastikan laman detail data mentah menampilkan panel penugasan UPT massal saat UPT difilter,
     * serta kolom EVALUATOR berupa dropdown.
     */
    public function test_tampilan_penugasan_upt_massal_dan_dropdown_kolom_evaluator(): void
    {
        $evaluator = $this->evaluators[0];

        // 1. Tampilan Admin memuat dropdown evaluator di tabel baris
        $responseAdmin = $this->actingAs($this->admin)->get(route('dataset.show', $this->dataset));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('name="evaluator_id"', false);
        $responseAdmin->assertSee('saveEvaluatorCell');

        // Evaluator melihat tampilan tanpa dropdown pengubahan evaluator
        $response = $this->actingAs($evaluator)->get(route('dataset.show', $this->dataset));
        $response->assertOk();
        $response->assertDontSee('name="evaluator_id"', false);

        // 2. Tampilan dengan filter UPT memuat informasi UPT terkait
        $responseFiltered = $this->actingAs($evaluator)->get(route('dataset.show', [
            'dataset' => $this->dataset,
            'upt' => 'Balai Besar POM di Jakarta',
        ]));
        $responseFiltered->assertOk();
        $responseFiltered->assertSee('Balai Besar POM di Jakarta');
    }

    public function test_keamanan_transfer_ownership_dan_baris_tanpa_evaluator_serta_kunci_dataset_final(): void
    {
        $owner = $this->evaluators[0];
        $otherEvaluator = $this->evaluators[1];

        // 1. Evaluator non-admin ditolak saat mencoba mengalihkan kepemilikan dataset (403)
        $resTransfer = $this->actingAs($otherEvaluator)->post("/dataset/{$this->dataset->id}/transfer-ownership", [
            'new_owner_id' => $otherEvaluator->id,
        ]);
        $resTransfer->assertStatus(403);

        // 2. Jika baris memiliki evaluator_id = null namun dataset dimiliki Evaluator 1, Evaluator 2 tetap ditolak (403)
        $row = DataMentah::where('dataset_id', $this->dataset->id)->first();
        $row->update(['evaluator_id' => null]);

        $resEditOther = $this->actingAs($otherEvaluator)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            ['kolom' => 'kesesuaian_evaluasi', 'nilai' => 1]
        );
        $resEditOther->assertStatus(403);

        // Namun pemilik dataset (Evaluator 1) tetap diizinkan (200)
        $resEditOwner = $this->actingAs($owner)->postJson(
            "/dataset/{$this->dataset->id}/data-mentah/manual-cell/{$row->id}",
            ['kolom' => 'kesesuaian_evaluasi', 'nilai' => 1]
        );
        $resEditOwner->assertOk();

        // 3. Jika dataset berstatus Final, pengalihan kepemilikan (transfer-ownership) dikunci
        $this->dataset->update(['status' => 'Final']);
        $resFinalTransfer = $this->actingAs($this->admin)->post("/dataset/{$this->dataset->id}/transfer-ownership", [
            'new_owner_id' => $otherEvaluator->id,
        ]);
        $resFinalTransfer->assertRedirect();
        $resFinalTransfer->assertSessionHas('error');
    }
}
