<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_utama_mengarahkan_ke_login_jika_belum_autentikasi(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_halaman_utama_mengarahkan_ke_dashboard_jika_sudah_login(): void
    {
        $user = User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/dashboard');
    }
}
