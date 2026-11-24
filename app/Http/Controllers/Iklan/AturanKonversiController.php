<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;

use App\Models\MasterKode;
use App\Models\PemetaanNilai;
use App\Models\Upt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AturanKonversiController extends Controller
{
    /**
     * Menampilkan daftar Aturan Konversi SIPT ke Kode Standar.
     */
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

        // Master Kode Dictionary by grup (digunakan sebagai backend referensi pilihan kode standar)
        $masterDict = MasterKode::all()->groupBy('grup')->map(function ($items) {
            return $items->pluck('nama', 'kode')->toArray();
        })->toArray();

        $uptDict = Upt::orderBy('kode')->pluck('nama', 'kode')->toArray();
        $masterDict['upt'] = $uptDict;

        $availableGrups = MasterKode::select('grup')->distinct()->orderBy('grup', 'asc')->pluck('grup')->toArray();
        if (! in_array('upt', $availableGrups)) {
            $availableGrups[] = 'upt';
            sort($availableGrups);
        }

        return view('aturan_konversi.index', [
            'rules' => $rules,
            'stats' => $stats,
            'masterDict' => $masterDict,
            'availableGrups' => $availableGrups,
            'cari' => $request->query('cari'),
            'filterGrup' => $request->query('grup'),
            'filterStatus' => $request->query('status'),
        ]);
    }

    /**
     * Menyimpan aturan konversi baru.
     */
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

        $redirectRoute = $request->routeIs('aturan-pemetaan.*') ? 'aturan-pemetaan.index' : 'aturan-konversi.index';

        return redirect()->route($redirectRoute)
            ->with('status', "Aturan konversi untuk '{$normalizedText}' berhasil disimpan.");
    }

    /**
     * Memperbarui aturan konversi yang sudah ada.
     */
    public function update(Request $request, PemetaanNilai $aturanKonversi): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $aturanKonversi->update([
            'kode' => (int) $validated['kode'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $redirectRoute = $request->routeIs('aturan-pemetaan.*') ? 'aturan-pemetaan.index' : 'aturan-konversi.index';

        return redirect()->route($redirectRoute)
            ->with('status', "Aturan konversi '{$aturanKonversi->nilai_sipt}' berhasil diperbarui.");
    }

    /**
     * Menghapus aturan konversi.
     */
    public function destroy(Request $request, PemetaanNilai $aturanKonversi): RedirectResponse
    {
        $nilai = $aturanKonversi->nilai_sipt;
        $aturanKonversi->delete();

        $redirectRoute = $request->routeIs('aturan-pemetaan.*') ? 'aturan-pemetaan.index' : 'aturan-konversi.index';

        return redirect()->route($redirectRoute)
            ->with('status', "Aturan konversi '{$nilai}' berhasil dihapus.");
    }
}
