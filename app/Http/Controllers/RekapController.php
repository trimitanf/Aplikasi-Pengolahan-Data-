<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Services\ExportRekapService;
use App\Services\RekapService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapController extends Controller
{
    public function __construct(
        protected RekapService $rekapService,
        protected ExportRekapService $exportService
    ) {}

    public function index(Request $request): View
    {
        // Tahun default: tahun dari dataset final terakhir, atau 2026
        $latestYear = Dataset::where('status', 'Final')->max('periode_tahun') ?? 2026;
        $tahun = $request->integer('tahun', $latestYear);

        $bulanAwal = $request->integer('bulan_awal', 1);

        $latestMonth = Dataset::where('periode_tahun', $tahun)
            ->where('status', 'Final')
            ->max('periode_bulan');
        $defaultBulanAkhir = $latestMonth ? (int)$latestMonth : 12;

        $bulanAkhir = $request->has('bulan_akhir')
            ? $request->integer('bulan_akhir')
            : $defaultBulanAkhir;

        $rekap = $this->rekapService->calculate($tahun, $bulanAwal, $bulanAkhir);

        return view('rekap.index', [
            'rekap' => $rekap,
            'tahun' => $tahun,
            'bulanAwal' => $bulanAwal,
            'bulanAkhir' => $bulanAkhir,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $latestYear = Dataset::where('status', 'Final')->max('periode_tahun') ?? 2026;
        $tahun = $request->integer('tahun', $latestYear);
        $bulanAwal = $request->integer('bulan_awal', 1);

        $latestMonth = Dataset::where('periode_tahun', $tahun)
            ->where('status', 'Final')
            ->max('periode_bulan');
        $defaultBulanAkhir = $latestMonth ? (int)$latestMonth : 12;

        $bulanAkhir = $request->has('bulan_akhir')
            ? $request->integer('bulan_akhir')
            : $defaultBulanAkhir;

        $rekap = $this->rekapService->calculate($tahun, $bulanAwal, $bulanAkhir);

        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $namaBulanAkhir = $bulanMap[$bulanAkhir] ?? (string)$bulanAkhir;

        $filename = "Rekap_Iklan_{$tahun}_s.d._{$namaBulanAkhir}.xlsx";

        return $this->exportService->download($rekap, $filename);
    }
}
