<?php

namespace Tests\Feature;

use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\Upt;
use App\Models\User;
use Database\Seeders\MasterKodeSeeder;
use Database\Seeders\PemetaanNilaiSeeder;
use Database\Seeders\TargetIkuSeeder;
use Database\Seeders\UptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotifikasiKodeBelumTerstandarisasiTest extends TestCase
{
    use RefreshDatabase;

    protected User $evaluator;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $this->seed([
            UptSeeder::class,
            MasterKodeSeeder::class,
            PemetaanNilaiSeeder::class,
            TargetIkuSeeder::class,
        ]);

        $this->evaluator = User::create([
            'name' => 'Evaluator BPOM',
            'username' => 'evaluator',
            'password' => Hash::make('password123'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin BPOM',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_muncul_notifikasi_singkat_jika_ada_upt_baru_yang_belum_terdaftar_di_master_data(): void
    {
        $dataset = Dataset::create([
            'uploaded_by' => $this->evaluator->id,
            'nama_file_asli' => 'dataset_upt_baru.xls',
            'path_file' => 'private/datasets/test.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 2,
        ]);

        // Baris 1: UPT terdaftar (Balai Besar POM di Jakarta)
        DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 1,
            'upt' => 'Balai Besar POM di Jakarta',
            'nama_produk' => 'Tolak Angin',
            'nomor_izin_edar' => 'TR123456789',
        ]);

        // Baris 2: UPT baru / belum terdaftar di Master Data
        DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 2,
            'upt' => 'Loka POM di Kabupaten Mahakam Ulu',
            'nama_produk' => 'Jamu Segar',
            'nomor_izin_edar' => 'TR987654321',
        ]);

        // Cek deteksi peringatan
        $warnings = $dataset->getUnstandardizedWarnings();
        $this->assertArrayHasKey('upt', $warnings);
        $this->assertContains('Loka POM di Kabupaten Mahakam Ulu', $warnings['upt']);
        $this->assertNotContains('Balai Besar POM di Jakarta', $warnings['upt']);

        // Buka halaman show (Data Mentah)
        $responseShow = $this->actingAs($this->evaluator)->get(route('dataset.show', $dataset));
        $responseShow->assertOk();
        $responseShow->assertSee('Pemberitahuan: Ditemukan Kode Belum Terstandarisasi di Master Data');
        $responseShow->assertSee('Loka POM di Kabupaten Mahakam Ulu');
        $responseShow->assertSee('Silakan hubungi Administrator untuk menambahkan kode standar UPT baru');

        // Buka halaman standardisasi
        $responseStd = $this->actingAs($this->evaluator)->get(route('dataset.standardisasi', $dataset));
        $responseStd->assertOk();
        $responseStd->assertSee('Pemberitahuan: Ditemukan Kode Belum Terstandarisasi di Master Data');
        $responseStd->assertSee('Loka POM di Kabupaten Mahakam Ulu');
    }

    public function test_notifikasi_hilang_setelah_admin_menambahkan_kode_upt_ke_master_data(): void
    {
        $dataset = Dataset::create([
            'uploaded_by' => $this->evaluator->id,
            'nama_file_asli' => 'dataset_upt_baru.xls',
            'path_file' => 'private/datasets/test.xls',
            'komoditi' => 'OBA',
            'periode_tahun' => 2026,
            'periode_bulan' => 1,
            'status' => 'Valid',
            'jumlah_baris' => 1,
        ]);

        DataMentah::create([
            'dataset_id' => $dataset->id,
            'nomor_baris' => 1,
            'upt' => 'Loka POM di Kota Nusantara',
            'nama_produk' => 'Herbal Sehat',
            'nomor_izin_edar' => 'TR111222333',
        ]);

        // Sebelum ditambahkan: muncul notifikasi
        $this->assertNotEmpty($dataset->getUnstandardizedWarnings());

        // Admin menambahkan UPT baru ke tabel master data
        Upt::create([
            'kode' => 999,
            'nama' => 'Loka POM di Kota Nusantara',
            'tipe' => 'Loka POM',
        ]);

        // Setelah ditambahkan: notifikasi otomatis hilang
        $warningsSetelah = $dataset->getUnstandardizedWarnings();
        $this->assertEmpty($warningsSetelah);

        $responseShow = $this->actingAs($this->evaluator)->get(route('dataset.show', $dataset));
        $responseShow->assertOk();
        $responseShow->assertDontSee('Pemberitahuan: Ditemukan Kode Belum Terstandarisasi di Master Data');
    }
}
