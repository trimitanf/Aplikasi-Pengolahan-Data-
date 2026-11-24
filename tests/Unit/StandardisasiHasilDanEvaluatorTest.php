<?php

namespace Tests\Unit;

use App\Models\DataMentah;
use App\Models\Dataset;
use App\Services\StandardizationService;
use Tests\TestCase;

class StandardisasiHasilDanEvaluatorTest extends TestCase
{
    /**
     * Memastikan standardizeHasil mengecek eksplisit 'TIDAK' atau 'TMK' lebih dahulu
     * sehingga 'TIDAK MEMENUHI KETENTUAN' tidak false-positive menjadi MK (1).
     */
    public function test_standardize_hasil_mendeteksi_tmk_sebelum_memenuhi(): void
    {
        $service = app(StandardizationService::class);

        // Kasus-kasus TMK (2)
        $this->assertEquals(2, $service->standardizeHasil('TIDAK MEMENUHI KETENTUAN'));
        $this->assertEquals(2, $service->standardizeHasil('tidak memenuhi ketentuan'));
        $this->assertEquals(2, $service->standardizeHasil('TMK'));
        $this->assertEquals(2, $service->standardizeHasil('TMK (Klaim berlebihan)'));
        $this->assertEquals(2, $service->standardizeHasil('2'));

        // Kasus-kasus MK (1)
        $this->assertEquals(1, $service->standardizeHasil('MEMENUHI KETENTUAN'));
        $this->assertEquals(1, $service->standardizeHasil('memenuhi ketentuan'));
        $this->assertEquals(1, $service->standardizeHasil('MK'));
        $this->assertEquals(1, $service->standardizeHasil('1'));

        // Kasus kosong
        $this->assertNull($service->standardizeHasil(null));
        $this->assertNull($service->standardizeHasil(''));
        $this->assertNull($service->standardizeHasil('-'));
    }

    /**
     * Memastikan standardizeRow menyertakan kolom 'evaluator_id' dari data_mentah.
     */
    public function test_standardize_row_menyertakan_evaluator_id(): void
    {
        $service = app(StandardizationService::class);

        $dataset = new Dataset([
            'id' => 999,
            'komoditi' => 'OT',
            'periode_tahun' => 2026,
            'periode_bulan' => 10,
        ]);

        $raw = new DataMentah([
            'id' => 100,
            'dataset_id' => 999,
            'evaluator_id' => 42,
            'nomor_baris' => 1,
            'upt' => 'Balai Besar POM di Jakarta',
            'tanggal_pengambilan_iklan' => '10/10/2026',
            'kesimpulan_upt' => 'TIDAK MEMENUHI KETENTUAN',
            'kesesuaian_hasil_evaluasi' => 'SESUAI',
        ]);

        $standardized = $service->standardizeRow($dataset, $raw);

        $this->assertArrayHasKey('evaluator_id', $standardized);
        $this->assertEquals(42, $standardized['evaluator_id']);
        $this->assertEquals(2, $standardized['hasil_evaluasi']);
    }

    /**
     * Memastikan helper resolveTextToCode mendahulukan kata negatif sebelum positif.
     */
    public function test_resolve_text_to_code_anti_false_positive(): void
    {
        $service = app(StandardizationService::class);

        $result = $service->resolveTextToCode(
            text: 'TIDAK MEMENUHI KETENTUAN',
            negativeKeywords: ['TIDAK' => 2, 'TMK' => 2],
            positiveKeywords: ['MEMENUHI' => 1, 'MK' => 1]
        );

        $this->assertEquals(2, $result);
    }
}
