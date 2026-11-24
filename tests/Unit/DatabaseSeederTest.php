<?php

namespace Tests\Unit;

use App\Models\MasterKode;
use App\Models\TargetIku;
use App\Models\Upt;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_83_upt(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(83, Upt::count());
        $this->assertEquals('Balai Besar POM di Banda Aceh', Upt::where('kode', 1)->value('nama'));
        $this->assertEquals('Loka POM di Kabupaten Bone', Upt::where('kode', 83)->value('nama'));
    }

    public function test_seeder_membuat_semua_grup_master_kode(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expectedGroups = [
            'bulan',
            'jenis_produk',
            'asal_produk',
            'klaim',
            'kategori_media',
            'kategori_iklan',
            'nama_media',
            'pencantuman_nie',
            'kesesuaian_nie',
            'hasil_evaluasi',
            'rincian_tmk',
            'jenis_tmk',
            'pembuat_iklan',
            'kualitas_laporan',
            'ketepatan_waktu',
            'kesesuaian_evaluasi',
        ];

        foreach ($expectedGroups as $grup) {
            $this->assertTrue(
                MasterKode::where('grup', $grup)->exists(),
                "Grup master kode {$grup} tidak ditemukan di database."
            );
        }

        // Cek kode khusus
        $this->assertEquals('OBA', MasterKode::where('grup', 'jenis_produk')->where('kode', 1)->value('nama'));
        $this->assertEquals('SK', MasterKode::where('grup', 'jenis_produk')->where('kode', 2)->value('nama'));
        $this->assertEquals('OK', MasterKode::where('grup', 'jenis_produk')->where('kode', 3)->value('nama'));

        $this->assertEquals('KRITIS', MasterKode::where('grup', 'jenis_tmk')->where('kode', 1)->value('nama'));
        $this->assertEquals('MAYOR', MasterKode::where('grup', 'jenis_tmk')->where('kode', 2)->value('nama'));
        $this->assertEquals('MINOR', MasterKode::where('grup', 'jenis_tmk')->where('kode', 3)->value('nama'));
    }

    public function test_seeder_membuat_target_iku_2026_default(): void
    {
        $this->seed(DatabaseSeeder::class);

        $oba = TargetIku::where('tahun', 2026)->where('kelompok', 'OBA')->first();
        $sk = TargetIku::where('tahun', 2026)->where('kelompok', 'SK')->first();

        $this->assertNotNull($oba);
        $this->assertNotNull($sk);
        $this->assertEquals(67.00, $oba->target);
        $this->assertEquals(83.00, $sk->target);
    }

    public function test_seeder_membuat_akun_admin_dan_enam_evaluator(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(7, User::count());
        
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->username);
        $this->assertTrue($admin->isAdmin());

        $evaluators = User::where('role', 'evaluator')->get();
        $this->assertCount(6, $evaluators);
    }
}
