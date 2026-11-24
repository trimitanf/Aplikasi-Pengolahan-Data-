<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;

use App\Models\DataIklan;
use App\Models\MasterKode;
use App\Services\Iklan\ExportStandardisasiService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTersimpanController extends Controller
{
    public function __construct(
        protected ExportStandardisasiService $exportService
    ) {}

    public const PELANGGARAN_MAP = [
        'klaim_berlebihan' => [
            'label' => 'Mencantumkan Klaim Berlebihan',
            'codes' => [3, 6],
        ],
        'tie' => [
            'label' => 'Produk Tidak Terdaftar (TIE)',
            'codes' => [7],
        ],
        'testimoni' => [
            'label' => 'Mencantumkan Testimoni',
            'codes' => [1, 4],
        ],
        'hadiah' => [
            'label' => 'Memberikan Hadiah',
            'codes' => [2, 5],
        ],
        'lainnya' => [
            'label' => 'Pelanggaran Lain-lain',
            'codes' => null,
        ],
    ];

    public function index(Request $request): View
    {
        [$query, $filters, $activePelanggaranName, $activeDimensionInfo] = $this->buildFilteredQuery($request);

        $totalFinal = (clone $query)->count();
        $items = $query->with(['dataMentah', 'evaluator'])
            ->orderByRaw('CASE WHEN upt IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('upt', 'asc')
            ->orderByRaw('CASE WHEN periode_bulan IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('periode_bulan', 'asc')
            ->orderBy('no', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(100)
            ->withQueryString();

        $masterDict = MasterKode::all()->groupBy('grup')->map(function ($g) {
            return $g->pluck('nama', 'kode')->toArray();
        })->toArray();
        $masterDict['upt'] = \App\Models\Upt::orderBy('kode')->pluck('nama', 'kode')->toArray();

        $evaluators = \App\Models\User::where('role', 'evaluator')->where('is_active', true)->orderBy('name')->get();
        $uptList = \App\Models\Upt::orderBy('kode')->get(['kode', 'nama']);

        return view('data_tersimpan.index', [
            'items' => $items,
            'totalFinal' => $totalFinal,
            'filters' => $filters,
            'evaluators' => $evaluators,
            'uptList' => $uptList,
            'activePelanggaranName' => $activePelanggaranName,
            'activeDimensionInfo' => $activeDimensionInfo,
            'highlightCol' => $filters['highlight_col'],
            'pelanggaranOptions' => self::PELANGGARAN_MAP,
            'masterDict' => $masterDict,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$query, $filters] = $this->buildFilteredQuery($request);
        $query
            ->orderByRaw('CASE WHEN upt IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('upt', 'asc')
            ->orderByRaw('CASE WHEN periode_bulan IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('periode_bulan', 'asc')
            ->orderBy('no', 'asc')
            ->orderBy('id', 'asc');

        $tahun = $filters['tahun'];
        $bulanAwal = $filters['bulan_awal'];
        $bulanAkhir = $filters['bulan_akhir'];
        $suffix = $filters['pelanggaran'] ? "_{$filters['pelanggaran']}" : '';

        $filename = "Data_Standardisasi_{$tahun}_{$bulanAwal}-{$bulanAkhir}{$suffix}.xlsx";

        return $this->exportService->download($query, $filename);
    }

    /**
     * Build base query with standard filters (Tahun, Bulan, Komoditi, UPT, Hasil Evaluasi, Pelanggaran TMK, 4 Dimensi, Cari).
     */
    protected function buildFilteredQuery(Request $request): array
    {
        $query = DataIklan::where(function ($q) {
            $q->where('is_final', true)
                ->orWhere(function ($sub) {
                    $sub->whereNotNull('kualitas_laporan')
                        ->whereNotNull('ketepatan_waktu');
                });
        })
            ->whereNull('deleted_at')
            ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'));

        $latestYear = DataIklan::where('is_final', true)
            ->whereNull('deleted_at')
            ->whereHas('dataset', fn ($q) => $q->whereNull('deleted_at'))
            ->max('periode_tahun')
            ?? \App\Models\Dataset::whereNull('deleted_at')->max('periode_tahun')
            ?? now()->year;
        $tahun = $request->integer('tahun', (int) $latestYear);
        $bulanAwal = $request->integer('bulan_awal', 1);
        $bulanAkhir = $request->integer('bulan_akhir', 12);
        $komoditi = $request->query('komoditi');
        $upt = $request->query('upt');
        $hasilEvaluasi = $request->query('hasil_evaluasi');
        $pelanggaran = $request->query('pelanggaran');

        // Filter 4 Dimensi Distribusi
        $asalProduk = $request->query('asal_produk');
        $kategoriMedia = $request->query('kategori_media');
        $kategoriIklan = $request->query('kategori_iklan');
        $pembuatIklan = $request->query('pembuat_iklan');
        $highlightCol = $request->query('highlight_col');

        // Normalisasi alias pelanggaran
        $aliasMap = [
            'klaim' => 'klaim_berlebihan',
            'produk_tidak_terdaftar' => 'tie',
            'mencantumkan_testimoni' => 'testimoni',
            'memberikan_hadiah' => 'hadiah',
            'lain_lain' => 'lainnya',
        ];
        if (isset($aliasMap[$pelanggaran])) {
            $pelanggaran = $aliasMap[$pelanggaran];
        }

        if ($tahun) {
            $query->where('periode_tahun', $tahun);
        }

        if ($bulanAwal && $bulanAkhir) {
            $query->whereBetween('periode_bulan', [$bulanAwal, $bulanAkhir]);
        }

        if ($komoditi && $komoditi !== 'SEMUA') {
            $komoditi = strtoupper($komoditi);
            if ($komoditi === 'OT' || $komoditi === 'OBA') {
                $query->where(function ($q) {
                    $q->where('jenis_produk', 1)
                        ->orWhere(fn ($sub) => $sub->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->whereIn('komoditi', ['OBA', 'OT'])));
                });
            } elseif ($komoditi === 'OK') {
                $query->where(function ($q) {
                    $q->where('jenis_produk', 3)
                        ->orWhere(fn ($sub) => $sub->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->where('komoditi', 'OK')));
                });
            } elseif ($komoditi === 'SK') {
                $query->where(function ($q) {
                    $q->where('jenis_produk', 2)
                        ->orWhere(fn ($sub) => $sub->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->where('komoditi', 'SK')));
                });
            } else {
                $query->whereHas('dataset', fn ($q) => $q->where('komoditi', $komoditi));
            }
        }

        if ($upt) {
            $query->where('upt', (int) $upt);
        }

        if ($request->filled('evaluator_id')) {
            $query->where('evaluator_id', $request->integer('evaluator_id'));
        }

        if ($hasilEvaluasi && in_array((int) $hasilEvaluasi, [1, 2])) {
            $query->where('hasil_evaluasi', (int) $hasilEvaluasi);
        }

        // Apply filter 4 dimensi
        if ($asalProduk) {
            $query->where('asal_produk', (int) $asalProduk);
        }
        if ($kategoriMedia) {
            $query->where('kategori_media', (int) $kategoriMedia);
        }
        if ($kategoriIklan) {
            $query->where('kategori_iklan', (int) $kategoriIklan);
        }
        if ($pembuatIklan) {
            $query->where('pembuat_iklan', (int) $pembuatIklan);
        }

        $activePelanggaranName = null;
        if ($pelanggaran) {
            $query->where('hasil_evaluasi', 2); // Otomatis TMK

            if (isset(self::PELANGGARAN_MAP[$pelanggaran])) {
                $config = self::PELANGGARAN_MAP[$pelanggaran];
                $activePelanggaranName = $config['label'];

                if ($config['codes'] !== null) {
                    $query->whereIn('rincian_tmk', $config['codes']);
                } else {
                    // Kategori 'lainnya'
                    $query->where(function ($q) {
                        $q->whereNotIn('rincian_tmk', [1, 2, 3, 4, 5, 6, 7])
                            ->orWhereNull('rincian_tmk')
                            ->orWhere('rincian_tmk', 8);
                    });
                }
            } elseif (is_numeric($pelanggaran)) {
                $query->where('rincian_tmk', (int) $pelanggaran);
                $activePelanggaranName = "Rincian TMK #{$pelanggaran}";
            }
        }

        // Label info untuk dimensi aktif
        $dimensionLabels = [
            'asal_produk' => [
                'name' => 'Asal Produk',
                'values' => [1 => 'Lokal', 2 => 'Impor', 3 => 'Lisensi'],
                'val' => $asalProduk,
            ],
            'kategori_media' => [
                'name' => 'Kategori Media',
                'values' => [1 => 'Audio', 2 => 'Visual', 3 => 'Audiovisual'],
                'val' => $kategoriMedia,
            ],
            'kategori_iklan' => [
                'name' => 'Kategori Iklan',
                'values' => [
                    1 => 'E-Commerce', 2 => 'Website', 3 => 'Media Sosial',
                    4 => 'Internet Lain', 5 => 'TV Nasional', 6 => 'Penyiaran Lokal',
                    7 => 'Luar Ruang', 8 => 'Cetak',
                ],
                'val' => $kategoriIklan,
            ],
            'pembuat_iklan' => [
                'name' => 'Pembuat Iklan',
                'values' => [1 => 'Pelaku Usaha', 2 => 'Perseorangan'],
                'val' => $pembuatIklan,
            ],
        ];

        $activeDimensionInfo = null;
        foreach ($dimensionLabels as $dimKey => $info) {
            if (! empty($info['val'])) {
                $valName = $info['values'][(int) $info['val']] ?? "Kode {$info['val']}";
                $activeDimensionInfo = [
                    'key' => $dimKey,
                    'name' => $info['name'],
                    'value_name' => $valName,
                ];
                if (! $highlightCol) {
                    $highlightCol = $dimKey;
                }
                break;
            }
        }

        if ($request->filled('cari')) {
            $keyword = '%'.trim($request->query('cari')).'%';
            $query->where(function ($q) use ($keyword) {
                $q->where('no', 'like', $keyword)
                    ->orWhereHas('dataMentah', function ($m) use ($keyword) {
                        $m->where('nama_produk', 'like', $keyword)
                            ->orWhere('nomor_izin_edar', 'like', $keyword)
                            ->orWhere('upt', 'like', $keyword);
                    });
            });
        }

        $filters = [
            'tahun' => $tahun,
            'bulan_awal' => $bulanAwal,
            'bulan_akhir' => $bulanAkhir,
            'komoditi' => $komoditi,
            'upt' => $upt,
            'evaluator_id' => $request->query('evaluator_id'),
            'hasil_evaluasi' => $hasilEvaluasi,
            'pelanggaran' => $pelanggaran,
            'asal_produk' => $asalProduk,
            'kategori_media' => $kategoriMedia,
            'kategori_iklan' => $kategoriIklan,
            'pembuat_iklan' => $pembuatIklan,
            'highlight_col' => $highlightCol,
            'cari' => $request->query('cari'),
        ];

        return [$query, $filters, $activePelanggaranName, $activeDimensionInfo];
    }
}
