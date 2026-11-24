<?php

namespace Tests\Feature;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use App\Services\StandardizationService;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\TargetIkuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KolomWaktuRealtimeTest extends TestCase
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
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator_bpom',
            'password' => bcrypt('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->dataset = Dataset::create([
            'nama_file_asli' => 'SIPT_OT_Realtime_2026.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 7,
            'status' => 'Terstandardisasi',
            'path_file' => 'datasets/test_realtime.xlsx',
            'jumlah_baris' => 2,
            'uploaded_by' => $this->evaluator->id,
            'uploaded_at' => now(),
        ]);

        for ($i = 1; $i <= 2; $i++) {
            $raw = DataMentah::create([
                'dataset_id' => $this->dataset->id,
                'nomor_baris' => $i,
                'no' => (string) $i,
                'upt' => 'Balai Besar POM di Banda Aceh',
                'jenis_produk' => 'Obat Tradisional',
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 1,
            ]);

            DataIklan::create([
                'dataset_id' => $this->dataset->id,
                'data_mentah_id' => $raw->id,
                'no' => $i,
                'periode_tahun' => 2026,
                'periode_bulan' => 7,
                'upt' => 1,
                'jenis_produk' => 1,
                'asal_produk' => 1,
                'klaim' => 1,
                'kategori_media' => 2,
                'kategori_iklan' => 1,
                'nama_media' => 1,
                'pencantuman_nie' => 1,
                'kesesuaian_nie' => 1,
                'hasil_evaluasi' => 1,
                'pembuat_iklan' => 1,
                'kesesuaian_evaluasi' => 1,
                'kualitas_laporan' => 1,
                'ketepatan_waktu' => 1,
                'evaluator_id' => $this->evaluator->id,
                'is_final' => false,
            ]);
        }
    }

    /**
     * Memastikan login evaluator mencatat waktu login realtime ke sistem.
     */
    public function test_login_evaluator_merekam_waktu_login_realtime(): void
    {
        $response = $this->post(route('login.post'), [
            'username' => 'evaluator_bpom',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->evaluator->refresh();
        $this->assertNotNull($this->evaluator->last_login_at);
        $this->assertTrue(session()->has('login_time'));
    }

    /**
     * Memastikan laman Dataset menampilkan waktu di header sistem tanpa teks 'WAKTU LOGIN SISTEM',
     * memiliki tanda hijau kedap-kedip tanda aktif, dan 1 kolom Waktu pada tabel.
     */
    public function test_laman_dataset_menampilkan_kolom_waktu_tunggal(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('dataset.index'));

        $response->assertOk();
        $response->assertDontSee('Waktu Evaluator Realtime:');
        $response->assertDontSee('Waktu Login Sistem :');
        $response->assertDontSee('WAKTU LOGIN SISTEM');
        $response->assertSee('animate-ping-dot');
        $response->assertSee('bg-emerald-500');
        $response->assertSee('Waktu');

        $now = now();
        $response->assertSee($now->translatedFormat('d F Y'));
        $response->assertSee('WIB');
    }

    /**
     * Memastikan laman Detail Data Mentah SIPT (setelah upload) menampilkan waktu di header sistem tanpa teks 'WAKTU LOGIN SISTEM',
     * memiliki tanda hijau kedap-kedip, dan 1 kolom WAKTU.
     */
    public function test_laman_detail_data_mentah_menampilkan_kolom_waktu_tunggal(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('dataset.show', $this->dataset));

        $response->assertOk();
        $response->assertDontSee('Waktu Evaluator Realtime:');
        $response->assertDontSee('Waktu Login Sistem :');
        $response->assertDontSee('WAKTU LOGIN SISTEM');
        $response->assertSee('animate-ping-dot');
        $response->assertSee('bg-emerald-500');
        $response->assertSee('WAKTU');

        $now = now();
        $response->assertSee($now->translatedFormat('d F Y'));
        $response->assertSee('WIB');
    }

    /**
     * Memastikan laman Standarisasi menampilkan waktu di header sistem tanpa teks 'WAKTU LOGIN SISTEM',
     * memiliki tanda hijau kedap-kedip, dan 1 kolom WAKTU.
     */
    public function test_laman_standarisasi_menampilkan_kolom_waktu_tunggal(): void
    {
        $response = $this->actingAs($this->evaluator)->get(route('standarisasi.index', ['dataset' => $this->dataset->id]));

        $response->assertOk();
        $response->assertDontSee('Waktu Evaluator Realtime:');
        $response->assertDontSee('Waktu Login Sistem :');
        $response->assertDontSee('WAKTU LOGIN SISTEM');
        $response->assertSee('animate-ping-dot');
        $response->assertSee('bg-emerald-500');
        $response->assertSee('WAKTU');

        $now = now();
        $response->assertSee($now->translatedFormat('d F Y'));
        $response->assertSee('WIB');
    }

    /**
     * Memastikan update manual cell di standarisasi mengembalikan waktu realtime pemrosesan.
     */
    public function test_update_manual_cell_standarisasi_mengembalikan_waktu_realtime(): void
    {
        $item = DataIklan::where('dataset_id', $this->dataset->id)->first();

        $response = $this->actingAs($this->evaluator)->postJson(
            route('dataset.manual-cell.update', ['dataset' => $this->dataset->id, 'item' => $item->id]),
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 2,
            ]
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'is_manual_complete',
            'waktu' => [
                'hari',
                'tanggal',
                'bulan',
                'tahun',
                'jam',
            ],
        ]);
        $response->assertJson([
            'success' => true,
            'waktu' => [
                'hari' => now()->translatedFormat('l'),
                'tanggal' => now()->format('d'),
                'bulan' => now()->translatedFormat('F'),
                'tahun' => now()->format('Y'),
            ],
        ]);
    }

    /**
     * Memastikan update manual cell di data mentah mengembalikan waktu realtime pemrosesan.
     */
    public function test_update_manual_cell_data_mentah_mengembalikan_waktu_realtime(): void
    {
        $rawItem = DataMentah::where('dataset_id', $this->dataset->id)->first();

        $response = $this->actingAs($this->evaluator)->postJson(
            route('dataset.mentah-cell.update', ['dataset' => $this->dataset->id, 'item' => $rawItem->id]),
            [
                'kolom' => 'kesesuaian_evaluasi',
                'nilai' => 2,
            ]
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'is_manual_complete',
            'waktu' => [
                'hari',
                'tanggal',
                'bulan',
                'tahun',
                'jam',
                'full',
            ],
        ]);
        $response->assertJson([
            'success' => true,
            'waktu' => [
                'hari' => now()->translatedFormat('l'),
                'tanggal' => now()->format('d'),
                'bulan' => now()->translatedFormat('F'),
                'tahun' => now()->format('Y'),
            ],
        ]);
    }

    /**
     * Memastikan laman Data Tersimpan menampilkan waktu di header sistem tanpa teks 'WAKTU LOGIN SISTEM',
     * memiliki tanda hijau kedap-kedip, dan 1 kolom WAKTU (berisi hari, tanggal, bulan, tahun).
     */
    public function test_laman_data_tersimpan_menampilkan_kolom_waktu_tunggal(): void
    {
        // Beri nilai evaluasi lengkap pada data mentah agar dataset siap difinalkan
        DataMentah::where('dataset_id', $this->dataset->id)->update([
            'evaluator_id' => $this->evaluator->id,
            'kesesuaian_evaluasi' => 1,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);
        app(StandardizationService::class)->standardizeDataset($this->dataset);

        // Finalisasi dataset agar muncul di Data Tersimpan
        app(StandardizationService::class)->finalizeDataset($this->dataset);

        $response = $this->actingAs($this->evaluator)->get(route('data-tersimpan.index', ['tahun' => 2026]));

        $response->assertOk();
        $response->assertDontSee('Waktu Evaluator Realtime:');
        $response->assertDontSee('Waktu Login Sistem :');
        $response->assertDontSee('WAKTU LOGIN SISTEM');
        $response->assertSee('animate-ping-dot');
        $response->assertSee('bg-emerald-500');
        $response->assertSee('WAKTU');

        $now = now();
        $response->assertSee($now->translatedFormat('d F Y'));
        $response->assertSee('WIB');
    }
}
