<?php

use App\Models\PemetaanNilai;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah dataset komoditi 'OT' menjadi 'OBA'
        DB::table('datasets')
            ->where('komoditi', 'OT')
            ->update(['komoditi' => 'OBA']);

        // 2. Ubah nama master kode jenis_produk kode 1 menjadi 'OBA'
        DB::table('master_kodes')
            ->where('grup', 'jenis_produk')
            ->where('kode', 1)
            ->update(['nama' => 'OBA']);

        // 3. Tambahkan aturan pemetaan nilai SIPT untuk OBA & OT ke kode 1
        $mappings = [
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT BAHAN ALAM', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT BAHAN ALAM (OBA)', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBA', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'BAHAN ALAM', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT TRADISIONAL', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT TRADISIONAL (OT)', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OT', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'TRADISIONAL', 'kode' => 1],
        ];

        foreach ($mappings as $item) {
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
        DB::table('datasets')
            ->where('komoditi', 'OBA')
            ->update(['komoditi' => 'OT']);

        DB::table('master_kodes')
            ->where('grup', 'jenis_produk')
            ->where('kode', 1)
            ->update(['nama' => 'OT']);
    }
};
