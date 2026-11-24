<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;
use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\MasterKode;
use App\Models\Upt;
use App\Services\Iklan\DashboardService;
use App\Services\Iklan\ExportStandardisasiService;
use App\Services\Iklan\StandardizationService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Menu Standarisasi Data: menampilkan hasil standarisasi dari dataset yang telah diunggah oleh evaluator terkait.
     */
    public function index(Request $request, ?Dataset $dataset = null): View|RedirectResponse
    {
        $user = auth()->user();
        $isEvaluator = $user && ! $user->isAdmin();

        $rawDatasets = Dataset::orderBy('id', 'desc')->get();

        if ($rawDatasets->isEmpty()) {
            return view('dataset.standardisasi-empty');
        }

        $activeDataset = null;
        if ($dataset && $dataset->exists) {
            $activeDataset = $dataset;
        } elseif ($request->filled('dataset_id')) {
            $activeDataset = $rawDatasets->firstWhere('id', (int) $request->query('dataset_id'));
        } elseif (session()->has('active_dataset_id')) {
            $activeDataset = $rawDatasets->firstWhere('id', (int) session('active_dataset_id'));
        }

        if (! $activeDataset) {
            $activeDataset = $rawDatasets->first();
        }

        $allDatasets = $isEvaluator
            ? $rawDatasets
            : $this->groupDatasetsForAdmin($rawDatasets, $activeDataset);

        return $this->renderStandardisasiView($activeDataset, $request, $allDatasets);
    }

    public function show(Dataset $dataset, Request $request): View|RedirectResponse
    {
        $user = auth()->user();
        $isEvaluator = $user && ! $user->isAdmin();

        $rawDatasets = Dataset::orderBy('id', 'desc')->get();

        $allDatasets = $isEvaluator
            ? $rawDatasets
            : $this->groupDatasetsForAdmin($rawDatasets, $dataset);

        return $this->renderStandardisasiView($dataset, $request, $allDatasets);
    }

    protected function groupDatasetsForAdmin($datasets, ?Dataset $activeDataset = null)
    {
        return $datasets
            ->groupBy(fn (Dataset $d) => $d->group_key)
            ->map(function ($group) use ($activeDataset) {
                $representative = ($activeDataset && $group->contains('id', $activeDataset->id))
                    ? $group->firstWhere('id', $activeDataset->id)
                    : $group->first();
                $representative->jumlah_baris = (int) $group->max('jumlah_baris');

                return $representative;
            })
            ->values();
    }

    protected function getEvaluatedQueryForDataset(Dataset $dataset): Builder
    {
        if (! DataIklan::where('dataset_id', $dataset->id)->exists()) {
            $this->standardizationService->standardizeDataset($dataset);
        }

        $user = auth()->user();

        if ($user && $user->isAdmin()) {
            $siblingSessions = $dataset->getSiblingSessions();
            foreach ($siblingSessions as $sib) {
                $this->standardizationService->syncEvaluatedRows($sib);
            }
            $siblingIds = $siblingSessions->pluck('id')->all();

            $allEvaluatedInGroup = DataIklan::whereIn('dataset_id', $siblingIds)
                ->whereNotNull('evaluator_id')
                ->whereNotNull('kualitas_laporan')
                ->whereNotNull('ketepatan_waktu')
                ->with('dataMentah:id,nomor_baris')
                ->orderBy('updated_at', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $dedupedIds = $allEvaluatedInGroup
                ->unique(fn (DataIklan $di) => $di->dataMentah?->nomor_baris ?? $di->no ?? $di->id)
                ->pluck('id')
                ->all();

            return DataIklan::whereIn('id', $dedupedIds);
        }

        // Pastikan DataIklan selalu sinkron dengan DataMentah & baris yang telah dievaluasi otomatis is_final = true
        $this->standardizationService->syncEvaluatedRows($dataset);

        return DataIklan::where('dataset_id', $dataset->id)
            ->whereNotNull('evaluator_id')
            ->whereNotNull('kualitas_laporan')
            ->whereNotNull('ketepatan_waktu');
    }

    protected function renderStandardisasiView(Dataset $dataset, Request $request, $allDatasets): View
    {
        $dataset->loadMissing('uploader');
        session(['active_dataset_id' => $dataset->id]);
        $user = auth()->user();
        $canEdit = $dataset->canBeEditedBy($user);

        $baseEvaluatedQuery = $this->getEvaluatedQueryForDataset($dataset);

        if ($user && ! $user->isAdmin()) {
            $baseEvaluatedQuery->where('evaluator_id', $user->id);
        } elseif ($request->filled('evaluator_id')) {
            $baseEvaluatedQuery->where('evaluator_id', $request->integer('evaluator_id'));
        }

        // Summary Counters (sesuai baris yang dievaluasi oleh evaluator terkait)
        $totalBaris = (clone $baseEvaluatedQuery)->count();
        $terstandarCount = $totalBaris;
        $perluKoreksiCount = 0;
        $menungguEvaluasiCount = 0;
        $manualTerisiCount = $totalBaris;

        // Query Rows
        $query = (clone $baseEvaluatedQuery)->with(['dataMentah', 'evaluator']);

        $filter = $request->query('filter', 'semua');

        if ($request->filled('upt')) {
            $query->where('upt', (int) $request->query('upt'));
        }

        if ($request->filled('cari')) {
            $keyword = '%'.trim($request->query('cari')).'%';
            $query->whereHas('dataMentah', function ($q) use ($keyword) {
                $q->where('nama_produk', 'like', $keyword)
                    ->orWhere('nomor_izin_edar', 'like', $keyword)
                    ->orWhere('upt', 'like', $keyword)
                    ->orWhere('tempat_pengambilan_iklan', 'like', $keyword);
            });
        }

        $perPage = $request->integer('per_page', 500);
        if (! in_array($perPage, [50, 100, 500, 1000, 5000], true)) {
            $perPage = 500;
        }

        $items = $query
            ->orderByRaw('CASE WHEN upt IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('upt', 'asc')
            ->orderByRaw('CASE WHEN periode_bulan IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('periode_bulan', 'asc')
            ->orderBy('no', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        // Unmapped summaries for resolution panel
        $unmappedSummaries = $this->standardizationService->getUnmappedSummaries($dataset);

        // Tooltip & mapping dictionaries (termasuk Master UPT dinamis)
        $masterDict = MasterKode::all()->groupBy('grup')->map(function ($items) {
            return $items->pluck('nama', 'kode')->toArray();
        })->toArray();
        $masterDict['upt'] = Upt::orderBy('kode')->pluck('nama', 'kode')->toArray();

        $evaluators = \App\Models\User::where('role', 'evaluator')->where('is_active', true)->orderBy('name')->get();
        $uptList = Upt::orderBy('kode')->get(['kode', 'nama']);

        return view('dataset.standardisasi', [
            'dataset' => $dataset,
            'allDatasets' => $allDatasets,
            'items' => $items,
            'canEdit' => $canEdit,
            'evaluators' => $evaluators,
            'uptList' => $uptList,
            'filterUpt' => $request->query('upt'),
            'filterEvaluatorId' => ($user && $user->isAdmin()) ? $request->query('evaluator_id') : null,
            'filter' => $filter,
            'cari' => $request->query('cari'),
            'totalBaris' => $totalBaris,
            'terstandarCount' => $terstandarCount,
            'perluKoreksiCount' => $perluKoreksiCount,
            'menungguEvaluasiCount' => $menungguEvaluasiCount,
            'manualTerisiCount' => $manualTerisiCount,
            'unmappedSummaries' => $unmappedSummaries,
            'masterDict' => $masterDict,
            'unstandardizedWarnings' => $dataset->getUnstandardizedWarnings(),
        ]);
    }

    public function runConversion(Dataset $dataset): RedirectResponse
    {
        if (! $dataset->canBeEditedBy(auth()->user())) {
            return back()->with('error', 'Akses Ditolak: Anda tidak berwenang mengonversi ulang dataset ini.');
        }

        if ($dataset->isFinal()) {
            return back()->with('error', 'Dataset yang sudah Final tidak dapat distandardisasi ulang.');
        }

        $this->standardizationService->standardizeDataset($dataset);
        DashboardService::bumpCacheVersion();

        return back()->with('status', 'Konversi standardisasi berhasil dijalankan ulang.');
    }

    public function updateManualCell(Request $request, Dataset $dataset, DataIklan $item): JsonResponse
    {
        if ((int) $item->dataset_id !== (int) $dataset->id) {
            return response()->json(['error' => 'Data tidak ditemukan pada dataset ini.'], 404);
        }

        $user = auth()->user();
        $canEditRow = $user->isAdmin() ||
            (int) $item->evaluator_id === (int) $user->id ||
            ($item->evaluator_id === null && ($dataset->uploaded_by === null || (int) $dataset->uploaded_by === (int) $user->id));

        if (! $canEditRow) {
            return response()->json(['error' => 'Akses Ditolak: Baris ini ditugaskan kepada evaluator lain.'], 403);
        }

        if ($dataset->isFinal() || $item->is_final) {
            return response()->json(['error' => 'Dataset sudah Final, tidak dapat diubah.'], 403);
        }

        $validated = $request->validate([
            'kolom' => [
                'required',
                'string',
                'in:upt,jenis_produk,asal_produk,klaim,kategori_media,kategori_iklan,nama_media,pencantuman_nie,kesesuaian_nie,rincian_tmk,jenis_tmk,pembuat_iklan,kesesuaian_evaluasi,kualitas_laporan,ketepatan_waktu',
            ],
            'nilai' => ['nullable'],
        ]);

        $kolom = $validated['kolom'];
        $rawNilai = $validated['nilai'];
        $assignedEvaluatorId = $item->evaluator_id ?? $user->id;
        $notes = $item->catatan_tidak_terpetakan ?? [];

        $updates = [
            'evaluator_id' => $assignedEvaluatorId,
        ];

        if ($kolom === 'pencantuman_nie') {
            if ($rawNilai === '1_1') {
                $updates['pencantuman_nie'] = 1;
                $updates['kesesuaian_nie'] = 1;
            } elseif ($rawNilai === '1_2') {
                $updates['pencantuman_nie'] = 1;
                $updates['kesesuaian_nie'] = 2;
            } elseif ($rawNilai !== null && $rawNilai !== '') {
                $valInt = (int) $rawNilai;
                $updates['pencantuman_nie'] = $valInt;
                if ($valInt === 2) {
                    $updates['kesesuaian_nie'] = null;
                } elseif ($valInt === 1 && $item->kesesuaian_nie === null) {
                    $updates['kesesuaian_nie'] = 1;
                }
            } else {
                $updates['pencantuman_nie'] = null;
                $updates['kesesuaian_nie'] = null;
            }
            if ($updates['pencantuman_nie'] !== null) {
                unset($notes['pencantuman_nie']);
            }
        } else {
            $valInt = ($rawNilai !== null && $rawNilai !== '') ? (int) $rawNilai : null;
            $updates[$kolom] = $valInt;
            if ($valInt !== null) {
                unset($notes[$kolom]);
            }
        }

        $updates['catatan_tidak_terpetakan'] = ! empty($notes) ? $notes : null;

        if ($item->hasil_evaluasi === null) {
            $updates['status_standardisasi'] = 'menunggu_evaluasi';
        } else {
            $reqUnmapped = array_diff_key($notes, ['nama_media' => true]);
            $updates['status_standardisasi'] = empty($reqUnmapped) ? 'terstandar' : 'perlu_koreksi';
        }

        $item->update($updates);

        if ($item->dataMentah) {
            $mentahUpdates = ['evaluator_id' => $assignedEvaluatorId];
            if (in_array($kolom, ['kesesuaian_evaluasi', 'kualitas_laporan', 'ketepatan_waktu'])) {
                $mentahUpdates[$kolom] = $updates[$kolom];
            }
            $item->dataMentah->update($mentahUpdates);
        }

        $this->standardizationService->syncEvaluatedRows($dataset);

        $waktu = now();
        DashboardService::bumpCacheVersion();

        return response()->json([
            'success' => true,
            'message' => 'Tersimpan',
            'is_manual_complete' => $item->isManualComplete(),
            'status_standardisasi' => $item->status_standardisasi,
            'pencantuman_nie' => $item->pencantuman_nie,
            'kesesuaian_nie' => $item->kesesuaian_nie,
            'waktu' => [
                'hari' => $waktu->translatedFormat('l'),
                'tanggal' => $waktu->format('d'),
                'bulan' => $waktu->translatedFormat('F'),
                'tahun' => $waktu->format('Y'),
                'jam' => $waktu->format('H:i:s'),
            ],
        ]);
    }

    public function bulkUpdateManual(Request $request, Dataset $dataset): RedirectResponse
    {
        if (! $dataset->canBeEditedBy(auth()->user())) {
            return back()->with('error', 'Akses Ditolak: Anda tidak berwenang mengubah data pada dataset ini.');
        }

        if ($dataset->isFinal()) {
            return back()->with('error', 'Dataset sudah Final, tidak dapat diubah.');
        }

        $validated = $request->validate([
            'kolom' => ['required', 'string', 'in:kesesuaian_evaluasi,kualitas_laporan,ketepatan_waktu'],
            'nilai' => ['required', 'integer', 'in:1,2'],
            'cakupan' => ['nullable', 'string', 'in:semua_dievaluasi,belum_terisi'],
        ]);

        $query = DataIklan::where('dataset_id', $dataset->id)
            ->whereNotNull('hasil_evaluasi'); // Hanya baris yang dihitung/dievaluasi

        if (($validated['cakupan'] ?? 'semua_dievaluasi') === 'belum_terisi') {
            $query->whereNull($validated['kolom']);
        }

        $updatedIds = $query->pluck('id')->toArray();
        $updatedCount = count($updatedIds);

        if ($updatedCount > 0) {
            $userId = auth()->id();
            $autoEvalId = $userId ?? $dataset->uploaded_by;

            DataIklan::whereIn('id', $updatedIds)->update([
                $validated['kolom'] => $validated['nilai'],
            ]);

            $rawIds = DataIklan::whereIn('id', $updatedIds)->pluck('data_mentah_id')->filter()->toArray();
            if (!empty($rawIds)) {
                \App\Models\DataMentah::whereIn('id', $rawIds)->update([
                    $validated['kolom'] => $validated['nilai'],
                ]);
                if ($autoEvalId) {
                    \App\Models\DataMentah::whereIn('id', $rawIds)
                        ->whereNull('evaluator_id')
                        ->update(['evaluator_id' => $autoEvalId]);
                    DataIklan::whereIn('id', $updatedIds)
                        ->whereNull('evaluator_id')
                        ->update(['evaluator_id' => $autoEvalId]);
                }
            }

            $this->standardizationService->syncEvaluatedRows($dataset);
        }

        DashboardService::bumpCacheVersion();

        return back()->with('status', "Berhasil mengisi massal {$updatedCount} baris pada kolom {$validated['kolom']} dengan kode {$validated['nilai']}.");
    }

    public function saveMappingRule(Request $request, Dataset $dataset): RedirectResponse
    {
        if (! $dataset->canBeEditedBy(auth()->user())) {
            return back()->with('error', 'Akses Ditolak: Anda tidak berwenang mengubah aturan pemetaan dataset ini.');
        }

        if ($dataset->isFinal()) {
            return back()->with('error', 'Dataset sudah Final, tidak dapat diubah.');
        }

        $validated = $request->validate([
            'kolom_sumber' => ['required', 'string'],
            'nilai_sipt' => ['required', 'string'],
            'kode' => ['required', 'string'],
            'grup' => ['required', 'string'],
        ]);

        // Jika memilih opsi tambah UPT baru langsung ke Master Data UPT
        if ($validated['grup'] === 'upt' && $validated['kode'] === '__new_upt__') {
            $nextKode = ((int) Upt::max('kode')) + 1;
            $namaUptBaru = trim($validated['nilai_sipt']);
            $uptBaru = Upt::create([
                'kode' => $nextKode,
                'nama' => $namaUptBaru,
            ]);
            $kodeVal = (int) $uptBaru->kode;
        } elseif (in_array($validated['kode'], ['1_1', '1_2'], true)) {
            $kodeVal = $validated['kode'];
        } else {
            $kodeVal = (int) $validated['kode'];
        }

        $this->standardizationService->saveMappingRule(
            $validated['kolom_sumber'],
            $validated['nilai_sipt'],
            $kodeVal,
            $validated['grup'],
            $dataset
        );

        DashboardService::bumpCacheVersion();

        return back()->with('status', "Aturan pemetaan untuk '{$validated['nilai_sipt']}' berhasil disimpan dan diterapkan.");
    }

    public function simpanFinal(Dataset $dataset): RedirectResponse
    {
        if (! $dataset->canBeEditedBy(auth()->user())) {
            return back()->with('error', 'Akses Ditolak: Anda hanya berwenang memfinalkan dataset milik Anda sendiri.');
        }

        try {
            $result = $this->standardizationService->finalizeDataset($dataset);
            DashboardService::bumpCacheVersion();

            return redirect()->route('dataset.show', $dataset)
                ->with('status', "Dataset berhasil difinalkan! Nomor resmi telah diterbitkan dari No. {$result['start_no']} s.d. {$result['end_no']} ({$result['total']} baris data).");
        } catch (Exception $e) {
            return back()->with('error', 'Gagal menyimpan final: '.$e->getMessage());
        }
    }

    public function export(Dataset $dataset, Request $request): StreamedResponse
    {
        $user = auth()->user();
        $query = $this->getEvaluatedQueryForDataset($dataset);

        if ($user && ! $user->isAdmin()) {
            $query->where('evaluator_id', $user->id);
        } elseif ($request->filled('evaluator_id')) {
            $query->where('evaluator_id', $request->integer('evaluator_id'));
        }

        if ($request->filled('upt')) {
            $query->where('upt', (int) $request->query('upt'));
        }

        if ($request->filled('cari')) {
            $keyword = '%'.trim($request->query('cari')).'%';
            $query->whereHas('dataMentah', function ($q) use ($keyword) {
                $q->where('nama_produk', 'like', $keyword)
                    ->orWhere('nomor_izin_edar', 'like', $keyword)
                    ->orWhere('upt', 'like', $keyword)
                    ->orWhere('tempat_pengambilan_iklan', 'like', $keyword);
            });
        }

        $query
            ->orderByRaw('CASE WHEN upt IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('upt', 'asc')
            ->orderByRaw('CASE WHEN periode_bulan IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('periode_bulan', 'asc')
            ->orderBy('no', 'asc')
            ->orderBy('id', 'asc');

        $namaBulan = $dataset->nama_bulan;
        $filename = "Data_Terstandarisasi_{$dataset->periode_tahun}_{$namaBulan}_{$dataset->komoditi}";

        if ($request->filled('upt')) {
            $filename .= "_UPT_" . $request->query('upt');
        }

        if ($request->filled('evaluator_id')) {
            $ev = \App\Models\User::find($request->integer('evaluator_id'));
            if ($ev) {
                $cleanName = \Illuminate\Support\Str::slug($ev->name, '_');
                $filename .= "_{$cleanName}";
            }
        }

        $filename .= ".xlsx";

        return $this->exportService->download($query, $filename);
    }

    /**
     * Ekspor data standarisasi agregat dengan filter bulan, tahun, komoditi, dan evaluator.
     */
    public function exportAll(Request $request): StreamedResponse
    {
        $query = DataIklan::query()->with(['dataMentah', 'evaluator', 'dataset']);

        if ($request->filled('komoditi') && $request->query('komoditi') !== 'SEMUA') {
            $komFilter = strtoupper((string) $request->query('komoditi'));
            if ($komFilter === 'OT' || $komFilter === 'OBA') {
                $query->where(function ($q) {
                    $q->where('jenis_produk', 1)
                        ->orWhere(fn ($sub) => $sub->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->whereIn('komoditi', ['OBA', 'OT'])));
                });
            } elseif ($komFilter === 'OK') {
                $query->where(function ($q) {
                    $q->where('jenis_produk', 3)
                        ->orWhere(fn ($sub) => $sub->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->where('komoditi', 'OK')));
                });
            } elseif ($komFilter === 'SK') {
                $query->where(function ($q) {
                    $q->where('jenis_produk', 2)
                        ->orWhere(fn ($sub) => $sub->whereNull('jenis_produk')->whereHas('dataset', fn ($d) => $d->where('komoditi', 'SK')));
                });
            } else {
                $query->whereHas('dataset', fn ($q) => $q->where('komoditi', $komFilter));
            }
        }
        if ($request->filled('tahun')) {
            $query->where('periode_tahun', $request->integer('tahun'));
        }
        if ($request->filled('bulan')) {
            $query->where('periode_bulan', $request->integer('bulan'));
        }
        $user = auth()->user();
        if ($user && $user->isAdmin() && $request->filled('evaluator_id')) {
            $query->where('evaluator_id', $request->integer('evaluator_id'));
        } elseif ($user && ! $user->isAdmin()) {
            $query->where('evaluator_id', $user->id);
        }

        $query->orderBy('periode_tahun', 'desc')
            ->orderByRaw('CASE WHEN upt IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('upt', 'asc')
            ->orderByRaw('CASE WHEN periode_bulan IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('periode_bulan', 'asc')
            ->orderBy('dataset_id', 'asc')
            ->orderBy('no', 'asc')
            ->orderBy('id', 'asc');

        $komoditi = $request->query('komoditi', 'SEMUA');
        $tahun = $request->query('tahun', now()->year);
        $bulan = $request->filled('bulan') ? "_Bulan_" . $request->integer('bulan') : '';
        $filename = "Data_Standarisasi_{$tahun}{$bulan}_{$komoditi}.xlsx";

        return $this->exportService->download($query, $filename);
    }
}
