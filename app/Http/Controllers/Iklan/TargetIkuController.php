<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTargetIkuRequest;
use App\Models\TargetIku;
use App\Services\Iklan\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TargetIkuController extends Controller
{
    /**
     * Menampilkan daftar dan statistik Target IKU.
     */
    public function index(Request $request): View
    {
        $tahunAktif = (int) date('Y');

        $query = TargetIku::query();

        // Filter berdasarkan kategori periode (sebelum, berjalan, akan_datang)
        if ($request->filled('periode') && $request->input('periode') !== 'semua') {
            $periode = $request->input('periode');
            if ($periode === 'sebelum') {
                $query->where('tahun', '<', $tahunAktif);
            } elseif ($periode === 'berjalan') {
                $query->where('tahun', '=', $tahunAktif);
            } elseif ($periode === 'akan_datang') {
                $query->where('tahun', '>', $tahunAktif);
            }
        }

        // Filter berdasarkan tahun spesifik
        if ($request->filled('tahun') && is_numeric($request->input('tahun'))) {
            $query->where('tahun', (int) $request->input('tahun'));
        }

        // Filter berdasarkan kelompok komoditi (OBA / SK)
        if ($request->filled('kelompok') && in_array($request->input('kelompok'), ['OBA', 'SK'])) {
            $query->where('kelompok', $request->input('kelompok'));
        }

        $targetIkuList = $query->orderBy('tahun', 'desc')
            ->orderBy('kelompok', 'asc')
            ->get();

        $targetObaTahunIni = TargetIku::where('tahun', $tahunAktif)->where('kelompok', 'OBA')->first();
        $targetSkTahunIni = TargetIku::where('tahun', $tahunAktif)->where('kelompok', 'SK')->first();

        // Kumpulan tahun untuk dropdown filter: 5 tahun lalu s/d 5 tahun mendatang + tahun yang tercatat di DB
        $existingYears = TargetIku::distinct()->pluck('tahun')->toArray();
        $defaultYears = range($tahunAktif - 5, $tahunAktif + 5);
        $availableYears = array_values(array_unique(array_merge($defaultYears, $existingYears)));
        rsort($availableYears);

        $stats = [
            'tahun_berjalan' => $tahunAktif,
            'target_oba' => $targetObaTahunIni?->target ?? 0,
            'target_sk' => $targetSkTahunIni?->target ?? 0,
            'total_target' => TargetIku::count(),
            'filtered_count' => $targetIkuList->count(),
        ];

        return view('target_iku.index', [
            'targetIkuList' => $targetIkuList,
            'stats' => $stats,
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Menyimpan atau menetapkan target IKU baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'kelompok' => ['required', 'in:OBA,SK'],
            'target' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'target.min' => 'Target IKU tidak boleh kurang dari 0%.',
            'target.max' => 'Target IKU tidak boleh lebih dari 100%.',
        ]);

        $tahun = (int) $validated['tahun'];
        $kelompok = $validated['kelompok'];
        $target = (float) $validated['target'];

        TargetIku::updateOrCreate(
            [
                'tahun' => $tahun,
                'kelompok' => $kelompok,
            ],
            [
                'target' => $target,
            ]
        );

        DashboardService::bumpCacheVersion();

        $redirectRoute = $request->routeIs('pengaturan.*')
            ? route('pengaturan.index', ['tab' => 'target_iku'])
            : route('target-iku.index');

        return redirect($redirectRoute)
            ->with('status', "Target IKU {$kelompok} Tahun {$tahun} berhasil disimpan ({$target}%).");
    }

    /**
     * Memperbarui nilai target IKU.
     */
    public function update(Request $request, TargetIku $targetIku): RedirectResponse
    {
        $validated = $request->validate([
            'target' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'target.min' => 'Target IKU tidak boleh kurang dari 0%.',
            'target.max' => 'Target IKU tidak boleh lebih dari 100%.',
        ]);

        $targetIku->update([
            'target' => (float) $validated['target'],
        ]);

        DashboardService::bumpCacheVersion();

        return redirect()->route('target-iku.index')
            ->with('status', "Target IKU {$targetIku->kelompok} Tahun {$targetIku->tahun} berhasil diperbarui menjadi {$targetIku->target}%.");
    }

    /**
     * Menghapus penetapan target IKU.
     */
    public function destroy(TargetIku $targetIku): RedirectResponse
    {
        $tahun = $targetIku->tahun;
        $kelompok = $targetIku->kelompok;
        $targetIku->delete();

        DashboardService::bumpCacheVersion();

        return redirect()->route('target-iku.index')
            ->with('status', "Target IKU {$kelompok} Tahun {$tahun} berhasil dihapus.");
    }
}
