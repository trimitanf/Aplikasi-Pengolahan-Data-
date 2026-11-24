<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\PemetaanNilaiSeeder;
use Database\Seeders\TargetIkuSeeder;
use Database\Seeders\UptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            UptSeeder::class,
            MasterKodeSeeder::class,
            PemetaanNilaiSeeder::class,
            TargetIkuSeeder::class,
        ]);

        $this->admin = User::create([
            'name' => 'Administrator BPOM',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->evaluator = User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);
    }

    public function test_evaluator_tidak_dapat_mengakses_halaman_pengaturan(): void
    {
        $response = $this->actingAs($this->evaluator)->get('/pengaturan?tab=target_iku');
        $response->assertStatus(403);

        $responsePost = $this->actingAs($this->evaluator)->post('/pengaturan/target-iku', [
            'tahun' => 2026,
            'kelompok' => 'OBA',
            'target' => 70.0,
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_admin_dapat_mengakses_halaman_pengaturan_target_iku(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengaturan?tab=target_iku');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Sistem', false);
        $response->assertSee('Obat Bahan Alam (OBA)');
        $response->assertSee('Suplemen Kesehatan (SK)');
    }

    public function test_kamus_kode_read_only_menampilkan_data_master(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengaturan?tab=kamus_kode&grup=jenis_produk');

        $response->assertStatus(200);
        $response->assertSee('OT');
        $response->assertSee('SK');
        $response->assertSee('OK');
    }

    public function test_daftar_83_upt_dapat_dilihat_di_kamus_kode(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengaturan?tab=kamus_kode&grup=upt_83');

        $response->assertStatus(200);
        $response->assertSee('Balai Besar POM di Banda Aceh');
        $response->assertSee('Loka POM di Kabupaten Bone');
    }

    public function test_aturan_pemetaan_nilai_dapat_dilihat(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengaturan?tab=pemetaan');

        $response->assertStatus(200);
        $response->assertSee('Aturan Pemetaan Nilai Mentah SIPT ke Kode Angka');
        $response->assertSee('SHOPEE');
        $response->assertSee('TOKOPEDIA');
    }

    public function test_admin_dapat_memperbarui_target_iku(): void
    {
        $response = $this->actingAs($this->admin)->post('/pengaturan/target-iku', [
            'tahun' => 2026,
            'kelompok' => 'OBA',
            'target' => 70.50,
        ]);

        $response->assertRedirect('/pengaturan?tab=target_iku');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('target_iku', [
            'tahun' => 2026,
            'kelompok' => 'OBA',
            'target' => 70.50,
        ]);
    }

    public function test_target_iku_validasi_menolak_angka_tidak_valid(): void
    {
        $response = $this->actingAs($this->admin)->post('/pengaturan/target-iku', [
            'tahun' => 2026,
            'kelompok' => 'OBA',
            'target' => 150, // Lebih dari 100
        ]);

        $response->assertSessionHasErrors('target');
    }
}
