<?php

namespace App\Http\Controllers;

use App\Models\DataIklan;
use App\Models\Dataset;
use App\Models\MasterKode;
use App\Services\ExportStandardisasiService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTersimpanController extends Controller
{
    public function __construct(
        protected ExportStandardisasiService $exportService
    ) {}

    public function index(Request $request): View
    {
        $query = DataIklan::where('is_final', true)->with('dataMentah');

        $tahun = $request->integer('tahun', now()->year);
        $bulanAwal = $request->integer('bulan_awal', 1);
        $bulanAkhir = $request->integer('bulan_akhir', 12);
        $komoditi = $request->query('komoditi');

        if ($tahun) {
            $query->where('periode_tahun', $tahun);
        }

        if ($bulanAwal && $bulanAkhir) {
            $query->whereBetween('periode_bulan', [$bulanAwal, $bulanAkhir]);
        }

        if ($komoditi) {
            $query->whereHas('dataset', function ($q) use ($komoditi) {
                $q->where('komoditi', $komoditi);
            });
        }

        if ($request->filled('cari')) {
            $keyword = '%' . trim($request->query('cari')) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('no', 'like', $keyword)
                    ->orWhereHas('dataMentah', function ($m) use ($keyword) {
                        $m->where('nama_produk', 'like', $keyword)
                            ->orWhere('nomor_izin_edar', 'like', $keyword)
                            ->orWhere('upt', 'like', $keyword);
                    });
            });
        }

        $totalFinal = (clone $query)->count();
        $items = $query->orderBy('no', 'asc')->paginate(100)->withQueryString();

        $masterDict = MasterKode::all()->groupBy('grup')->map(function ($g) {
            return $g->pluck('nama', 'kode')->toArray();
        });

        return view('data_tersimpan.index', [
            'items' => $items,
            'totalFinal' => $totalFinal,
            'filters' => [
                'tahun' => $tahun,
                'bulan_awal' => $bulanAwal,
                'bulan_akhir' => $bulanAkhir,
                'komoditi' => $komoditi,
                'cari' => $request->query('cari'),
            ],
            'masterDict' => $masterDict,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = DataIklan::where('is_final', true);

        $tahun = $request->integer('tahun', now()->year);
        $bulanAwal = $request->integer('bulan_awal', 1);
        $bulanAkhir = $request->integer('bulan_akhir', 12);
        $komoditi = $request->query('komoditi');

        if ($tahun) {
            $query->where('periode_tahun', $tahun);
        }

        if ($bulanAwal && $bulanAkhir) {
            $query->whereBetween('periode_bulan', [$bulanAwal, $bulanAkhir]);
        }

        if ($komoditi) {
            $query->whereHas('dataset', function ($q) use ($komoditi) {
                $q->where('komoditi', $komoditi);
            });
        }

        $query->orderBy('no', 'asc');

        $filename = "Data_Standardisasi_{$tahun}_{$bulanAwal}-{$bulanAkhir}.xlsx";

        return $this->exportService->download($query, $filename);
    }
}
