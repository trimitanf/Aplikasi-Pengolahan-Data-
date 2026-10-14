<?php

namespace App\Services;

use App\Models\DataIklan;
use App\Models\Dataset;
use App\Models\TargetIku;
use App\Models\Upt;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get complete dashboard metrics for given year, commodity, and optional UPT.
     */
    public function getMetrics(int $tahun, string $komoditi = 'SEMUA', ?int $uptKode = null): array
    {
        // 1. Base query for finalized data in the year
        $baseQuery = DataIklan::where('periode_tahun', $tahun)
            ->where('is_final', true);

        if ($uptKode !== null) {
            $baseQuery->where('upt', $uptKode);
        }

        // Clone for commodity filtering
        $filteredQuery = clone $baseQuery;
        if ($komoditi === 'OBA') {
            $filteredQuery->whereIn('jenis_produk', [1, 3]);
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
        $targetObaRecord = TargetIku::where('tahun', $tahun)->where('kelompok', 'OBA')->first();
        $targetSkRecord = TargetIku::where('tahun', $tahun)->where('kelompok', 'SK')->first();

        $targetOba = $targetObaRecord ? (float)$targetObaRecord->target : 67.00;
        $targetSk = $targetSkRecord ? (float)$targetSkRecord->target : 83.00;

        // Specific OBA performance
        $obaQuery = (clone $baseQuery)->whereIn('jenis_produk', [1, 3])->whereNotNull('hasil_evaluasi');
        $obaTotal = (clone $obaQuery)->count();
        $obaMk = (clone $obaQuery)->where('hasil_evaluasi', 1)->count();
        $obaPersenMk = $obaTotal > 0 ? ($obaMk / $obaTotal) * 100 : 0;
        $obaCapaianIku = $targetOba > 0 ? ($obaPersenMk / $targetOba) * 100 : 0;

        // Specific SK performance
        $skQuery = (clone $baseQuery)->where('jenis_produk', 2)->whereNotNull('hasil_evaluasi');
        $skTotal = (clone $skQuery)->count();
        $skMk = (clone $skQuery)->where('hasil_evaluasi', 1)->count();
        $skPersenMk = $skTotal > 0 ? ($skMk / $skTotal) * 100 : 0;
        $skCapaianIku = $targetSk > 0 ? ($skPersenMk / $targetSk) * 100 : 0;

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

        // 8. Available years and UPTs for filters
        $availableYears = Dataset::where('status', 'Final')
            ->distinct()
            ->orderByDesc('periode_tahun')
            ->pluck('periode_tahun')
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        $uptList = Upt::orderBy('kode')->get(['kode', 'nama']);

        return [
            'filter' => [
                'tahun' => $tahun,
                'komoditi' => $komoditi,
                'upt_kode' => $uptKode,
            ],
            'available_years' => $availableYears,
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
                    'realisasi_persen_mk' => $obaPersenMk,
                    'capaian_persen' => $obaCapaianIku,
                    'total' => $obaTotal,
                    'mk' => $obaMk,
                    'tercapai' => $obaPersenMk >= $targetOba,
                ],
                'sk' => [
                    'target' => $targetSk,
                    'realisasi_persen_mk' => $skPersenMk,
                    'capaian_persen' => $skCapaianIku,
                    'total' => $skTotal,
                    'mk' => $skMk,
                    'tercapai' => $skPersenMk >= $targetSk,
                ],
            ],
            'monthly_trend' => $monthlyTrend,
            'dimensions' => $dimensions,
            'top_tmk' => $topTmk,
            'nie_ecommerce' => $nieEcommerce,
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
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $labels = array_values($months);
        $obaPersen = [];
        $skPersen = [];
        $gabunganPersen = [];
        $totalPerBulan = [];

        for ($m = 1; $m <= 12; $m++) {
            // OBA
            $mObaQuery = (clone $baseQuery)->where('periode_bulan', $m)
                ->whereIn('jenis_produk', [1, 3])
                ->whereNotNull('hasil_evaluasi');
            $mObaTot = (clone $mObaQuery)->count();
            $mObaMk = (clone $mObaQuery)->where('hasil_evaluasi', 1)->count();
            $obaPersen[] = $mObaTot > 0 ? round(($mObaMk / $mObaTot) * 100, 2) : null;

            // SK
            $mSkQuery = (clone $baseQuery)->where('periode_bulan', $m)
                ->where('jenis_produk', 2)
                ->whereNotNull('hasil_evaluasi');
            $mSkTot = (clone $mSkQuery)->count();
            $mSkMk = (clone $mSkQuery)->where('hasil_evaluasi', 1)->count();
            $skPersen[] = $mSkTot > 0 ? round(($mSkMk / $mSkTot) * 100, 2) : null;

            // Gabungan
            $mGabQuery = (clone $baseQuery)->where('periode_bulan', $m)
                ->whereNotNull('hasil_evaluasi');
            $mGabTot = (clone $mGabQuery)->count();
            $mGabMk = (clone $mGabQuery)->where('hasil_evaluasi', 1)->count();
            $gabunganPersen[] = $mGabTot > 0 ? round(($mGabMk / $mGabTot) * 100, 2) : null;
            $totalPerBulan[] = $mGabTot;
        }

        return [
            'labels' => $labels,
            'oba' => $obaPersen,
            'sk' => $skPersen,
            'gabungan' => $gabunganPersen,
            'total_per_bulan' => $totalPerBulan,
            'target_oba' => array_fill(0, 12, $targetOba),
            'target_sk' => array_fill(0, 12, $targetSk),
        ];
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

        foreach ([1, 2, 3] as $code) {
            $rowQuery = (clone $query)->where('asal_produk', $code);
            $tot = (clone $rowQuery)->count();
            $mk = (clone $rowQuery)->where('hasil_evaluasi', 1)->count();
            $tmk = $tot - $mk;

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

        foreach ([1, 2, 3] as $code) {
            $rowQuery = (clone $query)->where('kategori_media', $code);
            $tot = (clone $rowQuery)->count();
            $mk = (clone $rowQuery)->where('hasil_evaluasi', 1)->count();
            $tmk = $tot - $mk;

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
     * Breakdown for Kategori Iklan (8 items or 7 if SK).
     */
    protected function getKategoriIklanBreakdown($query, string $komoditi): array
    {
        if ($komoditi === 'SK') {
            $items = [
                ['label' => 'E-Commerce', 'code' => 1],
                ['label' => 'Website', 'code' => 2],
                ['label' => 'Media Sosial', 'code' => 3],
                ['label' => 'Internet Lain', 'code' => 4],
                ['label' => 'TV Nasional', 'code' => [5, 6]],
                ['label' => 'Luar Ruang', 'code' => 7],
                ['label' => 'Cetak', 'code' => 8],
            ];
        } else {
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
        }

        $labels = [];
        $mkData = [];
        $tmkData = [];
        $totalData = [];

        foreach ($items as $item) {
            $labels[] = $item['label'];
            $rowQuery = is_array($item['code'])
                ? (clone $query)->whereIn('kategori_iklan', $item['code'])
                : (clone $query)->where('kategori_iklan', $item['code']);

            $tot = (clone $rowQuery)->count();
            $mk = (clone $rowQuery)->where('hasil_evaluasi', 1)->count();
            $tmk = $tot - $mk;

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

        foreach ([1, 2] as $code) {
            $rowQuery = (clone $query)->where('pembuat_iklan', $code);
            $tot = (clone $rowQuery)->count();
            $mk = (clone $rowQuery)->where('hasil_evaluasi', 1)->count();
            $tmk = $tot - $mk;

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
        usort($violations, fn($a, $b) => $b['total'] <=> $a['total']);

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

        $oba = $calcNie(clone $baseQuery, [1, 3]);
        $sk = $calcNie(clone $baseQuery, [2]);

        return [
            'oba' => $oba,
            'sk' => $sk,
        ];
    }
}
