<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;

use App\Models\MasterKode;
use App\Models\PemetaanNilai;
use App\Services\Iklan\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AturanPemetaanController extends Controller
{
    public function index(Request $request): View
    {
        $query = PemetaanNilai::query();

        if ($request->filled('cari')) {
            $keyword = '%'.trim($request->query('cari')).'%';
            $query->where(function ($q) use ($keyword) {
                $q->where('nilai_sipt', 'like', $keyword)
                    ->orWhere('kolom_sumber', 'like', $keyword);
            });
        }

        if ($request->filled('grup')) {
            $query->where('grup', $request->query('grup'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'aktif');
        }

        $rules = $query->orderBy('grup', 'asc')
            ->orderBy('nilai_sipt', 'asc')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total_aturan' => PemetaanNilai::count(),
            'total_aktif' => PemetaanNilai::where('is_active', true)->count(),
            'total_grup' => PemetaanNilai::distinct('grup')->count('grup'),
        ];

        // Master Kode Dictionary by grup
        $masterDict = MasterKode::all()->groupBy('grup')->map(function ($items) {
            return $items->pluck('nama', 'kode')->toArray();
        })->toArray();

        $uptDict = \App\Models\Upt::orderBy('kode')->pluck('nama', 'kode')->toArray();
        $masterDict['upt'] = $uptDict;

        $availableGrups = MasterKode::select('grup')->distinct()->orderBy('grup', 'asc')->pluck('grup')->toArray();
        if (! in_array('upt', $availableGrups)) {
            $availableGrups[] = 'upt';
            sort($availableGrups);
        }

        return view('aturan_pemetaan.index', [
            'rules' => $rules,
            'stats' => $stats,
            'masterDict' => $masterDict,
            'availableGrups' => $availableGrups,
            'cari' => $request->query('cari'),
            'filterGrup' => $request->query('grup'),
            'filterStatus' => $request->query('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'grup' => ['required', 'string'],
            'kolom_sumber' => ['required', 'string'],
            'nilai_sipt' => ['required', 'string'],
            'kode' => ['required', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $normalizedText = strtoupper(trim(preg_replace('/\s+/', ' ', $validated['nilai_sipt'])));

        PemetaanNilai::updateOrCreate(
            [
                'grup' => $validated['grup'],
                'nilai_sipt' => $normalizedText,
            ],
            [
                'kolom_sumber' => strtoupper(trim($validated['kolom_sumber'])),
                'kode' => (int) $validated['kode'],
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        DashboardService::bumpCacheVersion();

        return redirect()->route('aturan-pemetaan.index')
            ->with('status', "Aturan pemetaan untuk '{$normalizedText}' berhasil disimpan.");
    }

    public function update(Request $request, PemetaanNilai $aturanPemetaan): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $aturanPemetaan->update([
            'kode' => (int) $validated['kode'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        DashboardService::bumpCacheVersion();

        return redirect()->route('aturan-pemetaan.index')
            ->with('status', "Aturan pemetaan '{$aturanPemetaan->nilai_sipt}' berhasil diperbarui.");
    }

    public function destroy(Request $request, PemetaanNilai $aturanPemetaan): RedirectResponse
    {
        $nilai = $aturanPemetaan->nilai_sipt;
        $aturanPemetaan->delete();

        DashboardService::bumpCacheVersion();

        return redirect()->route('aturan-pemetaan.index')
            ->with('status', "Aturan pemetaan '{$nilai}' berhasil dihapus.");
    }
}
