<?php

use App\Models\PemetaanNilai;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $newMappings = [
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT BAHAN ALAM', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBA', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'BAHAN ALAM', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'TRADISIONAL', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'JAMU', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'HERBAL', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'FITOFARMAKA', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'SUPLEMEN', 'kode' => 2],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'KUASI', 'kode' => 3],
        ];

        foreach ($newMappings as $item) {
            PemetaanNilai::updateOrCreate(
                [
                    'kolom_sumber' => $item['kolom_sumber'],
                    'nilai_sipt' => $item['nilai_sipt'],
                ],
                [
                    'grup' => $item['grup'],
                    'kode' => $item['kode'],
                    'is_active' => true,
                ]
            );
        }
    }

    public function down(): void
    {
        PemetaanNilai::where('grup', 'jenis_produk')
            ->whereIn('nilai_sipt', [
                'OBAT BAHAN ALAM', 'OBA', 'BAHAN ALAM', 'TRADISIONAL',
                'JAMU', 'HERBAL', 'FITOFARMAKA', 'SUPLEMEN', 'KUASI',
            ])
            ->delete();
    }
};
