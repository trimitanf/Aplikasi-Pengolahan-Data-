<?php

namespace Tests\Feature;

use App\Models\MasterKode;
use App\Models\PemetaanNilai;
use App\Models\User;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\PemetaanNilaiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AturanPemetaanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MasterKodeSeeder::class,
            PemetaanNilaiSeeder::class,
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

    public function test_evaluator_tidak_dapat_mengakses_kelola_aturan_pemetaan(): void
    {
        $response = $this->actingAs($this->evaluator)->get('/aturan-pemetaan');
        $response->assertStatus(403);

        $responsePost = $this->actingAs($this->evaluator)->post('/aturan-pemetaan', [
            'grup' => 'jenis_produk',
            'kolom_sumber' => 'KOMODITI',
            'nilai_sipt' => 'OBAT HERBAL',
            'kode' => 1,
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_admin_dapat_melihat_daftar_aturan_pemetaan(): void
    {
        $response = $this->actingAs($this->admin)->get('/aturan-pemetaan');

        $response->assertStatus(200);
        $response->assertSee('Kelola Aturan Pemetaan SIPT');
        $response->assertSee('OBAT TRADISIONAL');
    }

    public function test_admin_dapat_mencari_dan_memfilter_aturan(): void
    {
        $response = $this->actingAs($this->admin)->get('/aturan-pemetaan?cari=SHOPEE');
        $response->assertStatus(200);
        $response->assertSee('SHOPEE');

        $responseFilter = $this->actingAs($this->admin)->get('/aturan-pemetaan?grup=nama_media');
        $responseFilter->assertStatus(200);
    }

    public function test_admin_dapat_menambahkan_aturan_pemetaan_baru(): void
    {
        $response = $this->actingAs($this->admin)->post('/aturan-pemetaan', [
            'grup' => 'nama_media',
            'kolom_sumber' => 'MEDIA',
            'nilai_sipt' => 'TIKTOK SHOP INDONESIA',
            'kode' => 12,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/aturan-pemetaan');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('pemetaan_nilai', [
            'grup' => 'nama_media',
            'kolom_sumber' => 'MEDIA',
            'nilai_sipt' => 'TIKTOK SHOP INDONESIA',
            'kode' => 12,
            'is_active' => true,
        ]);
    }

    public function test_admin_dapat_mengubah_kode_aturan_pemetaan(): void
    {
        $rule = PemetaanNilai::where('nilai_sipt', 'SHOPEE')->first();
        $this->assertNotNull($rule);

        $response = $this->actingAs($this->admin)->put("/aturan-pemetaan/{$rule->id}", [
            'kode' => 99,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/aturan-pemetaan');
        $response->assertSessionHas('status');

        $this->assertEquals(99, $rule->fresh()->kode);
    }

    public function test_admin_dapat_menghapus_aturan_pemetaan(): void
    {
        $rule = PemetaanNilai::where('nilai_sipt', 'SHOPEE')->first();
        $this->assertNotNull($rule);

        $response = $this->actingAs($this->admin)->delete("/aturan-pemetaan/{$rule->id}");

        $response->assertRedirect('/aturan-pemetaan');
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('pemetaan_nilai', [
            'id' => $rule->id,
        ]);
    }
}
