<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;

use App\Models\Dataset;
use App\Services\Iklan\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): View
    {
        $latestFinalYear = \App\Models\DataIklan::where('is_final', true)->max('periode_tahun')
            ?? Dataset::where('status', 'Final')->max('periode_tahun')
            ?? Dataset::max('periode_tahun');
        $tahun = (int) $request->input('tahun', $latestFinalYear ?: 2026);
        $komoditi = strtoupper($request->input('komoditi', 'SEMUA'));
        if (! in_array($komoditi, ['SEMUA', 'OT', 'OK', 'SK', 'OBA'])) {
            $komoditi = 'SEMUA';
        }
        $uptKode = $request->filled('upt') ? (int) $request->input('upt') : null;

        $metrics = $this->dashboardService->getMetrics($tahun, $komoditi, $uptKode);

        return view('dashboard', [
            'metrics' => $metrics,
            'tahun' => $tahun,
            'komoditi' => $komoditi,
            'uptKode' => $uptKode,
        ]);
    }

    /**
     * Endpoint ringan untuk mendeteksi perubahan data terbaru (Live Polling Otomatis).
     * Semua laman (Dashboard, Standarisasi, Data Tersimpan, Rekap) menggunakan versi ini.
     */
    public function syncVersion(): \Illuminate\Http\JsonResponse
    {
        $lastDatasetUpdate = Dataset::withTrashed()->max('updated_at') ?? '';
        $lastIklanUpdate = \App\Models\DataIklan::max('updated_at') ?? '';
        $lastMentahUpdate = \App\Models\DataMentah::max('updated_at') ?? '';
        $lastDatasetCount = Dataset::count();
        $lastIklanCount = \App\Models\DataIklan::where('is_final', true)->count();
        $lastMentahCount = \App\Models\DataMentah::count();
        $cacheVersion = DashboardService::getCacheVersion();

        $versionHash = md5("{$lastDatasetUpdate}_{$lastIklanUpdate}_{$lastMentahUpdate}_{$lastDatasetCount}_{$lastIklanCount}_{$lastMentahCount}_{$cacheVersion}");

        return response()->json([
            'version' => $versionHash,
            'versi' => $versionHash,
            'cache_version' => $cacheVersion,
            'dataset_count' => $lastDatasetCount,
            'datasets_count' => $lastDatasetCount,
            'iklan_count' => $lastIklanCount,
            'updated_at' => $lastIklanUpdate ?: $lastDatasetUpdate,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
