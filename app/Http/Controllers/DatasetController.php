<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDatasetRequest;
use App\Jobs\ProsesDatasetJob;
use App\Models\Dataset;
use App\Services\DatasetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DatasetController extends Controller
{
    public function __construct(
        protected DatasetService $datasetService
    ) {}

    public function index(Request $request): View
    {
        $query = Dataset::query();

        if ($request->filled('komoditi')) {
            $query->where('komoditi', $request->query('komoditi'));
        }

        if ($request->filled('tahun')) {
            $query->where('periode_tahun', $request->integer('tahun'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $datasets = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('dataset.index', [
            'datasets' => $datasets,
            'filters' => [
                'komoditi' => $request->query('komoditi'),
                'tahun' => $request->query('tahun'),
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function store(UploadDatasetRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $namaFileAsli = $file->getClientOriginalName();

        // Simpan file ke disk private
        $path = $file->store('datasets', 'private');

        $dataset = Dataset::create([
            'nama_file_asli' => $namaFileAsli,
            'path_file' => 'private/' . $path,
            'komoditi' => $request->input('komoditi'),
            'periode_tahun' => $request->integer('periode_tahun'),
            'periode_bulan' => $request->integer('periode_bulan'),
            'status' => 'Diunggah',
            'uploaded_at' => now(),
        ]);

        // Jalankan pemrosesan via Job antrean
        // Jika queue sync/database, dispatch job
        ProsesDatasetJob::dispatch($dataset);

        return redirect()->route('dataset.show', $dataset)
            ->with('status', "File '{$namaFileAsli}' berhasil diunggah dan sedang diproses oleh sistem.");
    }

    public function show(Dataset $dataset, Request $request): View
    {
        $issueQuery = $dataset->issues();

        if ($request->filled('tingkat')) {
            $issueQuery->where('tingkat', $request->query('tingkat'));
        }

        $issues = $issueQuery->orderBy('nomor_baris', 'asc')->paginate(50)->withQueryString();

        $errorCount = $dataset->issues()->where('tingkat', 'error')->count();
        $warningCount = $dataset->issues()->where('tingkat', 'warning')->count();

        // Tentukan stepper state (1: Unggah, 2: Validasi, 3: Data Mentah, 4: Konversi, 5: Final, 6: Rekap)
        $activeStep = match ($dataset->status) {
            'Diunggah', 'Diproses' => 2,
            'Ditolak', 'Gagal' => 2,
            'Valid' => 3,
            'Terstandardisasi' => 4,
            'Final' => 5,
            default => 2,
        };

        return view('dataset.show', [
            'dataset' => $dataset,
            'issues' => $issues,
            'errorCount' => $errorCount,
            'warningCount' => $warningCount,
            'activeStep' => $activeStep,
            'tingkatAktif' => $request->query('tingkat'),
        ]);
    }

    public function dataMentah(Dataset $dataset, Request $request): View
    {
        $query = $dataset->dataMentah();

        if ($request->filled('cari')) {
            $keyword = '%' . trim($request->query('cari')) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('upt', 'like', $keyword)
                    ->orWhere('nama_produk', 'like', $keyword)
                    ->orWhere('nomor_izin_edar', 'like', $keyword)
                    ->orWhere('media_iklan', 'like', $keyword);
            });
        }

        $rows = $query->orderBy('nomor_baris', 'asc')->paginate(50)->withQueryString();

        return view('dataset.data_mentah', [
            'dataset' => $dataset,
            'rows' => $rows,
            'cari' => $request->query('cari'),
        ]);
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

    public function destroy(Dataset $dataset): RedirectResponse
    {
        if (! $dataset->canBeDeleted()) {
            return redirect()->route('dataset.index')
                ->with('error', 'Dataset dengan status Final tidak dapat dihapus.');
        }

        // Hapus file fisik jika ada
        if (Storage::disk('private')->exists(str_replace('private/', '', $dataset->path_file))) {
            Storage::disk('private')->delete(str_replace('private/', '', $dataset->path_file));
        }

        $namaFile = $dataset->nama_file_asli;
        $dataset->delete();

        return redirect()->route('dataset.index')
            ->with('status', "Dataset '{$namaFile}' berhasil dihapus.");
    }
}
