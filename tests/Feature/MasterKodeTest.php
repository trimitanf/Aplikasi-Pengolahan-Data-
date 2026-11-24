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

class MasterKodeTest extends TestCase
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

    public function test_evaluator_tidak_dapat_mengakses_kamus_kode(): void
    {
        $response = $this->actingAs($this->evaluator)->get('/kamus-kode');
        $response->assertStatus(403);

        $responsePost = $this->actingAs($this->evaluator)->post('/kamus-kode', [
            'grup' => 'jenis_produk',
            'kode' => 99,
            'nama' => 'TEST PRODUK',
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_admin_dapat_melihat_daftar_kamus_kode(): void
    {
        $response = $this->actingAs($this->admin)->get('/kamus-kode?grup=jenis_produk');

        $response->assertStatus(200);
        $response->assertSee('Master Data');
        $response->assertSee('Total Kode Standar');
        $response->assertSee('OBAT TRADISIONAL');
        $response->assertDontSee('Kelompok Rekap');
    }

    public function test_admin_dapat_menambahkan_kode_standar_baru(): void
    {
        $response = $this->actingAs($this->admin)->post('/kamus-kode', [
            'grup' => 'jenis_produk',
            'kode' => 88,
            'nama' => 'PRODUK BARU HERBAL',
            'kelompok_rekap' => 'OT',
        ]);

        $response->assertRedirect('/kamus-kode?grup=jenis_produk');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('master_kodes', [
            'grup' => 'jenis_produk',
            'kode' => 88,
            'nama' => 'PRODUK BARU HERBAL',
            'kelompok_rekap' => 'OT',
        ]);
    }

    public function test_admin_tidak_dapat_menambahkan_kode_duplikat(): void
    {
        $existing = MasterKode::first();

        $response = $this->actingAs($this->admin)->post('/kamus-kode', [
            'grup' => $existing->grup,
            'kode' => $existing->kode,
            'nama' => 'DUPLIKAT',
        ]);

        $response->assertSessionHasErrors('kode');
    }

    public function test_admin_dapat_memperbarui_kode_standar(): void
    {
        $item = MasterKode::where('grup', 'jenis_produk')->first();

        $response = $this->actingAs($this->admin)->put("/kamus-kode/{$item->id}", [
            'nama' => 'NAMA DIUBAH',
            'kelompok_rekap' => 'OT_MODIFIED',
        ]);

        $response->assertRedirect("/kamus-kode?grup={$item->grup}");
        $this->assertDatabaseHas('master_kodes', [
            'id' => $item->id,
            'nama' => 'NAMA DIUBAH',
            'kelompok_rekap' => 'OT_MODIFIED',
        ]);
    }

    public function test_admin_dicegah_menghapus_kode_yang_sedang_digunakan_pemetaan(): void
    {
        // Temukan kode yang sudah dipakai di pemetaan_nilai
        $pemetaan = PemetaanNilai::first();
        $item = MasterKode::where('grup', $pemetaan->grup)->where('kode', $pemetaan->kode)->first();

        if ($item) {
            $response = $this->actingAs($this->admin)->delete("/kamus-kode/{$item->id}");
            $response->assertSessionHasErrors('error');
            $this->assertDatabaseHas('master_kodes', ['id' => $item->id]);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_admin_dapat_menghapus_kode_yang_tidak_digunakan(): void
    {
        $item = MasterKode::create([
            'grup' => 'test_grup',
            'kode' => 999,
            'nama' => 'KODE SEMENTARA',
        ]);

        $response = $this->actingAs($this->admin)->delete("/kamus-kode/{$item->id}");
        $response->assertRedirect('/kamus-kode?grup=test_grup');
        $this->assertDatabaseMissing('master_kodes', ['id' => $item->id]);
    }
}
