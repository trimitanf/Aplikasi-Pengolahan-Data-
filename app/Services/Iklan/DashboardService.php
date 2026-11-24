<?php

namespace App\Services\Iklan;

use App\Models\DataIklan;
use App\Models\Dataset;
use App\Models\TargetIku;
use App\Models\Upt;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public const CACHE_VERSION_KEY = 'dashboard_cache_version';

    /**
     * Mendapatkan nomor versi cache dashboard saat ini.
     */
    public static function getCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    /**
     * Menambah versi cache dashboard agar cache lama langsung diinvalidation secara konsisten.
     */
    public static function bumpCacheVersion(): int
    {
        $current = static::getCacheVersion();
        $next = $current + 1;
        Cache::forever(self::CACHE_VERSION_KEY, $next);

        return $next;
    }

    /**
     * Get complete dashboard metrics for given year, commodity, and optional UPT (cached for 5 minutes).
     */
    public function getMetrics(int $tahun, string $komoditi = 'SEMUA', ?int $uptKode = null): array
    {
        $version = static::getCacheVersion();
        $cacheKey = "dashboard_metrics_v{$version}_{$tahun}_{$komoditi}_" . ($uptKode ?? 'all');

        $metrics = Cache::remember($cacheKey, 300, function () use ($tahun, $komoditi, $uptKode) {
            return $this->computeMetrics($tahun, $komoditi, $uptKode);
        });

        // Selalu pasang daftar UPT live agar tidak terdegradasi menjadi __PHP_Incomplete_Class oleh serialisasi cache
        $metrics['upt_list'] = Upt::orderBy('kode')->get(['kode', 'nama']);

        return $metrics;
    }

    /**
     * Compute complete dashboard metrics for given year, commodity, and optional UPT.
     */
    public function computeMetrics(int $tahun, string $komoditi = 'SEMUA', ?int $uptKode = null): array
    {
        // 1. Base query for finalized data in the year
        $baseQuery = DataIklan::where('periode_tahun', $tahun)
            ->where('is_final', true)
            ->whereNull('deleted_at')
            ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'));

        if ($uptKode !== null) {
            $baseQuery->where('upt', $uptKode);
        }

        // Clone for commodity filtering
        $filteredQuery = clone $baseQuery;
        if ($komoditi === 'OT' || $komoditi === 'OBA') {
            $filteredQuery->where('jenis_produk', 1);
        } elseif ($komoditi === 'OK') {
            $filteredQuery->where('jenis_produk', 3);
        } elseif ($komoditi === 'SK') {
            $filteredQuery->where('jenis_produk', 2);
        }

        // 2. Evaluated records query (non-null hasil_evaluasi)
        $evalQuery = (clone $filteredQuery)->whereNotNull('hasil_evaluasi');

        // Total Counts & KPI
        $totalFinal = (clone $filteredQuery)->count();
        $menungguEvaluasi = (clone $filteredQuery)->whereNull('hasil_evaluasi')->count();
        $totalPengawasan = (clone $evalQuery)->count();
        $mkCount = (clone $evalQuery)->where('hasil_evaluasi', 1)->count();
        $tmkCount = (clone $evalQuery)->where('hasil_evaluasi', 2)->count();

        $persenMk = $totalPengawasan > 0 ? ($mkCount / $totalPengawasan) * 100 : 0;
        $persenTmk = $totalPengawasan > 0 ? ($tmkCount / $totalPengawasan) * 100 : 0;

        // 3. Target IKU & Realisasi
        // Target IKU diambil dinamis berdasarkan tahun filter dashboard dari master data Target IKU yang diinput admin.
        // Jika target tahun bersangkutan belum diinput, sistem secara cerdas menggunakan target tahun sebelumnya atau default resmi (OBA: 67%, SK: 83%).
        $targetObaRecord = TargetIku::where('tahun', $tahun)->where('kelompok', 'OBA')->first()
            ?? TargetIku::where('kelompok', 'OBA')->where('tahun', '<=', $tahun)->orderByDesc('tahun')->first()
            ?? TargetIku::where('kelompok', 'OBA')->latest('tahun')->first();

        $targetSkRecord = TargetIku::where('tahun', $tahun)->where('kelompok', 'SK')->first()
            ?? TargetIku::where('kelompok', 'SK')->where('tahun', '<=', $tahun)->orderByDesc('tahun')->first()
            ?? TargetIku::where('kelompok', 'SK')->latest('tahun')->first();

        $targetOba = $targetObaRecord ? (float) $targetObaRecord->target : 67.00;
        $targetSk = $targetSkRecord ? (float) $targetSkRecord->target : 83.00;

        // Specific OBA performance (Mandiri OT/OBA: jenis_produk = 1)
        $obaQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('jenis_produk', 1)
                ->orWhere(fn ($sq) => $sq->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->whereIn('komoditi', ['OBA', 'OT'])));
        })->whereNotNull('hasil_evaluasi');
        $obaTotal = (clone $obaQuery)->count();
        $obaMk = (clone $obaQuery)->where('hasil_evaluasi', 1)->count();
        $obaPersenMk = $obaTotal > 0 ? ($obaMk / $obaTotal) * 100 : 0;

        // Specific OK performance (Mandiri Obat Kuasi: jenis_produk = 3)
        $okQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('jenis_produk', 3)
                ->orWhere(fn ($sq) => $sq->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->where('komoditi', 'OK')));
        })->whereNotNull('hasil_evaluasi');
        $okTotal = (clone $okQuery)->count();
        $okMk = (clone $okQuery)->where('hasil_evaluasi', 1)->count();
        $okPersenMk = $okTotal > 0 ? ($okMk / $okTotal) * 100 : 0;

        // Specific SK performance (Mandiri Suplemen Kesehatan: jenis_produk = 2)
        $skQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('jenis_produk', 2)
                ->orWhere(fn ($sq) => $sq->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->where('komoditi', 'SK')));
        })->whereNotNull('hasil_evaluasi');
        $skTotal = (clone $skQuery)->count();
        $skMk = (clone $skQuery)->where('hasil_evaluasi', 1)->count();
        $skPersenMk = $skTotal > 0 ? ($skMk / $skTotal) * 100 : 0;
        $skCapaianIku = $targetSk > 0 ? ($skPersenMk / $targetSk) * 100 : 0;

        // Perhitungan Baku IKU OBA (Gabungan Dataset OBA/OT + OK)
        // IKU OBA BPOM adalah gabungan komoditi Obat Bahan Alam (OT) dan Obat Kuasi (OK)
        // Realisasi % MK IKU OBA = (Total MK OBA + OK) / (Total Pengawasan OBA + OK) * 100
        $ikuObaTotal = $obaTotal + $okTotal;
        $ikuObaMk = $obaMk + $okMk;
        $ikuObaPersenMk = $ikuObaTotal > 0 ? ($ikuObaMk / $ikuObaTotal) * 100 : 0;
        $ikuObaCapaian = $targetOba > 0 ? ($ikuObaPersenMk / $targetOba) * 100 : 0;

        // 4. Monthly Trend (Jan - Des)
        $monthlyTrend = $this->getMonthlyTrend($baseQuery, $tahun, $targetOba, $targetSk);

        // 5. Stacked Bar Chart Dimensions
        $dimensions = [
            'asal_produk' => $this->getAsalProdukBreakdown($evalQuery),
            'kategori_media' => $this->getKategoriMediaBreakdown($evalQuery),
            'kategori_iklan' => $this->getKategoriIklanBreakdown($evalQuery, $komoditi),
            'pembuat_iklan' => $this->getPembuatIklanBreakdown($evalQuery),
        ];

        // 6. Top 5 TMK Violations (Horizontal Bar)
        $topTmk = $this->getTopTmkViolations($evalQuery, $tmkCount);

        // 7. NIE E-Commerce Summary Table
        $nieEcommerce = $this->getNieEcommerceSummary($baseQuery);

        // 8. Available years and UPTs for filters with breakdown
        $finalYears = Dataset::where('status', 'Final')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('periode_tahun')
            ->map(fn ($y) => (int) $y)
            ->toArray();

        $allDatasetYears = Dataset::whereNull('deleted_at')
            ->distinct()
            ->pluck('periode_tahun')
            ->map(fn ($y) => (int) $y)
            ->toArray();

        $currentYear = (int) now()->year;
        $rangeYears = range(max($currentYear, 2026), 2020);

        $availableYears = array_values(array_filter(
            array_unique(array_merge($finalYears, $allDatasetYears, $rangeYears, [$tahun])),
            fn ($y) => (int) $y >= 2020
        ));
        rsort($availableYears);

        // Preload counts per year for breakdown
        $iklanCountsByYear = DataIklan::where('is_final', true)
            ->whereNull('deleted_at')
            ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'))
            ->groupBy('periode_tahun')
            ->selectRaw('periode_tahun, count(*) as total')
            ->pluck('total', 'periode_tahun')
            ->toArray();

        $datasetCountsByYear = Dataset::whereNull('deleted_at')
            ->groupBy('periode_tahun')
            ->selectRaw('periode_tahun, count(*) as total')
            ->pluck('total', 'periode_tahun')
            ->toArray();

        $yearBreakdown = [];
        foreach ($availableYears as $y) {
            $yearBreakdown[] = [
                'tahun' => (int) $y,
                'total_iklan_final' => (int) ($iklanCountsByYear[$y] ?? 0),
                'total_dataset' => (int) ($datasetCountsByYear[$y] ?? 0),
                'is_current' => (int) $y === $currentYear,
                'has_data' => isset($iklanCountsByYear[$y]) && $iklanCountsByYear[$y] > 0,
            ];
        }

        $uptList = Upt::orderBy('kode')->get(['kode', 'nama']);

        // 8. Top UPT dengan TMK tertinggi
        $topUptTmk = $this->calculateTopUptTmk($filteredQuery, 8);

        // 9. Kelengkapan Periode (Jan - Des x OBA, OK, SK)
        $kelengkapanPeriode = $this->getKelengkapanPeriode($baseQuery, $tahun);

        return [
            'filter' => [
                'tahun' => $tahun,
                'komoditi' => $komoditi,
                'upt_kode' => $uptKode,
            ],
            'available_years' => $availableYears,
            'year_breakdown' => $yearBreakdown,
            'upt_list' => $uptList,
            'kpi' => [
                'total_final' => $totalFinal,
                'total_pengawasan' => $totalPengawasan,
                'mk' => $mkCount,
                'tmk' => $tmkCount,
                'persen_mk' => $persenMk,
                'persen_tmk' => $persenTmk,
                'menunggu_evaluasi' => $menungguEvaluasi,
            ],
            'iku' => [
                'oba' => [
                    'target' => $targetOba,
                    'realisasi_persen_mk' => $ikuObaPersenMk,
                    'capaian_persen' => $ikuObaCapaian,
                    'total' => $ikuObaTotal,
                    'mk' => $ikuObaMk,
                    'tmk' => $ikuObaTotal - $ikuObaMk,
                    'persen_tmk' => $ikuObaTotal > 0 ? (($ikuObaTotal - $ikuObaMk) / $ikuObaTotal) * 100 : 0,
                    'tercapai' => $ikuObaPersenMk >= $targetOba,
                    'murni_total' => $obaTotal,
                    'murni_mk' => $obaMk,
                ],
                'ok' => [
                    'target' => 0.0,
                    'realisasi_persen_mk' => $okPersenMk,
                    'capaian_persen' => 0.0,
                    'total' => $okTotal,
                    'mk' => $okMk,
                    'tmk' => $okTotal - $okMk,
                    'persen_tmk' => $okTotal > 0 ? (($okTotal - $okMk) / $okTotal) * 100 : 0,
                    'tercapai' => true,
                ],
                'sk' => [
                    'target' => $targetSk,
                    'realisasi_persen_mk' => $skPersenMk,
                    'capaian_persen' => $skCapaianIku,
                    'total' => $skTotal,
                    'mk' => $skMk,
                    'tmk' => $skTotal - $skMk,
                    'persen_tmk' => $skTotal > 0 ? (($skTotal - $skMk) / $skTotal) * 100 : 0,
                    'tercapai' => $skPersenMk >= $targetSk,
                ],
                'rincian' => [
                    'oba' => ['total' => $obaTotal, 'mk' => $obaMk],
                    'ok' => ['total' => $okTotal, 'mk' => $okMk],
                    'sk' => ['total' => $skTotal, 'mk' => $skMk],
                ],
            ],
            'monthly_trend' => $monthlyTrend,
            'dimensions' => $dimensions,
            'top_tmk' => $topTmk,
            'nie_ecommerce' => $nieEcommerce,
            'top_upt_tmk' => $topUptTmk,
            'kelengkapan_periode' => $kelengkapanPeriode,
        ];
    }

    /**
     * Compute monthly trend for Jan - Des.
     */
    protected function getMonthlyTrend($baseQuery, int $tahun, float $targetOba, float $targetSk): array
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $labels = array_values($months);
        $obaPersen = [];
        $okPersen = [];
        $skPersen = [];
        $gabunganPersen = [];
        $totalPerBulan = [];
        $mkPerBulan = [];
        $tmkPerBulan = [];

        $statsPerMonth = (clone $baseQuery)
            ->whereNotNull('hasil_evaluasi')
            ->selectRaw('
                periode_bulan,
                COUNT(*) as gab_tot,
                SUM(CASE WHEN hasil_evaluasi = 1 THEN 1 ELSE 0 END) as gab_mk,
                SUM(CASE WHEN jenis_produk = 1 THEN 1 ELSE 0 END) as oba_tot,
                SUM(CASE WHEN jenis_produk = 1 AND hasil_evaluasi = 1 THEN 1 ELSE 0 END) as oba_mk,
                SUM(CASE WHEN jenis_produk = 3 THEN 1 ELSE 0 END) as ok_tot,
                SUM(CASE WHEN jenis_produk = 3 AND hasil_evaluasi = 1 THEN 1 ELSE 0 END) as ok_mk,
                SUM(CASE WHEN jenis_produk = 2 THEN 1 ELSE 0 END) as sk_tot,
                SUM(CASE WHEN jenis_produk = 2 AND hasil_evaluasi = 1 THEN 1 ELSE 0 END) as sk_mk
            ')
            ->groupBy('periode_bulan')
            ->get()
            ->keyBy('periode_bulan');

        for ($m = 1; $m <= 12; $m++) {
            $row = $statsPerMonth->get($m);

            // OBA (Sesuai Ketentuan IKU BPOM: Gabungan OT/OBA [1] & OK [3])
            $mObaTot = (int) ($row->oba_tot ?? 0) + (int) ($row->ok_tot ?? 0);
            $mObaMk = (int) ($row->oba_mk ?? 0) + (int) ($row->ok_mk ?? 0);
            $obaPersen[] = $mObaTot > 0 ? round(($mObaMk / $mObaTot) * 100, 2) : null;

            // OK ([3])
            $mOkTot = (int) ($row->ok_tot ?? 0);
            $mOkMk = (int) ($row->ok_mk ?? 0);
            $okPersen[] = $mOkTot > 0 ? round(($mOkMk / $mOkTot) * 100, 2) : null;

            // SK ([2])
            $mSkTot = (int) ($row->sk_tot ?? 0);
            $mSkMk = (int) ($row->sk_mk ?? 0);
            $skPersen[] = $mSkTot > 0 ? round(($mSkMk / $mSkTot) * 100, 2) : null;

            // Gabungan
            $mGabTot = (int) ($row->gab_tot ?? 0);
            $mGabMk = (int) ($row->gab_mk ?? 0);
            $gabunganPersen[] = $mGabTot > 0 ? round(($mGabMk / $mGabTot) * 100, 2) : null;
            $totalPerBulan[] = $mGabTot;
            $mkPerBulan[] = $mGabMk;
            $tmkPerBulan[] = max(0, $mGabTot - $mGabMk);
        }

        return [
            'labels' => $labels,
            'oba' => $obaPersen,
            'ok' => $okPersen,
            'sk' => $skPersen,
            'gabungan' => $gabunganPersen,
            'total_per_bulan' => $totalPerBulan,
            'mk_per_bulan' => $mkPerBulan,
            'tmk_per_bulan' => $tmkPerBulan,
            'target_oba' => array_fill(0, 12, $targetOba),
            'target_sk' => array_fill(0, 12, $targetSk),
        ];
    }

    /**
     * Helper agregasi 1 query untuk menghitung Total & MK per kode dimensi.
     */
    protected function getGroupedDimensionCounts($query, string $column)
    {
        return (clone $query)
            ->whereNotNull($column)
            ->selectRaw("{$column} as code, COUNT(*) as tot, SUM(CASE WHEN hasil_evaluasi = 1 THEN 1 ELSE 0 END) as mk")
            ->groupBy($column)
            ->get()
            ->keyBy('code');
    }

    /**
     * Breakdown for Asal Produk: Lokal (1), Impor (2), Lisensi (3).
     */
    protected function getAsalProdukBreakdown($query): array
    {
        $labels = ['Lokal', 'Impor', 'Lisensi'];
        $mkData = [];
        $tmkData = [];
        $totalData = [];

        $grouped = $this->getGroupedDimensionCounts($query, 'asal_produk');

        foreach ([1, 2, 3] as $code) {
            $row = $grouped->get($code);
            $tot = (int) ($row->tot ?? 0);
            $mk = (int) ($row->mk ?? 0);
            $tmk = max(0, $tot - $mk);

            $mkData[] = $mk;
            $tmkData[] = $tmk;
            $totalData[] = $tot;
        }

        return [
            'title' => 'Asal Produk',
            'labels' => $labels,
            'mk' => $mkData,
            'tmk' => $tmkData,
            'total' => $totalData,
        ];
    }

    /**
     * Breakdown for Kategori Media: Audio (1), Visual (2), Audiovisual (3).
     */
    protected function getKategoriMediaBreakdown($query): array
    {
        $labels = ['Audio', 'Visual', 'Audiovisual'];
        $mkData = [];
        $tmkData = [];
        $totalData = [];

        $grouped = $this->getGroupedDimensionCounts($query, 'kategori_media');

        foreach ([1, 2, 3] as $code) {
            $row = $grouped->get($code);
            $tot = (int) ($row->tot ?? 0);
            $mk = (int) ($row->mk ?? 0);
            $tmk = max(0, $tot - $mk);

            $mkData[] = $mk;
            $tmkData[] = $tmk;
            $totalData[] = $tot;
        }

        return [
            'title' => 'Kategori Media',
            'labels' => $labels,
            'mk' => $mkData,
            'tmk' => $tmkData,
            'total' => $totalData,
        ];
    }

    /**
     * Breakdown for Kategori Iklan (8 items matching rekap table).
     */
    protected function getKategoriIklanBreakdown($query, string $komoditi): array
    {
        $items = [
            ['label' => 'E-Commerce', 'code' => 1],
            ['label' => 'Website', 'code' => 2],
            ['label' => 'Media Sosial', 'code' => 3],
            ['label' => 'Internet Lain', 'code' => 4],
            ['label' => 'TV Nasional', 'code' => 5],
            ['label' => 'Penyiaran Lokal', 'code' => 6],
            ['label' => 'Luar Ruang', 'code' => 7],
            ['label' => 'Cetak', 'code' => 8],
        ];

        $labels = [];
        $mkData = [];
        $tmkData = [];
        $totalData = [];

        $grouped = $this->getGroupedDimensionCounts($query, 'kategori_iklan');

        foreach ($items as $item) {
            $labels[] = $item['label'];
            $codes = is_array($item['code']) ? $item['code'] : [$item['code']];
            $tot = 0;
            $mk = 0;
            foreach ($codes as $c) {
                $row = $grouped->get($c);
                $tot += (int) ($row->tot ?? 0);
                $mk += (int) ($row->mk ?? 0);
            }
            $tmk = max(0, $tot - $mk);

            $mkData[] = $mk;
            $tmkData[] = $tmk;
            $totalData[] = $tot;
        }

        return [
            'title' => 'Kategori Iklan',
            'labels' => $labels,
            'mk' => $mkData,
            'tmk' => $tmkData,
            'total' => $totalData,
        ];
    }

    /**
     * Breakdown for Pembuat Iklan: Pelaku Usaha (1), Perseorangan (2).
     */
    protected function getPembuatIklanBreakdown($query): array
    {
        $labels = ['Pelaku Usaha', 'Perseorangan'];
        $mkData = [];
        $tmkData = [];
        $totalData = [];

        $grouped = $this->getGroupedDimensionCounts($query, 'pembuat_iklan');

        foreach ([1, 2] as $code) {
            $row = $grouped->get($code);
            $tot = (int) ($row->tot ?? 0);
            $mk = (int) ($row->mk ?? 0);
            $tmk = max(0, $tot - $mk);

            $mkData[] = $mk;
            $tmkData[] = $tmk;
            $totalData[] = $tot;
        }

        return [
            'title' => 'Pembuat Iklan',
            'labels' => $labels,
            'mk' => $mkData,
            'tmk' => $tmkData,
            'total' => $totalData,
        ];
    }

    /**
     * Top 5 TMK Violations.
     */
    protected function getTopTmkViolations($query, int $tmkTotal): array
    {
        $tmkQuery = (clone $query)->where('hasil_evaluasi', 2);

        $tie = (clone $tmkQuery)->where('rincian_tmk', 7)->count();
        $testimoni = (clone $tmkQuery)->whereIn('rincian_tmk', [1, 4])->count();
        $hadiah = (clone $tmkQuery)->whereIn('rincian_tmk', [2, 5])->count();
        $klaim = (clone $tmkQuery)->whereIn('rincian_tmk', [3, 6])->count();
        $lainLain = max(0, $tmkTotal - ($tie + $testimoni + $hadiah + $klaim));

        $violations = [
            ['label' => 'Klaim Berlebihan', 'total' => $klaim],
            ['label' => 'Produk Tidak Terdaftar (TIE)', 'total' => $tie],
            ['label' => 'Mencantumkan Testimoni', 'total' => $testimoni],
            ['label' => 'Lain-lain', 'total' => $lainLain],
            ['label' => 'Memberikan Hadiah', 'total' => $hadiah],
        ];

        // Sort descending
        usort($violations, fn ($a, $b) => $b['total'] <=> $a['total']);

        // Calculate percentage of total TMK
        foreach ($violations as &$item) {
            $item['persen'] = $tmkTotal > 0 ? round(($item['total'] / $tmkTotal) * 100, 2) : 0;
        }

        return [
            'total_tmk' => $tmkTotal,
            'items' => $violations,
            'labels' => array_column($violations, 'label'),
            'values' => array_column($violations, 'total'),
            'percentages' => array_column($violations, 'persen'),
        ];
    }

    /**
     * NIE E-Commerce summary table comparison (OBA vs SK).
     */
    protected function getNieEcommerceSummary($baseQuery): array
    {
        // Helper
        $calcNie = function ($query, array $jenisProdukCodes) {
            $ecom = (clone $query)->whereIn('jenis_produk', $jenisProdukCodes)
                ->where('kategori_iklan', 1)
                ->whereNotNull('hasil_evaluasi');

            $tot = (clone $ecom)->count();
            $ada = (clone $ecom)->where('pencantuman_nie', 1)->count();
            $tidakAda = $tot - $ada;
            $persenAda = $tot > 0 ? round(($ada / $tot) * 100, 2) : 0;

            $sesuai = (clone $ecom)->where('pencantuman_nie', 1)->where('kesesuaian_nie', 1)->count();
            $tidakSesuai = $ada - $sesuai;
            $persenSesuai = $ada > 0 ? round(($sesuai / $ada) * 100, 2) : 0;

            return [
                'total' => $tot,
                'nie_ada' => $ada,
                'nie_tidak_ada' => $tidakAda,
                'persen_ada' => $persenAda,
                'nie_sesuai' => $sesuai,
                'nie_tidak_sesuai' => $tidakSesuai,
                'persen_sesuai' => $persenSesuai,
            ];
        };

        $oba = $calcNie(clone $baseQuery, [1]);
        $ok = $calcNie(clone $baseQuery, [3]);
        $sk = $calcNie(clone $baseQuery, [2]);

        return [
            'oba' => $oba,
            'ok' => $ok,
            'sk' => $sk,
        ];
    }

    /**
     * Menghitung Top UPT dengan TMK tertinggi (Filter hanya UPT dengan jumlah_tmk > 0, urutkan jumlah_tmk DESC, persen_tmk DESC, limit 8).
     */
    public function calculateTopUptTmk($baseQuery, int $limit = 8): array
    {
        $evalQuery = (clone $baseQuery)->whereNotNull('hasil_evaluasi');

        $rows = (clone $evalQuery)
            ->whereNotNull('data_iklan.upt')
            ->leftJoin('upts', 'data_iklan.upt', '=', 'upts.kode')
            ->selectRaw('
                data_iklan.upt as kode_upt,
                COALESCE(upts.nama, CONCAT("UPT ", data_iklan.upt)) as nama_upt,
                COUNT(*) as total_iklan,
                SUM(CASE WHEN data_iklan.hasil_evaluasi = 2 THEN 1 ELSE 0 END) as jumlah_tmk,
                SUM(CASE WHEN data_iklan.hasil_evaluasi = 1 THEN 1 ELSE 0 END) as jumlah_mk
            ')
            ->groupBy('data_iklan.upt', 'upts.nama')
            ->having('jumlah_tmk', '>', 0)
            ->orderByDesc('jumlah_tmk')
            ->get();

        $list = [];
        foreach ($rows as $r) {
            $total = (int) $r->total_iklan;
            $tmk = (int) $r->jumlah_tmk;
            $mk = (int) $r->jumlah_mk;
            $persenTmk = $total > 0 ? round(($tmk / $total) * 100, 1) : 0.0;
            $persenMk = $total > 0 ? round(($mk / $total) * 100, 1) : 0.0;

            $list[] = [
                'kode' => (int) $r->kode_upt,
                'nama' => $r->nama_upt,
                'nama_upt' => $r->nama_upt,
                'total' => $total,
                'total_iklan' => $total,
                'mk' => $mk,
                'tmk' => $tmk,
                'jumlah_tmk' => $tmk,
                'persen_mk' => $persenMk,
                'persen_tmk' => $persenTmk,
                'is_small_n' => $total < 15,
            ];
        }

        usort($list, function ($a, $b) {
            if ($b['jumlah_tmk'] === $a['jumlah_tmk']) {
                return $b['persen_tmk'] <=> $a['persen_tmk'];
            }
            return $b['jumlah_tmk'] <=> $a['jumlah_tmk'];
        });

        $topList = array_slice($list, 0, $limit);

        return [
            'total_upt_tmk' => count($topList),
            'items' => $topList,
            'labels' => array_column($topList, 'nama_upt'),
            'values' => array_column($topList, 'jumlah_tmk'),
            'percentages' => array_column($topList, 'persen_tmk'),
            'totals' => array_column($topList, 'total_iklan'),
        ];
    }

    /**
     * Alias backward-compatible untuk Top UPT dengan TMK tertinggi.
     */
    protected function getTopUptTmk($evalQuery, $uptList = null): array
    {
        return $this->calculateTopUptTmk($evalQuery, 8);
    }

    /**
     * Matriks kelengkapan data per bulan x komoditi (OBA, OK, SK) status final / belum.
     */
    protected function getKelengkapanPeriode($baseQuery, int $tahun): array
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $counts = (clone $baseQuery)
            ->selectRaw('periode_bulan, jenis_produk, COUNT(*) as cnt')
            ->groupBy('periode_bulan', 'jenis_produk')
            ->get();

        $lookup = [];
        foreach ($counts as $c) {
            $lookup[$c->periode_bulan][$c->jenis_produk] = (int) $c->cnt;
        }

        $matrix = [];
        foreach ($months as $m => $namaBulan) {
            $otCount = $lookup[$m][1] ?? 0;
            $skCount = $lookup[$m][2] ?? 0;
            $okCount = $lookup[$m][3] ?? 0;

            $matrix[] = [
                'bulan' => $m,
                'nama' => $namaBulan,
                'oba' => [
                    'final' => ($otCount > 0),
                    'count' => $otCount,
                ],
                'ok' => [
                    'final' => ($okCount > 0),
                    'count' => $okCount,
                ],
                'sk' => [
                    'final' => ($skCount > 0),
                    'count' => $skCount,
                ],
                'has_data' => ($otCount + $skCount + $okCount) > 0,
            ];
        }

        return $matrix;
    }
}
