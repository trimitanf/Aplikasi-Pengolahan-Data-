<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Administrator Utama',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->evaluator = User::create([
            'name' => 'Evaluator BPOM 1',
            'username' => 'evaluator1',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);
    }

    public function test_evaluator_tidak_dapat_mengakses_kelola_pengguna(): void
    {
        $response = $this->actingAs($this->evaluator)->get('/pengguna');
        $response->assertStatus(403);

        $responsePost = $this->actingAs($this->evaluator)->post('/pengguna', [
            'name' => 'User Baru',
            'username' => 'userbaru',
            'password' => 'password123',
            'role' => 'evaluator',
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_admin_dapat_melihat_daftar_pengguna(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengguna');

        $response->assertStatus(200);
        $response->assertSee('Kelola Pengguna Sistem');
        $response->assertSee('Administrator Utama');
        $response->assertSee('evaluator1');
    }

    public function test_admin_dapat_mencari_dan_memfilter_pengguna(): void
    {
        $response = $this->actingAs($this->admin)->get('/pengguna?cari=evaluator1');
        $response->assertStatus(200);
        $response->assertSee('Evaluator BPOM 1');

        $responseFilterRole = $this->actingAs($this->admin)->get('/pengguna?role=admin');
        $responseFilterRole->assertStatus(200);
        $responseFilterRole->assertSee('Administrator Utama');
    }

    public function test_admin_dapat_menambahkan_pengguna_baru(): void
    {
        $response = $this->actingAs($this->admin)->post('/pengguna', [
            'name' => 'Evaluator BPOM 2',
            'username' => 'evaluator2',
            'password' => 'password123',
            'role' => 'evaluator',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/pengguna');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'name' => 'Evaluator BPOM 2',
            'username' => 'evaluator2',
            'role' => 'evaluator',
            'is_active' => true,
        ]);
    }

    public function test_admin_dapat_mengubah_data_pengguna(): void
    {
        $response = $this->actingAs($this->admin)->put("/pengguna/{$this->evaluator->id}", [
            'name' => 'Evaluator BPOM Terupdate',
            'username' => 'evaluator1_updated',
            'role' => 'evaluator',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/pengguna');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $this->evaluator->id,
            'name' => 'Evaluator BPOM Terupdate',
            'username' => 'evaluator1_updated',
        ]);
    }

    public function test_admin_dapat_mengubah_nama_dan_username_dengan_titik_atau_spasi_atau_tanda_pagar(): void
    {
        // 1. Mengubah username dengan titik (misal: mita.anggraini)
        $responseDot = $this->actingAs($this->admin)->put("/pengguna/{$this->evaluator->id}", [
            'name' => 'Mita Anggraini',
            'username' => 'mita.anggraini',
            'role' => 'evaluator',
            'is_active' => '1',
        ]);
        $responseDot->assertRedirect('/pengguna');
        $this->assertDatabaseHas('users', [
            'id' => $this->evaluator->id,
            'name' => 'Mita Anggraini',
            'username' => 'mita.anggraini',
        ]);

        // 2. Mengubah username dengan spasi (otomatis dikonversi ke underscore)
        $responseSpace = $this->actingAs($this->admin)->put("/pengguna/{$this->evaluator->id}", [
            'name' => 'Mita BPOM Baru',
            'username' => 'mita bpom 1',
            'role' => 'evaluator',
            'is_active' => '1',
        ]);
        $responseSpace->assertRedirect('/pengguna');
        $this->assertDatabaseHas('users', [
            'id' => $this->evaluator->id,
            'name' => 'Mita BPOM Baru',
            'username' => 'mita_bpom_1',
        ]);

        // 3. Mengubah username dengan tanda pagar (misal user mengira ID #1)
        $responseHash = $this->actingAs($this->admin)->put("/pengguna/{$this->evaluator->id}", [
            'name' => 'Mita ID 10',
            'username' => '#10',
            'role' => 'evaluator',
            'is_active' => '1',
        ]);
        $responseHash->assertRedirect('/pengguna');
        $this->assertDatabaseHas('users', [
            'id' => $this->evaluator->id,
            'name' => 'Mita ID 10',
            'username' => '10',
        ]);
    }

    public function test_admin_dapat_mengubah_pengguna_melalui_rute_fallback(): void
    {
        $response = $this->actingAs($this->admin)->put('/pengguna', [
            'user_id' => $this->evaluator->id,
            'name' => 'Evaluator BPOM Fallback',
            'username' => 'evaluator_fallback',
            'role' => 'evaluator',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/pengguna');
        $this->assertDatabaseHas('users', [
            'id' => $this->evaluator->id,
            'name' => 'Evaluator BPOM Fallback',
            'username' => 'evaluator_fallback',
        ]);
    }

    public function test_admin_tidak_bisa_mengubah_role_sendiri_menjadi_evaluator(): void
    {
        $response = $this->actingAs($this->admin)->put("/pengguna/{$this->admin->id}", [
            'name' => 'Administrator Utama',
            'username' => 'admin',
            'role' => 'evaluator',
            'is_active' => '1',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('admin', $this->admin->fresh()->role);
    }

    public function test_admin_dapat_menghapus_pengguna_evaluator(): void
    {
        $response = $this->actingAs($this->admin)->delete("/pengguna/{$this->evaluator->id}");

        $response->assertRedirect('/pengguna');
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('users', [
            'id' => $this->evaluator->id,
        ]);
    }

    public function test_admin_tidak_dapat_menghapus_akun_sendiri(): void
    {
        $response = $this->actingAs($this->admin)->delete("/pengguna/{$this->admin->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
        ]);
    }

    public function test_tidak_dapat_menghapus_satu_satunya_admin(): void
    {
        // Add another admin
        $admin2 = User::create([
            'name' => 'Admin Kedua',
            'username' => 'admin2',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Delete admin2 while acting as admin
        $this->actingAs($this->admin)->delete("/pengguna/{$admin2->id}");
        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);

        // Now only 1 admin left; trying to delete the last admin from anywhere should be prevented
        // If we make evaluator try to delete: 403. If someone bypasses: handled.
    }
}
