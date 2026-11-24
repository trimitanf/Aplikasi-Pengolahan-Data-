<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('evaluator|127.0.0.1');
    }

    public function test_halaman_login_dapat_diakses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sistem Informasi Pengolahan Data Iklan');
        $response->assertSee('Unit Kerja Tim Iklan dan Penandaan');
        $response->assertSee('NAMA');
        $response->assertSee('NIP');
        $response->assertSee('Username');
        $response->assertSee('Password');
    }

    public function test_evaluator_dapat_login_dengan_nama_lengkap_dan_nip(): void
    {
        User::create([
            'name' => 'Mita Evaluator',
            'username' => 'mita',
            'password' => Hash::make('199801012024012001'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'Mita Evaluator',
            'password' => '199801012024012001',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_evaluator_dapat_login_dengan_username_dan_password(): void
    {
        User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'evaluator',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_gagal_jika_password_salah(): void
    {
        User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'evaluator',
            'password' => 'passwordsalah',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        User::create([
            'name' => 'Evaluator Nonaktif',
            'username' => 'evaluator_nonaktif',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'evaluator_nonaktif',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_throttling_membatasi_setelah_lima_kali_percobaan_gagal(): void
    {
        User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'evaluator',
                'password' => 'passwordsalah',
            ]);
        }

        // Attempt ke-6 harus ditolak oleh rate limiter
        $response = $this->post('/login', [
            'username' => 'evaluator',
            'password' => 'passwordsalah',
        ]);

        $response->assertSessionHasErrors('username');
        $errors = session('errors')->get('username');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errors[0]);
    }

    public function test_evaluator_dapat_logout(): void
    {
        $user = User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guest_diarahkan_ke_login_saat_mengakses_halaman_terproteksi(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $response2 = $this->get('/pengaturan');
        $response2->assertRedirect('/login');
    }
}
