<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\Iklan\DashboardService;
use App\Services\Iklan\DatasetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DatasetTrashController extends Controller
{
    public function __construct(
        protected DatasetService $datasetService
    ) {}

    public function trash(Request $request): View
    {
        $user = auth()->user();
        $isEvaluator = $user && ! $user->isAdmin();

        $query = Dataset::onlyTrashed()->with('uploader');
        if ($isEvaluator) {
            $query->where(function ($q) use ($user) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereNull('uploaded_by');
            });
        }

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

        $datasets = $query->orderBy('deleted_at', 'desc')->paginate(15)->withQueryString();

        $trashBase = Dataset::onlyTrashed();
        $activeBase = Dataset::query();
        if ($isEvaluator) {
            $trashBase->where(function ($q) use ($user) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereNull('uploaded_by');
            });
            $activeBase->where(function ($q) use ($user) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereNull('uploaded_by');
            });
        }

        $stats = [
            'total_sampah' => (clone $trashBase)->count(),
            'total_baris' => (int) (clone $trashBase)->sum('jumlah_baris'),
            'total_final' => (clone $trashBase)->where('status', 'Final')->count(),
            'total_aktif' => (clone $activeBase)->count(),
        ];

        return view('dataset.trash', [
            'datasets' => $datasets,
            'stats' => $stats,
            'filters' => [
                'komoditi' => $request->query('komoditi'),
                'tahun' => $request->query('tahun'),
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $dataset = Dataset::onlyTrashed()->findOrFail($id);
        if (! $dataset->canBeManagedBy(auth()->user())) {
            return back()->with('error', 'Akses Ditolak: Anda hanya dapat memulihkan dataset milik Anda sendiri.');
        }
        $dataset->restore();

        DashboardService::bumpCacheVersion();

        return redirect()->back()
            ->with('status', "Dataset '{$dataset->nama_file_asli}' berhasil dipulihkan dari Kotak Sampah.");
    }

    public function restoreAll(): RedirectResponse
    {
        $query = Dataset::onlyTrashed();
        if (! auth()->user()?->isAdmin()) {
            $query->where(function ($q) {
                $q->where('uploaded_by', auth()->id())
                    ->orWhereNull('uploaded_by');
            });
        }
        $trashed = $query->get();
        $count = $trashed->count();

        foreach ($trashed as $dataset) {
            $dataset->restore();
        }

        DashboardService::bumpCacheVersion();

        return redirect()->route('dataset.trash')
            ->with('status', "Berkas sampah ({$count} dataset) berhasil dipulihkan.");
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $dataset = Dataset::onlyTrashed()->findOrFail($id);
        if (! $dataset->canBeManagedBy(auth()->user())) {
            return back()->with('error', 'Akses Ditolak: Anda hanya dapat menghapus permanen dataset milik Anda sendiri.');
        }
        $namaFile = $dataset->nama_file_asli;

        $this->datasetService->forceDelete($dataset);

        DashboardService::bumpCacheVersion();

        return redirect()->back()
            ->with('status', "Dataset '{$namaFile}' telah dihapus permanen dari sistem.");
    }

    public function emptyTrash(): RedirectResponse
    {
        $query = Dataset::onlyTrashed();
        if (! auth()->user()?->isAdmin()) {
            $query->where(function ($q) {
                $q->where('uploaded_by', auth()->id())
                    ->orWhereNull('uploaded_by');
            });
        }
        $trashed = $query->get();
        $count = $trashed->count();

        foreach ($trashed as $dataset) {
            $this->datasetService->forceDelete($dataset);
        }

        DashboardService::bumpCacheVersion();

        return redirect()->route('dataset.trash')
            ->with('status', "Kotak Sampah berhasil dikosongkan ({$count} dataset dihapus permanen).");
    }
}
