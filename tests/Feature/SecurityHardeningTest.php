<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $username, string $role = 'evaluator', bool $active = true): User
    {
        return User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    public function test_respons_memiliki_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_halaman_login_tidak_disimpan_di_cache(): void
    {
        $user = $this->makeUser('evaluator1');

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_pengguna_yang_dinonaktifkan_dikeluarkan_dari_sesi(): void
    {
        $user = $this->makeUser('evaluator1');
        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_tidak_dapat_menonaktifkan_akun_sendiri(): void
    {
        $admin = $this->makeUser('admin', 'admin');

        $response = $this->actingAs($admin)->put("/pengguna/{$admin->id}", [
            'name' => 'Admin',
            'username' => 'admin',
            'role' => 'admin',
            // is_active tidak dikirim = checkbox tidak dicentang
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_login_dibatasi_rate_limit(): void
    {
        $this->makeUser('evaluator1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'evaluator1', 'password' => 'salah']);
        }

        $response = $this->post('/login', ['username' => 'evaluator1', 'password' => 'password123']);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_evaluator_hanya_dapat_menugaskan_baris_ke_diri_sendiri(): void
    {
        $admin = $this->makeUser('admin', 'admin');
        $eval1 = $this->makeUser('evaluator1');
        $eval2 = $this->makeUser('evaluator2');

        $dataset = \App\Models\Dataset::create([
            'nama_file_asli' => 'dataset.xls',
            'path_file' => 'private/datasets/dataset.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 2,
            'uploaded_by' => $admin->id,
        ]);

        $row1 = \App\Models\DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 1,
            'upt' => 'Balai Besar POM di Banda Aceh',
            'jenis_produk' => 'Obat Tradisional',
            'nama_produk' => 'Produk A',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
            'evaluator_id' => null,
        ]);

        $row2 = \App\Models\DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 2,
            'upt' => 'Balai POM di Palu',
            'jenis_produk' => 'Obat Tradisional',
            'nama_produk' => 'Produk B',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
            'evaluator_id' => $eval2->id,
        ]);

        // 1. Evaluator 1 boleh menugaskan baris kosong ke dirinya sendiri
        $respSelf = $this->actingAs($eval1)->postJson("/dataset/{$dataset->id}/data-mentah/manual-cell/{$row1->id}", [
            'kolom' => 'evaluator_id',
            'nilai' => $eval1->id,
        ]);
        $respSelf->assertOk();
        $this->assertEquals($eval1->id, $row1->fresh()->evaluator_id);

        // 2. Evaluator 1 DITOLAK (403) jika mencoba menugaskan ke evaluator lain
        $respOther = $this->actingAs($eval1)->postJson("/dataset/{$dataset->id}/data-mentah/manual-cell/{$row1->id}", [
            'kolom' => 'evaluator_id',
            'nilai' => $eval2->id,
        ]);
        $respOther->assertStatus(403);

        // 3. Evaluator 1 DITOLAK (403) jika mencoba melepas penugasan baris milik Evaluator 2
        $respUnassignOther = $this->actingAs($eval1)->postJson("/dataset/{$dataset->id}/data-mentah/manual-cell/{$row2->id}", [
            'kolom' => 'evaluator_id',
            'nilai' => null,
        ]);
        $respUnassignOther->assertStatus(403);

        // 4. Admin BEBAS menugaskan baris ke siapa pun
        $respAdmin = $this->actingAs($admin)->postJson("/dataset/{$dataset->id}/data-mentah/manual-cell/{$row2->id}", [
            'kolom' => 'evaluator_id',
            'nilai' => $eval1->id,
        ]);
        $respAdmin->assertOk();
        $this->assertEquals($eval1->id, $row2->fresh()->evaluator_id);
    }

    public function test_evaluator_tidak_dapat_assign_upt_ke_evaluator_lain(): void
    {
        $admin = $this->makeUser('admin', 'admin');
        $eval1 = $this->makeUser('evaluator1');
        $eval2 = $this->makeUser('evaluator2');

        $dataset = \App\Models\Dataset::create([
            'nama_file_asli' => 'dataset_upt.xls',
            'path_file' => 'private/datasets/dataset_upt.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 1,
            'uploaded_by' => $admin->id,
        ]);

        \App\Models\Upt::create(['kode' => 1, 'nama' => 'Balai Besar POM di Banda Aceh']);

        // Evaluator 1 mencoba menugaskan UPT ke Evaluator 2 -> Ditolak
        $resp = $this->actingAs($eval1)->post("/dataset/{$dataset->id}/assign-upt", [
            'upt' => '1',
            'evaluator_id' => $eval2->id,
        ]);
        $resp->assertSessionHas('error');

        // Evaluator 1 menugaskan UPT ke dirinya sendiri -> Berhasil
        $respOk = $this->actingAs($eval1)->post("/dataset/{$dataset->id}/assign-upt", [
            'upt' => '1',
            'evaluator_id' => $eval1->id,
        ]);
        $respOk->assertSessionHas('status');
    }
}
