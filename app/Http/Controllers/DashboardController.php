<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): View
    {
        $latestFinalYear = Dataset::where('status', 'Final')->max('periode_tahun');
        $tahun = (int) $request->input('tahun', $latestFinalYear ?: 2026);
        $komoditi = strtoupper($request->input('komoditi', 'SEMUA'));
        if (! in_array($komoditi, ['SEMUA', 'OBA', 'SK'])) {
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
}
