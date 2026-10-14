<?php

namespace App\Services;

use App\Models\DataIklan;
use App\Models\Dataset;
use App\Models\TargetIku;
use Illuminate\Support\Facades\DB;

class RekapService
{
    /**
     * Calculate comprehensive Rekap data for a given year and month range.
     */
    public function calculate(int $tahun, int $bulanAwal = 1, ?int $bulanAkhir = null): array
    {
        // Default bulanAkhir to the latest finalized month in the year, or 12
        if ($bulanAkhir === null) {
            $latestMonth = Dataset::where('periode_tahun', $tahun)
                ->where('status', 'Final')
                ->max('periode_bulan');
            $bulanAkhir = $latestMonth ? (int)$latestMonth : 12;
        }

        // Tanggal update = tanggal finalisasi dataset terakhir
        $latestFinal = Dataset::where('periode_tahun', $tahun)
            ->where('status', 'Final')
            ->max('finalized_at');
        $updateDate = $latestFinal ? \Carbon\Carbon::parse($latestFinal) : now();

        // Highest official NO
        $maxNo = DataIklan::where('periode_tahun', $tahun)
            ->where('is_final', true)
            ->max('no') ?? 0;

        // Base query for evaluated finalized rows
        $baseQuery = DataIklan::where('periode_tahun', $tahun)
            ->whereBetween('periode_bulan', [$bulanAwal, $bulanAkhir])
            ->where('is_final', true)
            ->whereNotNull('hasil_evaluasi');

        // Target IKU from database
        $targetObaRecord = TargetIku::where('tahun', $tahun)->where('kelompok', 'OBA')->first();
        $targetSkRecord = TargetIku::where('tahun', $tahun)->where('kelompok', 'SK')->first();

        $targetOba = $targetObaRecord ? (float) $targetObaRecord->target : 67.00;
        $targetSk = $targetSkRecord ? (float) $targetSkRecord->target : 83.00;

        // Calculate for OBA (jenis_produk IN (1, 3)) and SK (jenis_produk = 2)
        $oba = $this->calculateCommodityGroup(clone $baseQuery, [1, 3], $targetOba);
        $sk = $this->calculateCommodityGroup(clone $baseQuery, [2], $targetSk, isSk: true);

        // Menunggu Evaluasi count in period
        $menungguEvaluasiCount = DataIklan::where('periode_tahun', $tahun)
            ->whereBetween('periode_bulan', [$bulanAwal, $bulanAkhir])
            ->where('is_final', true)
            ->whereNull('hasil_evaluasi')
            ->count();

        // Kelengkapan Periode (Bulan 1..12 x OT, OK, SK)
        $kelengkapan = $this->getKelengkapanPeriode($tahun);

        return [
            'tahun' => $tahun,
            'bulan_awal' => $bulanAwal,
            'bulan_akhir' => $bulanAkhir,
            'tanggal_update' => $updateDate,
            'max_no' => $maxNo,
            'menunggu_evaluasi_count' => $menungguEvaluasiCount,
            'oba' => $oba,
            'sk' => $sk,
            'kelengkapan' => $kelengkapan,
        ];
    }

    /**
     * Calculate statistics for a commodity group (OBA or SK).
     */
    protected function calculateCommodityGroup($query, array $jenisProdukCodes, float $targetIku, bool $isSk = false): array
    {
        $groupQuery = (clone $query)->whereIn('jenis_produk', $jenisProdukCodes);

        $total = (clone $groupQuery)->count();
        $mk = (clone $groupQuery)->where('hasil_evaluasi', 1)->count();
        $tmk = $total - $mk;
        $persenMk = $total > 0 ? ($mk / $total) * 100 : 0;
        $persenTmk = $total > 0 ? ($tmk / $total) * 100 : 0;

        // 1. Rincian Asal Produk
        $asalProduk = [
            'lokal' => $this->getRowCounts(clone $groupQuery, 'asal_produk', 1),
            'impor' => $this->getRowCounts(clone $groupQuery, 'asal_produk', 2),
            'lisensi' => $this->getRowCounts(clone $groupQuery, 'asal_produk', 3),
        ];

        // 2. Rincian Kategori Media
        $kategoriMedia = [
            'audio' => $this->getRowCounts(clone $groupQuery, 'kategori_media', 1),
            'visual' => $this->getRowCounts(clone $groupQuery, 'kategori_media', 2),
            'audiovisual' => $this->getRowCounts(clone $groupQuery, 'kategori_media', 3),
        ];

        // 3. Rincian Kategori Iklan
        if (! $isSk) {
            // OBA 8 baris
            $kategoriIklan = [
                'ecommerce' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 1),
                'website' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 2),
                'medsos' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 3),
                'internet_lain' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 4),
                'tv_nasional' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 5),
                'penyiaran_lokal' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 6),
                'luar_ruang' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 7),
                'cetak' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 8),
            ];
        } else {
            // SK 7 baris: TV Nasional menggabungkan kategori 5 dan 6
            $kategoriIklan = [
                'ecommerce' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 1),
                'website' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 2),
                'medsos' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 3),
                'internet_lain' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 4),
                'tv_nasional' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', [5, 6]),
                'luar_ruang' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 7),
                'cetak' => $this->getRowCounts(clone $groupQuery, 'kategori_iklan', 8),
            ];
        }

        // 4. Rincian Pembuat Iklan
        $pembuatIklan = [
            'pelaku_usaha' => $this->getRowCounts(clone $groupQuery, 'pembuat_iklan', 1),
            'perseorangan' => $this->getRowCounts(clone $groupQuery, 'pembuat_iklan', 2),
        ];

        // 5. Pencantuman NIE (E-Commerce: kategori_iklan = 1)
        $totalEcommerce = $kategoriIklan['ecommerce']['total'];
        $nieAda = (clone $groupQuery)->where('kategori_iklan', 1)->where('pencantuman_nie', 1)->count();
        $nieTidakAda = $totalEcommerce - $nieAda;

        // 6. Kesesuaian NIE (E-Commerce: kategori_iklan = 1 & pencantuman_nie = 1)
        $nieSesuai = (clone $groupQuery)->where('kategori_iklan', 1)->where('pencantuman_nie', 1)->where('kesesuaian_nie', 1)->count();
        $nieTidakSesuai = $nieAda - $nieSesuai;

        // 7. Rincian TMK (hanya baris TMK: hasil_evaluasi = 2)
        $tmkQuery = (clone $groupQuery)->where('hasil_evaluasi', 2);
        $tmkTotal = $tmk;

        $rincianTie = (clone $tmkQuery)->where('rincian_tmk', 7)->count();
        $rincianTestimoni = (clone $tmkQuery)->whereIn('rincian_tmk', [1, 4])->count();
        $rincianHadiah = (clone $tmkQuery)->whereIn('rincian_tmk', [2, 5])->count();
        $rincianKlaim = (clone $tmkQuery)->whereIn('rincian_tmk', [3, 6])->count();

        // Lain-lain = Total TMK - (a + b + c + d)
        $rincianLainLain = $tmkTotal - ($rincianTie + $rincianTestimoni + $rincianHadiah + $rincianKlaim);
        $rincianTotalCheck = $rincianTie + $rincianTestimoni + $rincianHadiah + $rincianKlaim + $rincianLainLain;

        $rincianTmk = [
            'tie' => [
                'total' => $rincianTie,
                'persen' => $tmkTotal > 0 ? ($rincianTie / $tmkTotal) * 100 : 0,
            ],
            'testimoni' => [
                'total' => $rincianTestimoni,
                'persen' => $tmkTotal > 0 ? ($rincianTestimoni / $tmkTotal) * 100 : 0,
            ],
            'hadiah' => [
                'total' => $rincianHadiah,
                'persen' => $tmkTotal > 0 ? ($rincianHadiah / $tmkTotal) * 100 : 0,
            ],
            'klaim_berlebihan' => [
                'total' => $rincianKlaim,
                'persen' => $tmkTotal > 0 ? ($rincianKlaim / $tmkTotal) * 100 : 0,
            ],
            'lain_lain' => [
                'total' => $rincianLainLain,
                'persen' => $tmkTotal > 0 ? ($rincianLainLain / $tmkTotal) * 100 : 0,
            ],
            'total' => [
                'total' => $rincianTotalCheck,
                'persen' => $tmkTotal > 0 ? ($rincianTotalCheck / $tmkTotal) * 100 : 0,
            ],
        ];

        // 8. IKU Calculation
        $capaianIku = $persenMk;
        $persentaseCapaianIku = $targetIku > 0 ? ($capaianIku / $targetIku) * 100 : 0;

        return [
            'total_pengawasan' => [
                'total' => $total,
                'mk' => $mk,
                'persen_mk' => $persenMk,
                'tmk' => $tmk,
                'persen_tmk' => $persenTmk,
            ],
            'asal_produk' => $asalProduk,
            'kategori_media' => $kategoriMedia,
            'kategori_iklan' => $kategoriIklan,
            'pembuat_iklan' => $pembuatIklan,
            'pencantuman_nie' => [
                'ada' => $nieAda,
                'tidak_ada' => $nieTidakAda,
            ],
            'kesesuaian_nie' => [
                'sesuai' => $nieSesuai,
                'tidak_sesuai' => $nieTidakSesuai,
            ],
            'rincian_tmk' => $rincianTmk,
            'target_iku' => $targetIku,
            'capaian_iku' => $capaianIku,
            'persentase_capaian_iku' => $persentaseCapaianIku,
        ];
    }

    /**
     * Helper to get Total, MK, %MK, TMK, %TMK for a field and value.
     */
    protected function getRowCounts($query, string $field, int|array $value): array
    {
        $filtered = is_array($value)
            ? (clone $query)->whereIn($field, $value)
            : (clone $query)->where($field, $value);

        $tot = (clone $filtered)->count();
        $m = (clone $filtered)->where('hasil_evaluasi', 1)->count();
        $t = $tot - $m;
        $pMk = $tot > 0 ? ($m / $tot) * 100 : 0;
        $pTmk = $tot > 0 ? ($t / $tot) * 100 : 0;

        return [
            'total' => $tot,
            'mk' => $m,
            'persen_mk' => $pMk,
            'tmk' => $t,
            'persen_tmk' => $pTmk,
        ];
    }

    /**
     * Get kelengkapan status for 12 months x 3 commodities (OT, OK, SK).
     */
    public function getKelengkapanPeriode(int $tahun): array
    {
        $finalized = Dataset::where('periode_tahun', $tahun)
            ->where('status', 'Final')
            ->select('periode_bulan', 'komoditi')
            ->get();

        $matrix = [];
        for ($m = 1; $m <= 12; $m++) {
            $matrix[$m] = [
                'OT' => $finalized->contains(fn ($d) => $d->periode_bulan === $m && $d->komoditi === 'OT'),
                'OK' => $finalized->contains(fn ($d) => $d->periode_bulan === $m && $d->komoditi === 'OK'),
                'SK' => $finalized->contains(fn ($d) => $d->periode_bulan === $m && $d->komoditi === 'SK'),
            ];
        }

        return $matrix;
    }
}
