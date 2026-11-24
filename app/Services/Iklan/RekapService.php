<?php

namespace App\Services\Iklan;

use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\TargetIku;
use Carbon\Carbon;

class RekapService
{
    /**
     * Calculate comprehensive Rekap data for a given year and month range.
     */
    public function calculate(int $tahun, int $bulanAwal = 1, ?int $bulanAkhir = null): array
    {
        // Default bulanAkhir to the latest finalized month in the year, or 12
        if ($bulanAkhir === null) {
            $latestMonth = DataIklan::where('periode_tahun', $tahun)
                ->where('is_final', true)
                ->whereNull('deleted_at')
                ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'))
                ->max('periode_bulan')
                ?? Dataset::where('periode_tahun', $tahun)->whereNull('deleted_at')->max('periode_bulan');
            $bulanAkhir = $latestMonth ? (int) $latestMonth : 12;
        }

        // Tanggal update dinamis = waktu evaluasi terbaru dari 6 evaluator atau tanggal dataset terbaru
        $latestIklanUpdate = DataIklan::where('periode_tahun', $tahun)
            ->where('is_final', true)
            ->whereNull('deleted_at')
            ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'))
            ->max('updated_at');

        if ($latestIklanUpdate) {
            $updateDate = Carbon::parse($latestIklanUpdate);
        } else {
            $latestDatasetInYear = Dataset::where('periode_tahun', $tahun)
                ->whereNull('deleted_at')
                ->orderByRaw('COALESCE(finalized_at, uploaded_at, created_at, updated_at) DESC')
                ->first();

            if ($latestDatasetInYear) {
                $latestDate = $latestDatasetInYear->finalized_at
                    ?? $latestDatasetInYear->uploaded_at
                    ?? $latestDatasetInYear->created_at
                    ?? $latestDatasetInYear->updated_at;
                $updateDate = $latestDate ? Carbon::parse($latestDate) : now();
            } else {
                $globalLatest = Dataset::whereNull('deleted_at')
                    ->orderByRaw('COALESCE(finalized_at, uploaded_at, created_at, updated_at) DESC')
                    ->first();
                $latestDate = $globalLatest?->finalized_at ?? $globalLatest?->uploaded_at ?? $globalLatest?->created_at;
                $updateDate = $latestDate ? Carbon::parse($latestDate) : now();
            }
        }

        
        $maxNo = (int) (DataIklan::where('periode_tahun', $tahun)
            ->whereNull('deleted_at')
            ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'))
            ->max('no')
            ?: DataIklan::where('periode_tahun', $tahun)
                ->where('is_final', true)
                ->whereNull('deleted_at')
                ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'))
                ->count());

        
        $baseQuery = DataIklan::where('periode_tahun', $tahun)
            ->whereBetween('periode_bulan', [$bulanAwal, $bulanAkhir])
            ->where('is_final', true)
            ->whereNull('deleted_at')
            ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'))
            ->whereNotNull('hasil_evaluasi');

        // Target IKU from database
        $targetObaRecord = TargetIku::where('tahun', $tahun)->where('kelompok', 'OBA')->first();
        $targetSkRecord = TargetIku::where('tahun', $tahun)->where('kelompok', 'SK')->first();

        $targetOba = $targetObaRecord ? (float) $targetObaRecord->target : 67.00;
        $targetSk = $targetSkRecord ? (float) $targetSkRecord->target : 83.00;

        // Calculate for OBA (jenis_produk IN (1, 3)) and SK (jenis_produk = 2)
        // Tabel OBA merupakan hasil akumulasi dari komoditi Obat Tradisional (OT - kode 1) dan Obat Kuasi (OK - kode 3)
        $oba = $this->calculateCommodityGroup(clone $baseQuery, [1, 3], $targetOba);
        $sk = $this->calculateCommodityGroup(clone $baseQuery, [2], $targetSk, isSk: true);

        // Menunggu Evaluasi count in period
        $menungguEvaluasiCount = DataIklan::where('periode_tahun', $tahun)
            ->whereBetween('periode_bulan', [$bulanAwal, $bulanAkhir])
            ->where('is_final', true)
            ->whereNull('deleted_at')
            ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'))
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
     * Tabel Iklan OBA mengelompokkan dan mengakumulasikan komoditi Obat Tradisional (OT, kode 1) dan Obat Kuasi (OK, kode 3).
     */
    protected function calculateCommodityGroup($query, array $jenisProdukCodes, float $targetIku, bool $isSk = false): array
    {
        $groupQuery = (clone $query)->where(function ($q) use ($jenisProdukCodes, $isSk) {
            $q->whereIn('jenis_produk', $jenisProdukCodes);
            if (! $isSk) {
                // Akumulasi baris yang berasal dari dataset komoditi OT, OK, maupun OBA
                $q->orWhere(function ($sub) {
                    $sub->whereNull('jenis_produk')
                        ->whereHas('dataset', fn ($d) => $d->whereIn('komoditi', ['OT', 'OK', 'OBA']));
                });
            } else {
                $q->orWhere(function ($sub) {
                    $sub->whereNull('jenis_produk')
                        ->whereHas('dataset', fn ($d) => $d->where('komoditi', 'SK'));
                });
            }
        });

        $summaryStats = (clone $groupQuery)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN hasil_evaluasi = 1 THEN 1 ELSE 0 END) as mk')
            ->first();

        $total = (int) ($summaryStats->total ?? 0);
        $mk = (int) ($summaryStats->mk ?? 0);
        $tmk = $total - $mk;
        $persenMk = $total > 0 ? ($mk / $total) * 100 : 0;
        $persenTmk = $total > 0 ? ($tmk / $total) * 100 : 0;

        // 1. Rincian Asal Produk (1 query GROUP BY)
        $asalGrouped = $this->getGroupedCounts($groupQuery, 'asal_produk');
        $asalProduk = [
            'lokal' => $this->resolveGroupedCount($asalGrouped, 1),
            'impor' => $this->resolveGroupedCount($asalGrouped, 2),
            'lisensi' => $this->resolveGroupedCount($asalGrouped, 3),
        ];

        // 2. Rincian Kategori Media (1 query GROUP BY)
        $mediaGrouped = $this->getGroupedCounts($groupQuery, 'kategori_media');
        $kategoriMedia = [
            'audio' => $this->resolveGroupedCount($mediaGrouped, 1),
            'visual' => $this->resolveGroupedCount($mediaGrouped, 2),
            'audiovisual' => $this->resolveGroupedCount($mediaGrouped, 3),
        ];

        // 3. Rincian Kategori Iklan (1 query GROUP BY)
        $iklanGrouped = $this->getGroupedCounts($groupQuery, 'kategori_iklan');
        if (! $isSk) {
            // OBA 8 baris
            $kategoriIklan = [
                'ecommerce' => $this->resolveGroupedCount($iklanGrouped, 1),
                'website' => $this->resolveGroupedCount($iklanGrouped, 2),
                'medsos' => $this->resolveGroupedCount($iklanGrouped, 3),
                'internet_lain' => $this->resolveGroupedCount($iklanGrouped, 4),
                'tv_nasional' => $this->resolveGroupedCount($iklanGrouped, 5),
                'penyiaran_lokal' => $this->resolveGroupedCount($iklanGrouped, 6),
                'luar_ruang' => $this->resolveGroupedCount($iklanGrouped, 7),
                'cetak' => $this->resolveGroupedCount($iklanGrouped, 8),
            ];
        } else {
            // SK 7 baris: TV Nasional menggabungkan kategori 5 dan 6
            $kategoriIklan = [
                'ecommerce' => $this->resolveGroupedCount($iklanGrouped, 1),
                'website' => $this->resolveGroupedCount($iklanGrouped, 2),
                'medsos' => $this->resolveGroupedCount($iklanGrouped, 3),
                'internet_lain' => $this->resolveGroupedCount($iklanGrouped, 4),
                'tv_nasional' => $this->resolveGroupedCount($iklanGrouped, [5, 6]),
                'luar_ruang' => $this->resolveGroupedCount($iklanGrouped, 7),
                'cetak' => $this->resolveGroupedCount($iklanGrouped, 8),
            ];
        }

        // 4. Rincian Pembuat Iklan (1 query GROUP BY)
        $pembuatGrouped = $this->getGroupedCounts($groupQuery, 'pembuat_iklan');
        $pembuatIklan = [
            'pelaku_usaha' => $this->resolveGroupedCount($pembuatGrouped, 1),
            'perseorangan' => $this->resolveGroupedCount($pembuatGrouped, 2),
        ];

        // 5 & 6. Pencantuman & Kesesuaian NIE (E-Commerce: kategori_iklan = 1) (1 query)
        $totalEcommerce = $kategoriIklan['ecommerce']['total'];
        $nieStats = (clone $groupQuery)
            ->where('kategori_iklan', 1)
            ->selectRaw('
                SUM(CASE WHEN pencantuman_nie = 1 THEN 1 ELSE 0 END) as nie_ada,
                SUM(CASE WHEN pencantuman_nie = 1 AND kesesuaian_nie = 1 THEN 1 ELSE 0 END) as nie_sesuai
            ')
            ->first();

        $nieAda = (int) ($nieStats->nie_ada ?? 0);
        $nieTidakAda = $totalEcommerce - $nieAda;
        $nieSesuai = (int) ($nieStats->nie_sesuai ?? 0);
        $nieTidakSesuai = $nieAda - $nieSesuai;

        // 7. Rincian TMK (hanya baris TMK: hasil_evaluasi = 2) (1 query GROUP BY)
        $tmkTotal = $tmk;
        $tmkGrouped = (clone $groupQuery)
            ->where('hasil_evaluasi', 2)
            ->whereNotNull('rincian_tmk')
            ->selectRaw('rincian_tmk, COUNT(*) as total')
            ->groupBy('rincian_tmk')
            ->pluck('total', 'rincian_tmk');

        $rincianTie = (int) ($tmkGrouped[7] ?? 0);
        $rincianTestimoni = (int) ($tmkGrouped[1] ?? 0) + (int) ($tmkGrouped[4] ?? 0);
        $rincianHadiah = (int) ($tmkGrouped[2] ?? 0) + (int) ($tmkGrouped[5] ?? 0);
        $rincianKlaim = (int) ($tmkGrouped[3] ?? 0) + (int) ($tmkGrouped[6] ?? 0);

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
     * Helper agregasi GROUP BY per kolom dimensi untuk efisiensi query.
     */
    protected function getGroupedCounts($query, string $field)
    {
        return (clone $query)
            ->whereNotNull($field)
            ->selectRaw("{$field} as code, COUNT(*) as total, SUM(CASE WHEN hasil_evaluasi = 1 THEN 1 ELSE 0 END) as mk")
            ->groupBy($field)
            ->get()
            ->keyBy('code');
    }

    /**
     * Ambil hasil perhitungan dari koleksi agregasi GROUP BY untuk 1 atau beberapa kode.
     */
    protected function resolveGroupedCount($grouped, int|array $value): array
    {
        $codes = is_array($value) ? $value : [$value];
        $tot = 0;
        $m = 0;
        foreach ($codes as $code) {
            $row = $grouped->get($code);
            $tot += (int) ($row->total ?? 0);
            $m += (int) ($row->mk ?? 0);
        }

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
     * Get kelengkapan status for 12 months x 2 commodities (OBA, SK).
     */
    public function getKelengkapanPeriode(int $tahun): array
    {
        $finalized = Dataset::where('periode_tahun', $tahun)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereIn('status', ['Final', 'Sudah Diproses'])
                    ->orWhereHas('dataIklan', fn ($di) => $di->where('is_final', true)->whereNull('deleted_at'));
            })
            ->select('periode_bulan', 'komoditi')
            ->get();

        $matrix = [];
        for ($m = 1; $m <= 12; $m++) {
            $matrix[$m] = [
                'OBA' => $finalized->contains(fn ($d) => $d->periode_bulan === $m && in_array($d->komoditi, ['OBA', 'OT', 'OK'])),
                'SK' => $finalized->contains(fn ($d) => $d->periode_bulan === $m && $d->komoditi === 'SK'),
            ];
        }

        return $matrix;
    }
}
