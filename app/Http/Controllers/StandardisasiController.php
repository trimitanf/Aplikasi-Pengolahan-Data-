<?php

namespace App\Http\Controllers;

use App\Models\DataIklan;
use App\Models\Dataset;
use App\Models\MasterKode;
use App\Services\ExportStandardisasiService;
use App\Services\StandardizationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StandardisasiController extends Controller
{
    public function __construct(
        protected StandardizationService $standardizationService,
        protected ExportStandardisasiService $exportService
    ) {}

    public function show(Dataset $dataset, Request $request): View
    {
        // Jika data_iklan belum terbentuk, jalankan standardisasi awal
        if (! DataIklan::where('dataset_id', $dataset->id)->exists()) {
            $this->standardizationService->standardizeDataset($dataset);
        }

        // Summary Counters
        $totalBaris = DataIklan::where('dataset_id', $dataset->id)->count();
        $terstandarCount = DataIklan::where('dataset_id', $dataset->id)
            ->where('status_standardisasi', 'terstandar')
            ->count();
        $perluKoreksiCount = DataIklan::where('dataset_id', $dataset->id)
            ->where('status_standardisasi', 'perlu_koreksi')
            ->count();
        $menungguEvaluasiCount = DataIklan::where('dataset_id', $dataset->id)
            ->where('status_standardisasi', 'menunggu_evaluasi')
            ->count();
        $manualTerisiCount = DataIklan::where('dataset_id', $dataset->id)
            ->whereNotNull('kesesuaian_evaluasi')
            ->whereNotNull('kualitas_laporan')
            ->whereNotNull('ketepatan_waktu')
            ->count();

        // Query Rows
        $query = DataIklan::where('dataset_id', $dataset->id)->with('dataMentah');

        $filter = $request->query('filter', 'semua');
        if ($filter === 'perlu_koreksi') {
            $query->where('status_standardisasi', 'perlu_koreksi');
        } elseif ($filter === 'menunggu_evaluasi') {
            $query->where('status_standardisasi', 'menunggu_evaluasi');
        } elseif ($filter === 'belum_manual') {
            $query->where(function ($q) {
                $q->whereNull('kesesuaian_evaluasi')
                    ->orWhereNull('kualitas_laporan')
                    ->orWhereNull('ketepatan_waktu');
            })->whereNotNull('hasil_evaluasi');
        }

        if ($request->filled('cari')) {
            $keyword = '%' . trim($request->query('cari')) . '%';
            $query->whereHas('dataMentah', function ($q) use ($keyword) {
                $q->where('nama_produk', 'like', $keyword)
                    ->orWhere('nomor_izin_edar', 'like', $keyword)
                    ->orWhere('upt', 'like', $keyword)
                    ->orWhere('tempat_pengambilan_iklan', 'like', $keyword);
            });
        }

        $items = $query->orderBy('no', 'asc')->paginate(100)->withQueryString();

        // Unmapped summaries for resolution panel
        $unmappedSummaries = $this->standardizationService->getUnmappedSummaries($dataset);

        // Tooltip dictionaries
        $masterDict = MasterKode::all()->groupBy('grup')->map(function ($items) {
            return $items->pluck('nama', 'kode')->toArray();
        });

        return view('dataset.standardisasi', [
            'dataset' => $dataset,
            'items' => $items,
            'filter' => $filter,
            'cari' => $request->query('cari'),
            'totalBaris' => $totalBaris,
            'terstandarCount' => $terstandarCount,
            'perluKoreksiCount' => $perluKoreksiCount,
            'menungguEvaluasiCount' => $menungguEvaluasiCount,
            'manualTerisiCount' => $manualTerisiCount,
            'unmappedSummaries' => $unmappedSummaries,
            'masterDict' => $masterDict,
        ]);
    }

    public function runConversion(Dataset $dataset): RedirectResponse
    {
        if ($dataset->isFinal()) {
            return back()->with('error', 'Dataset yang sudah Final tidak dapat distandardisasi ulang.');
        }

        $this->standardizationService->standardizeDataset($dataset);

        return back()->with('status', 'Konversi standardisasi berhasil dijalankan ulang.');
    }

    public function updateManualCell(Request $request, Dataset $dataset, DataIklan $item): JsonResponse
    {
        if ($dataset->isFinal()) {
            return response()->json(['error' => 'Dataset sudah Final, tidak dapat diubah.'], 403);
        }

        $validated = $request->validate([
            'kolom' => ['required', 'string', 'in:kesesuaian_evaluasi,kualitas_laporan,ketepatan_waktu'],
            'nilai' => ['nullable', 'integer', 'in:1,2'],
        ]);

        $item->update([
            $validated['kolom'] => $validated['nilai'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tersimpan',
            'is_manual_complete' => $item->isManualComplete(),
        ]);
    }

    public function bulkUpdateManual(Request $request, Dataset $dataset): RedirectResponse
    {
        if ($dataset->isFinal()) {
            return back()->with('error', 'Dataset sudah Final, tidak dapat diubah.');
        }

        $validated = $request->validate([
            'kolom' => ['required', 'string', 'in:kesesuaian_evaluasi,kualitas_laporan,ketepatan_waktu'],
            'nilai' => ['required', 'integer', 'in:1,2'],
            'cakupan' => ['required', 'string', 'in:semua_dievaluasi,belum_terisi'],
        ]);

        $query = DataIklan::where('dataset_id', $dataset->id)
            ->whereNotNull('hasil_evaluasi'); // Hanya baris yang dihitung/dievaluasi

        if ($validated['cakupan'] === 'belum_terisi') {
            $query->whereNull($validated['kolom']);
        }

        $updatedCount = $query->update([
            $validated['kolom'] => $validated['nilai'],
        ]);

        return back()->with('status', "Berhasil mengisi massal {$updatedCount} baris pada kolom {$validated['kolom']} dengan kode {$validated['nilai']}.");
    }

    public function saveMappingRule(Request $request, Dataset $dataset): RedirectResponse
    {
        if ($dataset->isFinal()) {
            return back()->with('error', 'Dataset sudah Final, tidak dapat diubah.');
        }

        $validated = $request->validate([
            'kolom_sumber' => ['required', 'string'],
            'nilai_sipt' => ['required', 'string'],
            'kode' => ['required', 'integer'],
            'grup' => ['required', 'string'],
        ]);

        $this->standardizationService->saveMappingRule(
            $validated['kolom_sumber'],
            $validated['nilai_sipt'],
            (int) $validated['kode'],
            $validated['grup'],
            $dataset
        );

        return back()->with('status', "Aturan pemetaan untuk '{$validated['nilai_sipt']}' berhasil disimpan dan diterapkan.");
    }

    public function simpanFinal(Dataset $dataset): RedirectResponse
    {
        try {
            $result = $this->standardizationService->finalizeDataset($dataset);

            return redirect()->route('dataset.show', $dataset)
                ->with('status', "Dataset berhasil difinalkan! Nomor resmi telah diterbitkan dari No. {$result['start_no']} s.d. {$result['end_no']} ({$result['total']} baris data).");
        } catch (Exception $e) {
            return back()->with('error', 'Gagal menyimpan final: ' . $e->getMessage());
        }
    }

    public function export(Dataset $dataset): StreamedResponse
    {
        $query = DataIklan::where('dataset_id', $dataset->id)->orderBy('no', 'asc');
        $namaBulan = $dataset->nama_bulan;
        $filename = "Data_Standardisasi_{$dataset->periode_tahun}_{$namaBulan}_{$dataset->komoditi}.xlsx";

        return $this->exportService->download($query, $filename);
    }
}
