<?php

namespace Tests\Unit;

use App\Models\DataMentahPenandaan;
use App\Models\DataPenandaan;
use App\Models\DatasetPenandaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenandaanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dataset_penandaan_relasi_dan_siklus_hidup(): void
    {
        $user = User::create([
            'name' => 'Staf Penandaan',
            'username' => 'eval_test_penandaan',
            'password' => bcrypt('password123'),
            'role' => 'evaluator_penandaan',
            'is_active' => true,
        ]);

        $dataset = DatasetPenandaan::create([
            'nama_file_asli' => 'SIPT_Penandaan_Nov_2026.xlsx',
            'path_file' => 'private/penandaan/test.xlsx',
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 11,
            'uploaded_by' => $user->id,
            'status' => 'Diunggah',
        ]);

        $this->assertDatabaseHas('datasets_penandaan', [
            'id' => $dataset->id,
            'nama_file_asli' => 'SIPT_Penandaan_Nov_2026.xlsx',
        ]);

        $this->assertTrue($dataset->canBeEditedBy($user));
        $this->assertEquals($user->id, $dataset->uploader->id);

        // Buat Data Mentah Penandaan
        $mentah = DataMentahPenandaan::create([
            'dataset_penandaan_id' => $dataset->id,
            'baris_ke' => 1,
            'upt' => 'BBPOM di Bandung',
            'nama_produk' => 'Jamu Herbal X',
            'evaluator_id' => $user->id,
            'kualitas_laporan' => 1,
            'ketepatan_waktu' => 1,
        ]);

        $this->assertEquals(1, $dataset->dataMentah()->count());
        $this->assertEquals($dataset->id, $mentah->dataset->id);
        $this->assertEquals($user->id, $mentah->evaluator->id);

        // Buat Data Terstandarisasi Penandaan
        $dataPenandaan = DataPenandaan::create([
            'dataset_penandaan_id' => $dataset->id,
            'data_mentah_penandaan_id' => $mentah->id,
            'evaluator_id' => $user->id,
            'upt' => 101,
            'periode_tahun' => 2026,
            'periode_bulan' => 11,
            'jenis_produk' => 1,
            'hasil_evaluasi' => 1,
            'is_final' => false,
        ]);

        $this->assertEquals(1, $dataset->dataPenandaan()->count());
        $this->assertEquals($mentah->id, $dataPenandaan->dataMentah->id);
    }
}
