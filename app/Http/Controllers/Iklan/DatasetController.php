<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;

use App\Actions\Iklan\AssignEvaluatorAction;
use App\Actions\Iklan\UpdateManualEvaluationAction;
use App\Http\Requests\UploadDatasetRequest;
use App\Jobs\ProsesDatasetJob;
use App\Models\DataIklan;
use App\Models\DataMentah;
use App\Models\Dataset;
use App\Models\Upt;
use App\Models\User;
use App\Services\Iklan\DashboardService;
use App\Services\Iklan\DatasetService;
use App\Services\Iklan\SiptDatasetReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatasetController extends Controller
{
    public function __construct(
        protected DatasetService $datasetService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $isEvaluator = $user && ! $user->isAdmin();

        $statsBase = Dataset::query();
        $trashBase = Dataset::onlyTrashed();

        $query = Dataset::query()->with('uploader');

        // Model Terpusat & Kolaboratif: Seluruh pengguna melihat dataset master yang diunggah Admin/PIC

        if ($request->filled('komoditi')) {
            $komoditiFilter = $request->query('komoditi');
            if ($komoditiFilter === 'OT' || $komoditiFilter === 'OBA') {
                $query->whereIn('komoditi', ['OBA', 'OT']);
            } elseif ($komoditiFilter === 'OK') {
                $query->where('komoditi', 'OK');
            } elseif ($komoditiFilter === 'SK') {
                $query->where('komoditi', 'SK');
            } else {
                $query->where('komoditi', $komoditiFilter);
            }
        }

        if ($request->filled('tahun')) {
            $query->where('periode_tahun', $request->integer('tahun'));
        }

        if ($request->filled('status')) {
            $statusFilter = $request->query('status');
            if ($statusFilter === 'Sudah Diproses') {
                $query->whereIn('status', ['Final', 'Sudah Diproses']);
            } elseif ($statusFilter === 'Diproses') {
                $query->whereNotIn('status', ['Final', 'Sudah Diproses']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if ($request->filled('uploaded_by')) {
            $query->where('uploaded_by', $request->integer('uploaded_by'));
        }

        if ($request->filled('upt')) {
            $uptVal = $request->query('upt');
            $selectedUpt = is_numeric($uptVal)
                ? Upt::where('kode', (int) $uptVal)->first()
                : Upt::where('nama', $uptVal)->first();

            if ($selectedUpt) {
                $baseLocation = $this->datasetService->cleanUptName($selectedUpt->nama);
                $query->where(function ($q) use ($selectedUpt, $baseLocation) {
                    $q->whereHas('dataMentah', function ($dm) use ($selectedUpt, $baseLocation) {
                        if ($baseLocation) {
                            $dm->where('upt', 'like', '%' . $baseLocation . '%');
                        }
                        $dm->orWhereHas('dataIklan', fn($di) => $di->where('upt', $selectedUpt->kode));
                    })->orWhereHas('dataIklan', function ($di) use ($selectedUpt) {
                        $di->where('upt', $selectedUpt->kode);
                    });
                });
            }
        }

        $evaluators = User::where('role', 'evaluator')->where('is_active', true)->orderBy('name')->get();
        $uptList = Upt::orderBy('kode')->get(['kode', 'nama']);

        // Seluruh pengguna (Admin & Evaluator) melihat ringkasan berkas master kolaboratif
        $allFiltered = $query->orderBy('id', 'desc')->get();
        $allFilteredIds = $allFiltered->pluck('id')->all();

        // Optimasi SQL Aggregation: Ambil jumlah unik baris terevaluasi per dataset_id tanpa memuat seluruh model ke RAM
        $evaluatedCounts = ! empty($allFilteredIds)
            ? DataMentah::whereIn('dataset_id', $allFilteredIds)
                ->whereNotNull('kualitas_laporan')
                ->whereNotNull('ketepatan_waktu')
                ->selectRaw('dataset_id, COUNT(DISTINCT nomor_baris) as total_evaluated')
                ->groupBy('dataset_id')
                ->pluck('total_evaluated', 'dataset_id')
            : collect();

        $groupedCollection = $allFiltered
            ->groupBy(fn (Dataset $d) => $d->group_key)
            ->map(function ($group) use ($evaluatedCounts) {
                /** @var Dataset $primary */
                $primary = $group->first();
                $totalBaris = (int) $group->max('jumlah_baris');
                $menunggu = (int) $group->max('jumlah_menunggu_evaluasi');
                $wajibEvaluasi = max(0, $totalBaris - $menunggu);
                if ($wajibEvaluasi === 0 && $totalBaris > 0) {
                    $wajibEvaluasi = $totalBaris;
                }

                $uniqueEvaluatedCount = min(
                    $totalBaris,
                    (int) $group->sum(fn (Dataset $ds) => (int) ($evaluatedCounts[$ds->id] ?? 0))
                );
                $progressPercent = $wajibEvaluasi > 0
                    ? min(100, (int) round(($uniqueEvaluatedCount / $wajibEvaluasi) * 100))
                    : 0;
                $isGroupComplete = ($wajibEvaluasi > 0 && $uniqueEvaluatedCount >= $wajibEvaluasi)
                    || $group->contains(fn (Dataset $d) => $d->isFinal());

                // Hitung kontribusi per sesi evaluator
                foreach ($group as $sesi) {
                    $sesi->session_evaluated_count = (int) ($evaluatedCounts[$sesi->id] ?? 0);
                }

                $primary->setRelation('groupedSessions', $group->values());
                $primary->sessions_count = $group->count();
                $primary->jumlah_baris = $totalBaris;
                $primary->wajib_evaluasi = $wajibEvaluasi;
                $primary->evaluated_count = $uniqueEvaluatedCount;
                $primary->progress_percent = $progressPercent;
                $primary->is_group_complete = $isGroupComplete;

                if ($isGroupComplete) {
                    $primary->status = 'Final';
                }

                return $primary;
            })
            ->values();

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $currentItems = $groupedCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $datasets = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $groupedCollection->count(),
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        $allStatsDatasets = (clone $statsBase)->orderBy('id', 'desc')->get();
        $allStatsIds = $allStatsDatasets->pluck('id')->all();
        $statsEvaluatedCounts = ! empty($allStatsIds)
            ? DataMentah::whereIn('dataset_id', $allStatsIds)
                ->whereNotNull('kualitas_laporan')
                ->whereNotNull('ketepatan_waktu')
                ->selectRaw('dataset_id, COUNT(DISTINCT nomor_baris) as total_evaluated')
                ->groupBy('dataset_id')
                ->pluck('total_evaluated', 'dataset_id')
            : collect();

        $groupedStats = $allStatsDatasets->groupBy(fn (Dataset $d) => $d->group_key);

        $totalBarisUnik = 0;
        $totalWajibUnik = 0;
        $totalTerevaluasiUnik = 0;

        foreach ($groupedStats as $grp) {
            $maxBaris = (int) $grp->max('jumlah_baris');
            $maxMenunggu = (int) $grp->max('jumlah_menunggu_evaluasi');
            $wajib = max(0, $maxBaris - $maxMenunggu);
            if ($wajib === 0 && $maxBaris > 0) {
                $wajib = $maxBaris;
            }
            $evalCnt = min(
                $maxBaris,
                (int) $grp->sum(fn (Dataset $ds) => (int) ($statsEvaluatedCounts[$ds->id] ?? 0))
            );

            $totalBarisUnik += $maxBaris;
            $totalWajibUnik += $wajib;
            $totalTerevaluasiUnik += $evalCnt;
        }

        $stats = [
            'total_dataset' => $groupedStats->count(),
            'total_baris' => $totalBarisUnik,
            'total_wajib_evaluasi' => $totalWajibUnik,
            'total_baris_terevaluasi' => $totalTerevaluasiUnik,
            'total_valid' => $groupedStats->filter(
                fn ($grp) => $grp->contains(fn (Dataset $d) => in_array($d->status, ['Valid', 'Terstandardisasi', 'Final'], true))
            )->count(),
            'total_sampah' => (clone $trashBase)->count(),
        ];

        return view('dataset.index', [
            'datasets' => $datasets,
            'stats' => $stats,
            'evaluators' => $evaluators,
            'uptList' => $uptList,
            'filters' => [
                'komoditi' => $request->query('komoditi'),
                'tahun' => $request->query('tahun'),
                'upt' => $request->query('upt'),
                'status' => $request->query('status'),
                'uploaded_by' => $request->query('uploaded_by'),
            ],
        ]);
    }

    public function store(UploadDatasetRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $namaFileAsli = $file->getClientOriginalName();
        $komoditiInput = strtoupper(trim((string) $request->input('komoditi')));
        $periodeTahun = $request->integer('periode_tahun');
        $periodeBulan = $request->integer('periode_bulan');

        // Simpan file ke disk private
        $path = $file->store('datasets', 'private');

        $dataset = Dataset::create([
            'uploaded_by' => auth()->id(),
            'nama_file_asli' => $namaFileAsli,
            'path_file' => 'private/'.$path,
            'komoditi' => $komoditiInput,
            'periode_tahun' => $periodeTahun,
            'periode_bulan' => $periodeBulan,
            'status' => 'Diunggah',
            'uploaded_at' => now(),
        ]);

        // Jalankan pemrosesan via Job antrean
        // Fallback cerdas: Jika queue driver adalah sync atau ukuran berkas dalam batas wajar (< 10 MB / < 2.000 baris),
        // gunakan dispatchSync() agar pemrosesan langsung tuntas tanpa menggantung jika queue:work lupa dijalankan di server.
        if (config('queue.default') === 'sync' || $file->getSize() < 10 * 1024 * 1024) {
            ProsesDatasetJob::dispatchSync($dataset);
        } else {
            ProsesDatasetJob::dispatch($dataset);
        }

        $dataset->refresh();
        session(['active_dataset_id' => $dataset->id]);

        if ($dataset->status === 'Gagal') {
            $errorMessage = $dataset->issues()->where('tingkat', 'error')->latest()->value('pesan')
                ?? "Gagal memproses file '{$namaFileAsli}'.";
            return redirect()->route('dataset.show', $dataset)
                ->with('error', $errorMessage);
        }

        $warnings = $dataset->getUnstandardizedWarnings();
        if (! empty($warnings['upt'])) {
            $uptListStr = implode(', ', $warnings['upt']);
            session()->flash('warning', "Pemberitahuan: Ditemukan nama UPT pada dataset ini yang belum terdaftar di Master Data ({$uptListStr}). Silakan hubungi Administrator untuk menambahkan kode UPT baru ke Kamus Master Data.");
        }

        DashboardService::bumpCacheVersion();

        return redirect()->route('dataset.show', $dataset)
            ->with('status', "File '{$namaFileAsli}' berhasil diunggah dan diproses oleh sistem.");
    }

    public function show(Dataset $dataset, Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        // 1. Cek jika pengguna memilih filter Tahun / Komoditi / Bulan yang mengarah ke Dataset lain
        $filterTahun = $request->filled('tahun') ? $request->integer('tahun') : $dataset->periode_tahun;
        $filterKomoditi = $request->filled('komoditi') ? $request->query('komoditi') : $dataset->komoditi;
        $filterBulan = $request->filled('bulan') ? $request->integer('bulan') : $dataset->periode_bulan;

        $hasCommodityMismatch = false;

        if (
            ($request->has('tahun') || $request->has('komoditi') || $request->has('bulan')) &&
            ($filterTahun !== $dataset->periode_tahun || $filterKomoditi !== $dataset->komoditi || $filterBulan !== $dataset->periode_bulan)
        ) {
            $matchedQuery = Dataset::where('periode_tahun', $filterTahun)
                ->where('periode_bulan', $filterBulan);
            if ($user && ! $user->isAdmin()) {
                $matchedQuery->where(function ($q) use ($user) {
                    $q->where('uploaded_by', $user->id)
                        ->orWhereNull('uploaded_by');
                });
            }
            if ($filterKomoditi === 'OBA' || $filterKomoditi === 'OT') {
                $matchedQuery->whereIn('komoditi', ['OBA', 'OT']);
            } else {
                $matchedQuery->where('komoditi', $filterKomoditi);
            }
            $matchedDataset = $matchedQuery->latest('id')->first();

            if ($matchedDataset && $matchedDataset->id !== $dataset->id) {
                return redirect()->route('dataset.show', [
                    'dataset' => $matchedDataset->id,
                    'cari' => $request->query('cari'),
                    'upt' => $request->query('upt'),
                ]);
            }

            if (! $matchedDataset) {
                $hasCommodityMismatch = true;
            }
        }

        $dataset->loadMissing('uploader');
        session(['active_dataset_id' => $dataset->id]);

        $canEdit = $dataset->canBeEditedBy($user);

        // Model Kolaboratif Bersama: Seluruh pengguna melihat tabel terpadu dengan hasil kerja rekan lain
        $isAdmin = $user && $user->isAdmin();
        $siblingSessions = $dataset->getSiblingSessions();
        $siblingIds = $siblingSessions->pluck('id')->all();
        if (! in_array($dataset->id, $siblingIds, true)) {
            $siblingIds[] = $dataset->id;
        }
        $isSpecificSession = false;
        $evalFilterId = $request->filled('evaluator_id') ? (int) $request->query('evaluator_id') : null;

        // Peta baris hasil kerja evaluator (per nomor_baris) dari seluruh sesi untuk sinkronisasi tampilan
        $mergedRowMap = DataMentah::with('evaluator')
            ->whereIn('dataset_id', $siblingIds)
            ->whereNotNull('evaluator_id')
            ->where(function ($q) {
                $q->whereNotNull('kualitas_laporan')->orWhereNotNull('ketepatan_waktu');
            })
            ->get()
            ->groupBy('nomor_baris')
            ->map(function ($grp) use ($evalFilterId) {
                return $grp->sortBy([
                    fn ($a, $b) => (int) ($evalFilterId && $b->evaluator_id === $evalFilterId) <=> (int) ($evalFilterId && $a->evaluator_id === $evalFilterId),
                    fn ($a, $b) => (int) ($b->kualitas_laporan !== null && $b->ketepatan_waktu !== null) <=> (int) ($a->kualitas_laporan !== null && $a->ketepatan_waktu !== null),
                    fn ($a, $b) => strcmp((string) $b->updated_at, (string) $a->updated_at),
                ])->first();
            });

        // 2. Query Data Mentah (26 kolom SIPT)
        $rawQuery = $dataset->dataMentah()->with('evaluator');

        if ($hasCommodityMismatch) {
            $rawQuery->whereRaw('1 = 0');
        }

        if ($request->filled('upt')) {
            $uptVal = $request->query('upt');
            $selectedUpt = is_numeric($uptVal)
                ? Upt::where('kode', (int) $uptVal)->first()
                : Upt::where('nama', $uptVal)->first();

            if ($selectedUpt) {
                $baseLocation = $this->datasetService->cleanUptName($selectedUpt->nama);
                $rawQuery->where(function ($q) use ($selectedUpt, $baseLocation) {
                    if ($baseLocation) {
                        $q->where('upt', 'like', '%' . $baseLocation . '%');
                    }
                    $q->orWhereHas('dataIklan', function ($di) use ($selectedUpt) {
                        $di->where('upt', $selectedUpt->kode);
                    });
                });
            }
        }

        if ($evalFilterId) {
            $rawQuery->where('evaluator_id', $evalFilterId);
        }

        // Filter Status Tab (Tugas Saya, Belum Diambil, Selesai, Semua) & Toggle Sembunyikan Selesai
        $statusTugas = $request->query('status_tugas', 'semua');
        $hideCompleted = $request->boolean('sembunyikan_selesai');

        if ($statusTugas === 'tugas_saya' && $user) {
            $rawQuery->where('evaluator_id', $user->id);
            if ($hideCompleted) {
                $rawQuery->where(function ($q) {
                    $q->whereNull('kualitas_laporan')->orWhereNull('ketepatan_waktu');
                });
            }
        } elseif ($statusTugas === 'belum_diambil') {
            $rawQuery->whereNull('evaluator_id');
        } elseif ($statusTugas === 'selesai') {
            $rawQuery->whereNotNull('kualitas_laporan')->whereNotNull('ketepatan_waktu');
        } elseif ($statusTugas === 'belum_selesai') {
            $rawQuery->where(function ($q) {
                $q->whereNull('kualitas_laporan')->orWhereNull('ketepatan_waktu');
            });
        } elseif ($hideCompleted) {
            $rawQuery->where(function ($q) {
                $q->whereNull('kualitas_laporan')->orWhereNull('ketepatan_waktu');
            });
        }

        if ($request->filled('cari')) {
            $keyword = '%'.trim($request->query('cari')).'%';
            $rawQuery->where(function ($q) use ($keyword) {
                $q->where('upt', 'like', $keyword)
                    ->orWhere('nama_produk', 'like', $keyword)
                    ->orWhere('nomor_izin_edar', 'like', $keyword)
                    ->orWhere('khasiat_indikasi', 'like', $keyword)
                    ->orWhere('pendaftar', 'like', $keyword)
                    ->orWhere('media_iklan', 'like', $keyword)
                    ->orWhere('kesimpulan_upt', 'like', $keyword)
                    ->orWhere('kesimpulan_pusat', 'like', $keyword);
            });
        }

        $perPage = $request->integer('per_page', 500);
        if (! in_array($perPage, [50, 100, 500, 1000, 5000], true)) {
            $perPage = 500;
        }
        $rows = $rawQuery->orderBy('nomor_baris', 'asc')->orderBy('id', 'asc')->paginate($perPage)->withQueryString();

        // Tampilkan hasil kerja evaluator (lintas sesi jika ada) pada tiap baris
        foreach ($rows->getCollection() as $row) {
            $src = $mergedRowMap->get($row->nomor_baris);
            if ($src) {
                if ($row->kualitas_laporan === null && $src->kualitas_laporan !== null) {
                    $row->kualitas_laporan = $src->kualitas_laporan;
                }
                if ($row->ketepatan_waktu === null && $src->ketepatan_waktu !== null) {
                    $row->ketepatan_waktu = $src->ketepatan_waktu;
                }
                if ($row->kesesuaian_evaluasi === null && $src->kesesuaian_evaluasi !== null) {
                    $row->kesesuaian_evaluasi = $src->kesesuaian_evaluasi;
                }
                if ($row->evaluator_id === null && $src->evaluator_id !== null) {
                    $row->evaluator_id = $src->evaluator_id;
                    $row->setRelation('evaluator', $src->evaluator);
                }
                if ($src->updated_at) {
                    $row->updated_at = $src->updated_at;
                }
            }
        }

        // Ringkasan pekerjaan per evaluator: jumlah baris & daftar UPT yang dikerjakan
        $evaluatorWorkSummary = $mergedRowMap
            ->groupBy('evaluator_id')
            ->map(fn ($grp) => [
                'nama' => $grp->first()->evaluator?->name ?? 'Evaluator',
                'jumlah' => $grp->count(),
                'upt' => $grp->pluck('upt')->filter()->unique()->sort()->values(),
            ]);

        // 3. Issue pemeriksaan (Non-blocking catatan validasi)
        $issueQuery = $dataset->issues();
        if ($request->filled('tingkat')) {
            $issueQuery->where('tingkat', $request->query('tingkat'));
        }
        $issues = $issueQuery->orderBy('nomor_baris', 'asc')->get();

        $errorCount = $dataset->issues()->where('tingkat', 'error')->count();
        $warningCount = $dataset->issues()->where('tingkat', 'warning')->count();

        if ($hasCommodityMismatch) {
            $manualTerisiCount = 0;
            $errorCount = 0;
            $warningCount = 0;
            $progressStats = ['total' => 0, 'terisi' => 0, 'persen' => 0];
            $evaluatorWorkSummary = collect();
            $canEdit = false;
        } else {
            $manualTerisiCount = DataMentah::where('dataset_id', $dataset->id)
                ->whereNotNull('kualitas_laporan')
                ->whereNotNull('ketepatan_waktu')
                ->count();
            $progressStats = $dataset->progressStats();
        }

        $evaluators = User::where('role', 'evaluator')->where('is_active', true)->orderBy('name')->get();
        $masterUptList = Upt::orderBy('kode')->get(['kode', 'nama']);

        // Ringkasan Status Tiap UPT untuk Dropdown Pintar dan Panel Klaim Mandiri
        $rawUptStats = DataMentah::where('dataset_id', $dataset->id)
            ->select('upt')
            ->selectRaw('count(*) as total_baris')
            ->selectRaw('count(CASE WHEN kualitas_laporan IS NOT NULL AND ketepatan_waktu IS NOT NULL THEN 1 END) as selesai_count')
            ->selectRaw('min(evaluator_id) as min_eval')
            ->selectRaw('max(evaluator_id) as max_eval')
            ->groupBy('upt')
            ->get();

        $uptStatusList = collect();
        $currentUptInfo = null;
        $activeUptFilter = $request->query('upt');

        foreach ($rawUptStats as $stat) {
            $rawUptName = (string) $stat->upt;
            $matchedMaster = $masterUptList->first(function ($m) use ($rawUptName) {
                $baseLoc = $this->datasetService->cleanUptName($m->nama);
                return $baseLoc && str_contains(strtolower($rawUptName), $baseLoc);
            });

            $evalId = ($stat->min_eval === $stat->max_eval) ? $stat->min_eval : null;
            $evalUser = $evalId ? $evaluators->firstWhere('id', $evalId) : null;
            $isMine = $user && $evalId && (int) $user->id === (int) $evalId;
            $isComplete = $stat->selesai_count >= $stat->total_baris && $stat->total_baris > 0;

            if ($evalId === null) {
                $claimStatus = 'available';
                $statusLabel = 'Tersedia';
                $statusBadgeClass = 'bg-slate-100 text-slate-700 border-slate-300';
            } elseif ($isMine) {
                $claimStatus = $isComplete ? 'mine_done' : 'mine_in_progress';
                $statusLabel = $isComplete ? 'Milik Anda (Selesai)' : 'Milik Anda (Proses)';
                $statusBadgeClass = $isComplete ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-blue-100 text-blue-800 border-blue-300';
            } else {
                $claimStatus = $isComplete ? 'other_done' : 'other_in_progress';
                $statusLabel = $isComplete ? 'Selesai: ' . ($evalUser?->name ?? 'Evaluator') : 'Dikerjakan: ' . ($evalUser?->name ?? 'Evaluator');
                $statusBadgeClass = $isComplete ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-300';
            }

            $uptItem = [
                'raw_name' => $rawUptName,
                'kode' => $matchedMaster?->kode,
                'nama' => $matchedMaster?->nama ?? $rawUptName,
                'total_baris' => (int) $stat->total_baris,
                'selesai_count' => (int) $stat->selesai_count,
                'evaluator_id' => $evalId,
                'evaluator_name' => $evalUser?->name,
                'claim_status' => $claimStatus,
                'status_label' => $statusLabel,
                'status_badge_class' => $statusBadgeClass,
                'is_mine' => $isMine,
                'is_complete' => $isComplete,
            ];

            $uptStatusList->push($uptItem);

            if ($activeUptFilter && (
                (is_numeric($activeUptFilter) && $matchedMaster && (int) $matchedMaster->kode === (int) $activeUptFilter) ||
                (strtolower($rawUptName) === strtolower($activeUptFilter)) ||
                (isset($baseLocation) && $baseLocation && str_contains(strtolower($rawUptName), $baseLocation))
            )) {
                $currentUptInfo = $uptItem;
            }
        }

        // Stepper: jika data mentah sudah masuk, langkah aktif adalah Step 3 (Data Mentah)
        $activeStep = match ($dataset->status) {
            'Diunggah', 'Diproses' => 2,
            'Terstandardisasi' => 4,
            'Final' => 5,
            default => 3,
        };

        // Option list untuk dropdown filter
        $availableYears = Dataset::select('periode_tahun')->distinct()->orderBy('periode_tahun', 'desc')->pluck('periode_tahun')->toArray();
        if (! in_array($dataset->periode_tahun, $availableYears)) {
            $availableYears[] = $dataset->periode_tahun;
        }
        if (! in_array(now()->year, $availableYears)) {
            $availableYears[] = now()->year;
        }
        rsort($availableYears);

        return view('dataset.show', [
            'dataset' => $dataset,
            'rows' => $rows,
            'cari' => $request->query('cari'),
            'canEdit' => $canEdit,
            'evaluators' => $evaluators,
            'uptList' => $masterUptList,
            'uptStatusList' => $uptStatusList,
            'currentUptInfo' => $currentUptInfo,
            'filterUpt' => $request->query('upt'),
            'filterStatusTugas' => $statusTugas,
            'sembunyikanSelesai' => $hideCompleted,
            'filterEvaluatorId' => $evalFilterId,
            'progressStats' => $progressStats,
            'filterTahun' => $filterTahun,
            'filterKomoditi' => $filterKomoditi,
            'filterBulan' => $filterBulan,
            'availableYears' => $availableYears,
            'issues' => $issues,
            'errorCount' => $errorCount,
            'warningCount' => $warningCount,
            'manualTerisiCount' => $manualTerisiCount,
            'activeStep' => $activeStep,
            'tingkatAktif' => $request->query('tingkat'),
            'siblingSessions' => $siblingSessions,
            'isSpecificSession' => $isSpecificSession,
            'isAdminMerged' => false,
            'evaluatorWorkSummary' => $evaluatorWorkSummary,
            'hasCommodityMismatch' => $hasCommodityMismatch,
            'unstandardizedWarnings' => $dataset->getUnstandardizedWarnings($siblingIds),
        ]);
    }

    public function dataMentah(Dataset $dataset, Request $request): View
    {
        $dataset->loadMissing('uploader');
        session(['active_dataset_id' => $dataset->id]);
        $user = auth()->user();
        $canEdit = $dataset->canBeEditedBy($user);

        $query = $dataset->dataMentah()->with('evaluator');

        if ($request->filled('upt')) {
            $uptVal = $request->query('upt');
            $selectedUpt = is_numeric($uptVal)
                ? Upt::where('kode', (int) $uptVal)->first()
                : Upt::where('nama', $uptVal)->first();

            if ($selectedUpt) {
                $baseLocation = $this->datasetService->cleanUptName($selectedUpt->nama);
                $query->where(function ($q) use ($selectedUpt, $baseLocation) {
                    if ($baseLocation) {
                        $q->where('upt', 'like', '%' . $baseLocation . '%');
                    }
                    $q->orWhereHas('dataIklan', function ($di) use ($selectedUpt) {
                        $di->where('upt', $selectedUpt->kode);
                    });
                });
            }
        }

        if ($request->filled('cari')) {
            $keyword = '%'.trim($request->query('cari')).'%';
            $query->where(function ($q) use ($keyword) {
                $q->where('upt', 'like', $keyword)
                    ->orWhere('nama_produk', 'like', $keyword)
                    ->orWhere('nomor_izin_edar', 'like', $keyword)
                    ->orWhere('media_iklan', 'like', $keyword);
            });
        }

        $perPage = $request->integer('per_page', 500);
        if (! in_array($perPage, [50, 100, 500, 1000, 5000], true)) {
            $perPage = 500;
        }
        $rows = $query->orderBy('nomor_baris', 'asc')->orderBy('id', 'asc')->paginate($perPage)->withQueryString();
        $evaluators = User::where('role', 'evaluator')->where('is_active', true)->orderBy('name')->get();
        $uptList = Upt::orderBy('kode')->get(['kode', 'nama']);
        $progressStats = $dataset->progressStats();

        return view('dataset.data_mentah', [
            'dataset' => $dataset,
            'rows' => $rows,
            'cari' => $request->query('cari'),
            'canEdit' => $canEdit,
            'evaluators' => $evaluators,
            'uptList' => $uptList,
            'filterUpt' => $request->query('upt'),
            'filterEvaluatorId' => ($user && $user->isAdmin()) ? $request->query('evaluator_id') : null,
            'progressStats' => $progressStats,
            'unstandardizedWarnings' => $dataset->getUnstandardizedWarnings(),
        ]);
    }

    public function updateManualCell(
        Request $request,
        Dataset $dataset,
        DataMentah $item,
        UpdateManualEvaluationAction $action
    ): JsonResponse {
        return app(DatasetEvaluationController::class)->updateManualCell($request, $dataset, $item, $action);
    }

    public function bulkUpdateManual(
        Request $request,
        Dataset $dataset,
        UpdateManualEvaluationAction $action
    ): RedirectResponse|JsonResponse {
        return app(DatasetEvaluationController::class)->bulkUpdateManual($request, $dataset, $action);
    }

    /**
     * Menugaskan seluruh baris pada UPT tertentu ke evaluator yang dipilih (atau klaim mandiri Opsi A).
     */
    public function assignUpt(
        Request $request,
        Dataset $dataset,
        AssignEvaluatorAction $action
    ): RedirectResponse {
        return app(DatasetEvaluationController::class)->assignUpt($request, $dataset, $action);
    }

    /**
     * Sinkronkan penugasan baris ke pemilik dataset (Admin atau Pemilik Dataset).
     */
    public function redistributeEvaluator(
        Dataset $dataset,
        AssignEvaluatorAction $action
    ): RedirectResponse {
        return app(DatasetEvaluationController::class)->redistributeEvaluator($dataset, $action);
    }

    /**
     * Mengalihkan kepemilikan dataset beserta seluruh barisnya ke evaluator lain (khusus Admin).
     */
    public function reassignEvaluatorRows(
        Request $request,
        Dataset $dataset,
        AssignEvaluatorAction $action
    ): RedirectResponse {
        return app(DatasetEvaluationController::class)->reassignEvaluatorRows($request, $dataset, $action);
    }

    public function status(Dataset $dataset): JsonResponse
    {
        return response()->json([
            'id' => $dataset->id,
            'status' => $dataset->status,
            'jumlah_baris' => $dataset->jumlah_baris,
            'jumlah_menunggu_evaluasi' => $dataset->jumlah_menunggu_evaluasi,
            'errors_count' => $dataset->issues()->where('tingkat', 'error')->count(),
            'warnings_count' => $dataset->issues()->where('tingkat', 'warning')->count(),
        ]);
    }

    public function download(Dataset $dataset): StreamedResponse
    {
        $cleanPath = str_replace(['../', '..\\', 'private/'], '', (string) $dataset->path_file);
        $cleanPath = ltrim($cleanPath, '/\\');

        if (! Storage::disk('private')->exists($cleanPath)) {
            abort(404, 'File dokumen dataset tidak ditemukan di server.');
        }

        return Storage::disk('private')->download($cleanPath, $dataset->nama_file_asli);
    }

    public function destroy(Request $request, Dataset $dataset): RedirectResponse
    {
        $user = auth()->user();
        if (! $dataset->canBeManagedBy($user)) {
            return back()->with('error', 'Akses Ditolak: Anda hanya dapat menghapus dataset milik Anda sendiri.');
        }

        $namaFile = $dataset->nama_file_asli;
        $isFinal = $dataset->isFinal();

        if ($user && $user->isAdmin() && $request->boolean('hapus_grup')) {
            $siblings = $dataset->getSiblingSessions();
            $count = $siblings->count();
            foreach ($siblings as $sib) {
                $sib->delete();
            }
            $sesiInfo = $count > 1 ? " ({$count} sesi evaluator)" : ($isFinal ? ' (Status Final)' : '');

            DashboardService::bumpCacheVersion();

            return redirect()->route('dataset.index')
                ->with('status', "Dataset '{$namaFile}'{$sesiInfo} berhasil dipindahkan ke Kotak Sampah.");
        }

        $dataset->delete();

        $info = $isFinal ? ' (Status Final)' : '';

        DashboardService::bumpCacheVersion();

        return redirect()->route('dataset.index')
            ->with('status', "Dataset '{$namaFile}'{$info} berhasil dipindahkan ke Kotak Sampah.");
    }

    public function trash(Request $request): View
    {
        return app(DatasetTrashController::class)->trash($request);
    }

    public function restore(int $id): RedirectResponse
    {
        return app(DatasetTrashController::class)->restore($id);
    }

    public function restoreAll(): RedirectResponse
    {
        return app(DatasetTrashController::class)->restoreAll();
    }

    public function forceDelete(int $id): RedirectResponse
    {
        return app(DatasetTrashController::class)->forceDelete($id);
    }

    public function emptyTrash(): RedirectResponse
    {
        return app(DatasetTrashController::class)->emptyTrash();
    }

    /**
     * Ekspor Daftar Ringkasan Dataset ke berkas Excel (.xlsx).
     */
    public function exportList(Request $request): StreamedResponse
    {
        return app(DatasetExportController::class)->exportList($request);
    }

    /**
     * Ekspor Data Mentah SIPT (26 Kolom SIPT + Kolom Evaluasi Manual) ke Excel (.xlsx).
     */
    public function exportMentah(Dataset $dataset, Request $request): StreamedResponse
    {
        return app(DatasetExportController::class)->exportMentah($dataset, $request);
    }
}
