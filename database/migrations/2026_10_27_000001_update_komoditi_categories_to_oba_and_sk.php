<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pertahankan komoditi dataset pada 3 kategori utama: OT, OK, SK
        // (Rekapitulasi OBA dihitung sebagai gabungan dari OT dan OK pada RekapService)
        DB::table('datasets')
            ->where('komoditi', 'OBA')
            ->update(['komoditi' => 'OT']);
    }

    public function down(): void
    {
        // No-op
    }
};
