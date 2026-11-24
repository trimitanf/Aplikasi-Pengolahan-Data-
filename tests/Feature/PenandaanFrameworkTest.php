<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PenandaanFrameworkTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $evaluatorIklan;
    protected User $evaluatorPenandaan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Utama',
            'username' => 'admin_test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->evaluatorIklan = User::create([
            'name' => 'Evaluator Iklan OTSK',
            'username' => 'eval_iklan',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->evaluatorPenandaan = User::create([
            'name' => 'Evaluator Penandaan OTSK',
            'username' => 'eval_penandaan',
            'password' => Hash::make('password123'),
            'role' => 'evaluator_penandaan',
            'is_active' => true,
        ]);
    }

    public function test_admin_dapat_mengakses_modul_iklan_dan_modul_penandaan(): void
    {
        // Akses modul penandaan
        $this->actingAs($this->admin)->get(route('penandaan.dashboard'))->assertOk()->assertSee('Framework Modul Pengawasan Penandaan Aktif');
        $this->actingAs($this->admin)->get(route('penandaan.dataset.index'))->assertOk()->assertSee('Kelola Dataset Penandaan');
        $this->actingAs($this->admin)->get(route('penandaan.standarisasi.index'))->assertOk()->assertSee('Standarisasi Data Penandaan');
        $this->actingAs($this->admin)->get(route('penandaan.rekap.index'))->assertOk()->assertSee('Rekap Evaluasi Penandaan');

        // Akses modul iklan
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('dataset.index'))->assertOk();

        // Verifikasi sidebar admin merender modul iklan dan dapat mengakses penandaan
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertSee('Pengawasan Iklan');
        $response->assertSee(route('dataset.index'));

        // Verifikasi evaluator penandaan melihat seksi Pengawasan Penandaan
        $resPen = $this->actingAs($this->evaluatorPenandaan)->get(route('penandaan.dashboard'));
        $resPen->assertOk();
        $resPen->assertSee('Pengawasan Penandaan');
    }

    public function test_evaluator_iklan_dapat_mengakses_iklan_tetapi_ditolak_mengakses_penandaan(): void
    {
        // Dapat mengakses modul iklan
        $this->actingAs($this->evaluatorIklan)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->evaluatorIklan)->get(route('dataset.index'))->assertOk();

        // Ditolak 403 saat mencoba mengakses modul penandaan
        $this->actingAs($this->evaluatorIklan)->get(route('penandaan.dashboard'))->assertStatus(403);
        $this->actingAs($this->evaluatorIklan)->get(route('penandaan.dataset.index'))->assertStatus(403);
        $this->actingAs($this->evaluatorIklan)->get(route('penandaan.standarisasi.index'))->assertStatus(403);
        $this->actingAs($this->evaluatorIklan)->get(route('penandaan.rekap.index'))->assertStatus(403);

        // Sidebar evaluator iklan tidak menampilkan rute penandaan
        $response = $this->actingAs($this->evaluatorIklan)->get(route('dashboard'));
        $response->assertDontSee(route('penandaan.dashboard'));
        $response->assertDontSee('Dashboard Penandaan');
    }

    public function test_evaluator_penandaan_dapat_mengakses_penandaan_tetapi_ditolak_mengakses_iklan(): void
    {
        // Dapat mengakses modul penandaan
        $this->actingAs($this->evaluatorPenandaan)->get(route('penandaan.dashboard'))->assertOk();
        $this->actingAs($this->evaluatorPenandaan)->get(route('penandaan.dataset.index'))->assertOk();
        $this->actingAs($this->evaluatorPenandaan)->get(route('penandaan.standarisasi.index'))->assertOk();
        $this->actingAs($this->evaluatorPenandaan)->get(route('penandaan.rekap.index'))->assertOk();

        // Ditolak 403 saat mencoba mengakses modul iklan
        $this->actingAs($this->evaluatorPenandaan)->get(route('dashboard'))->assertStatus(403);
        $this->actingAs($this->evaluatorPenandaan)->get(route('dataset.index'))->assertStatus(403);
        $this->actingAs($this->evaluatorPenandaan)->get(route('standarisasi.index'))->assertStatus(403);
        $this->actingAs($this->evaluatorPenandaan)->get(route('rekap.index'))->assertStatus(403);

        // Sidebar evaluator penandaan hanya menampilkan modul penandaan
        $response = $this->actingAs($this->evaluatorPenandaan)->get(route('penandaan.dashboard'));
        $response->assertSee(route('penandaan.dataset.index'));
        $response->assertDontSee(route('dataset.index'));
    }

    public function test_redirect_root_sesuai_role(): void
    {
        // Evaluator penandaan ke penandaan.dashboard
        $this->actingAs($this->evaluatorPenandaan)->get('/')
            ->assertRedirect(route('penandaan.dashboard'));

        // Evaluator iklan ke dashboard
        $this->actingAs($this->evaluatorIklan)->get('/')
            ->assertRedirect(route('dashboard'));

        // Admin ke dashboard
        $this->actingAs($this->admin)->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_controller_mengarahkan_evaluator_penandaan_ke_dashboard_penandaan(): void
    {
        $response = $this->post(route('login.post'), [
            'username' => 'eval_penandaan',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('penandaan.dashboard'));
    }

    public function test_kelola_pengguna_dapat_mendaftarkan_dan_memfilter_evaluator_penandaan(): void
    {
        // Tambah user evaluator_penandaan baru
        $response = $this->actingAs($this->admin)->post(route('pengguna.store'), [
            'name' => 'Staf Baru Penandaan',
            'username' => 'staf_penandaan_1',
            'password' => 'password123',
            'role' => 'evaluator_penandaan',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('pengguna.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'staf_penandaan_1',
            'role' => 'evaluator_penandaan',
        ]);

        // Filter laman pengguna berdasarkan role evaluator_penandaan
        $pageResponse = $this->actingAs($this->admin)->get(route('pengguna.index', ['role' => 'evaluator_penandaan']));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Staf Baru Penandaan');
        $pageResponse->assertSee('Evaluator Penandaan');
    }
}
