<?php

namespace Tests\Feature;

use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_report_evaluator_hanya_bisa_diakses_admin_dan_menampilkan_rekap_beserta_ekspor_excel(): void
    {
        $admin = User::create([
            'name' => 'Administrator Utama',
            'username' => 'admin_utama',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $evaluator1 = User::create([
            'name' => 'Evaluator Andi',
            'username' => 'evaluator_andi',
            'password' => bcrypt('password'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $evaluator2 = User::create([
            'name' => 'Evaluator Budi',
            'username' => 'evaluator_budi',
            'password' => bcrypt('password'),
            'role' => 'evaluator',
            'is_active' => true,
        ]);

        $dataset = Dataset::create([
            'nama_file_asli' => 'dataset_ot_nov.xlsx',
            'path_file' => 'private/datasets/dataset_ot_nov.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 11,
            'status' => 'Valid',
            'jumlah_baris' => 2,
            'uploaded_by' => $evaluator1->id,
        ]);

        DataMentah::create([
            'dataset_id' => $dataset->id,
            'evaluator_id' => $evaluator1->id,
            'nomor_baris' => 1,
            'no' => '1',
            'upt' => 'Balai Besar POM di Bandung',
            'tanggal_evaluasi_pusat' => '03 November 2026',
            'nama_produk' => 'Jamu Sehat A',
            'nomor_izin_edar' => 'TR123456781',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
            'kesesuaian_evaluasi' => 1,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        DataMentah::create([
            'dataset_id' => $dataset->id,
            'evaluator_id' => $evaluator1->id,
            'nomor_baris' => 3,
            'no' => '3',
            'upt' => 'Balai Besar POM di Semarang',
            'tanggal_evaluasi_pusat' => '03 November 2026',
            'nama_produk' => 'Jamu Sehat C',
            'nomor_izin_edar' => 'TR123456783',
            'kesimpulan_pusat' => 'Memenuhi Ketentuan',
            'kesesuaian_evaluasi' => 1,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        DataMentah::create([
            'dataset_id' => $dataset->id,
            'evaluator_id' => $evaluator2->id,
            'nomor_baris' => 2,
            'no' => '2',
            'upt' => 'Balai Besar POM di Surabaya',
            'tanggal_evaluasi_pusat' => '03 November 2026',
            'nama_produk' => 'Jamu Sehat B',
            'nomor_izin_edar' => 'TR123456782',
            'kesimpulan_pusat' => 'Tidak Memenuhi Ketentuan',
            'kesesuaian_evaluasi' => 1,
            'kualitas_laporan' => 2,
            'ketepatan_waktu' => null,
        ]);

        // 1. Evaluator biasa ditolak aksesnya (403)
        $forbiddenResponse = $this->actingAs($evaluator1)->get(route('report-evaluator.index'));
        $forbiddenResponse->assertStatus(403);

        // 2. Admin dapat mengakses halaman Report Evaluator
        $response = $this->actingAs($admin)->get(route('report-evaluator.index'));
        $response->assertOk();
        $response->assertSee('Rekapitulasi Pengerjaan Evaluator Berdasarkan Pembagian UPT', false);
        $response->assertSee('Export Excel (.xlsx)', false);
        $response->assertSee('Evaluator Andi');
        $response->assertSee('Evaluator Budi');
        $response->assertSee('Balai Besar POM di Bandung');
        $response->assertSee('Balai Besar POM di Surabaya');
        $response->assertDontSee('BARIS DIEVALUASI');
        $response->assertDontSee('Lihat Data');
        $response->assertDontSee('UPT Dikerjakan');
        $response->assertDontSee('/1 Selesai');
        $response->assertSee('1.');
        $response->assertSee('2.');
        $response->assertSee('Balai Besar POM di Semarang');
        $response->assertSee('>UPT</th>', false);
        $response->assertDontSee('>Progres Evaluasi', false);
        $response->assertDontSee('2 Evaluator');
        $response->assertDontSee('3 Evaluator');

        // 3. Filter spesifik berdasarkan Evaluator Budi
        $filteredResponse = $this->actingAs($admin)->get(route('report-evaluator.index', [
            'evaluator_id' => $evaluator2->id,
        ]));
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Balai Besar POM di Surabaya');
        $filteredResponse->assertDontSee('Balai Besar POM di Bandung');

        // 4. Export Excel Report Evaluator oleh Admin
        $exportResponse = $this->actingAs($admin)->get(route('report-evaluator.export'));
        $exportResponse->assertOk();
    }
}
