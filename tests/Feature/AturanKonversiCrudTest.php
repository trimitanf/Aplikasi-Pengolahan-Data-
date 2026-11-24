<?php

namespace Tests\Feature;

use App\Models\MasterKode;
use App\Models\PemetaanNilai;
use App\Models\User;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\PemetaanNilaiSeeder;
use Database\Seeders\UptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AturanKonversiCrudTest extends TestCase
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

    public function test_evaluator_tidak_dapat_mengakses_aturan_konversi(): void
    {
        $response = $this->actingAs($this->evaluator)->get('/aturan-konversi');
        $response->assertStatus(403);

        $responsePost = $this->actingAs($this->evaluator)->post('/aturan-konversi', [
            'grup' => 'jenis_produk',
            'kolom_sumber' => 'KOMODITI',
            'nilai_sipt' => 'OBAT BARU',
            'kode' => 1,
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_admin_dapat_melihat_daftar_aturan_konversi(): void
    {
        $response = $this->actingAs($this->admin)->get('/aturan-konversi');

        $response->assertStatus(200);
        $response->assertSee('Aturan Konversi SIPT');
        $response->assertSee('Total Aturan Konversi');
        $response->assertSee('OBAT TRADISIONAL');
    }

    public function test_admin_dapat_menambahkan_aturan_konversi_baru(): void
    {
        $response = $this->actingAs($this->admin)->post('/aturan-konversi', [
            'grup' => 'nama_media',
            'kolom_sumber' => 'MEDIA',
            'nilai_sipt' => 'BLIBLI MALL OFFICIAL',
            'kode' => 11,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/aturan-konversi');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('pemetaan_nilai', [
            'grup' => 'nama_media',
            'kolom_sumber' => 'MEDIA',
            'nilai_sipt' => 'BLIBLI MALL OFFICIAL',
            'kode' => 11,
            'is_active' => true,
        ]);
    }

    public function test_admin_dapat_mengubah_aturan_konversi(): void
    {
        $rule = PemetaanNilai::first();

        $response = $this->actingAs($this->admin)->put("/aturan-konversi/{$rule->id}", [
            'kode' => $rule->kode,
            'is_active' => 0,
        ]);

        $response->assertRedirect('/aturan-konversi');
        $this->assertDatabaseHas('pemetaan_nilai', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_dapat_menghapus_aturan_konversi(): void
    {
        $rule = PemetaanNilai::create([
            'grup' => 'nama_media',
            'kolom_sumber' => 'MEDIA',
            'nilai_sipt' => 'MEDIA UNTUK DIHAPUS',
            'kode' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/aturan-konversi/{$rule->id}");

        $response->assertRedirect('/aturan-konversi');
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('pemetaan_nilai', [
            'id' => $rule->id,
        ]);
    }
}
