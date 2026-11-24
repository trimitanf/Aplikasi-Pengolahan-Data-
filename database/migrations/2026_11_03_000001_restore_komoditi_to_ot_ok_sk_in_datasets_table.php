<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kembalikan dataset yang berlabel 'OBA' menjadi 'OT' atau 'OK' (3 komoditi input: OT, OK, SK)
        $obaDatasets = DB::table('datasets')->where('komoditi', 'OBA')->get();

        foreach ($obaDatasets as $dataset) {
            $hasOkRows = DB::table('data_iklan')
                ->where('dataset_id', $dataset->id)
                ->where('jenis_produk', 3)
                ->exists();

            $filenameUpper = strtoupper((string) $dataset->nama_file_asli);
            $isOkFilename = (bool) preg_match('/\bOK\b/', $filenameUpper) || str_contains($filenameUpper, 'KUASI');

            $resolvedKomoditi = ($hasOkRows || $isOkFilename) ? 'OK' : 'OT';

            DB::table('datasets')
                ->where('id', $dataset->id)
                ->update(['komoditi' => $resolvedKomoditi]);
        }
    }

    public function down(): void
    {
        // No-op: komoditi tetap menggunakan 3 kategori standar (OT, OK, SK)
    }
};
