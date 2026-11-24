<?php

namespace Tests\Feature;

use App\Models\TargetIku;
use App\Models\User;
use Database\Seeders\TargetIkuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TargetIkuCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
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

    public function test_evaluator_tidak_dapat_mengakses_target_iku(): void
    {
        $response = $this->actingAs($this->evaluator)->get('/target-iku');
        $response->assertStatus(403);

        $responsePost = $this->actingAs($this->evaluator)->post('/target-iku', [
            'tahun' => 2026,
            'kelompok' => 'OBA',
            'target' => 75.0,
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_admin_dapat_melihat_daftar_target_iku(): void
    {
        $response = $this->actingAs($this->admin)->get('/target-iku');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Target IKU');
        $response->assertSee('Tahun Berjalan');
        $response->assertSee('Obat Bahan Alam (OBA)');
    }

    public function test_admin_dapat_menambahkan_target_iku_baru(): void
    {
        $response = $this->actingAs($this->admin)->post('/target-iku', [
            'tahun' => 2030,
            'kelompok' => 'OBA',
            'target' => 80.5,
        ]);

        $response->assertRedirect('/target-iku');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('target_iku', [
            'tahun' => 2030,
            'kelompok' => 'OBA',
            'target' => 80.5,
        ]);
    }

    public function test_admin_dapat_mengubah_nilai_target_iku(): void
    {
        $target = TargetIku::first();

        $response = $this->actingAs($this->admin)->put("/target-iku/{$target->id}", [
            'target' => 95.0,
        ]);

        $response->assertRedirect('/target-iku');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('target_iku', [
            'id' => $target->id,
            'target' => 95.0,
        ]);
    }

    public function test_admin_dapat_menghapus_target_iku(): void
    {
        $target = TargetIku::create([
            'tahun' => 2040,
            'kelompok' => 'SK',
            'target' => 77.0,
        ]);

        $response = $this->actingAs($this->admin)->delete("/target-iku/{$target->id}");

        $response->assertRedirect('/target-iku');
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('target_iku', [
            'id' => $target->id,
        ]);
    }

    public function test_kolom_tahun_tidak_menampilkan_teks_tahun_ini(): void
    {
        $response = $this->actingAs($this->admin)->get('/target-iku');
        $response->assertStatus(200);

        // Memastikan tidak ada teks/badge 'Tahun Ini' di kolom tabel
        $response->assertDontSee('Tahun Ini');
        // Filter toolbar merender dropdown tahun dan komoditi
        $response->assertSee('Semua Tahun');
        $response->assertSee('Tahun:');
        $response->assertSee('Komoditi:');
    }

    public function test_admin_dapat_memfilter_target_iku_berdasarkan_periode_sebelum_dan_akan_datang(): void
    {
        $currentYear = (int) date('Y');

        TargetIku::create([
            'tahun' => $currentYear - 2,
            'kelompok' => 'OBA',
            'target' => 60.0,
        ]);

        TargetIku::create([
            'tahun' => $currentYear + 2,
            'kelompok' => 'SK',
            'target' => 90.0,
        ]);

        // 1. Filter tahun-tahun sebelum
        $responseSebelum = $this->actingAs($this->admin)->get('/target-iku?periode=sebelum');
        $responseSebelum->assertStatus(200);
        $listSebelum = $responseSebelum->viewData('targetIkuList');
        $this->assertTrue($listSebelum->contains('tahun', $currentYear - 2));
        $this->assertFalse($listSebelum->contains('tahun', $currentYear + 2));
        $this->assertFalse($listSebelum->contains('tahun', $currentYear));

        // 2. Filter tahun akan datang
        $responseAkanDatang = $this->actingAs($this->admin)->get('/target-iku?periode=akan_datang');
        $responseAkanDatang->assertStatus(200);
        $listAkanDatang = $responseAkanDatang->viewData('targetIkuList');
        $this->assertTrue($listAkanDatang->contains('tahun', $currentYear + 2));
        $this->assertFalse($listAkanDatang->contains('tahun', $currentYear - 2));
        $this->assertFalse($listAkanDatang->contains('tahun', $currentYear));

        // 3. Filter tahun spesifik
        $responseSpesifik = $this->actingAs($this->admin)->get('/target-iku?tahun=' . ($currentYear - 2));
        $responseSpesifik->assertStatus(200);
        $listSpesifik = $responseSpesifik->viewData('targetIkuList');
        $this->assertTrue($listSpesifik->contains('tahun', $currentYear - 2));
        $this->assertFalse($listSpesifik->contains('tahun', $currentYear + 2));
    }
}
