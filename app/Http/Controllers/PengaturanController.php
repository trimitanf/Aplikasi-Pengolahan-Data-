<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTargetIkuRequest;
use App\Models\MasterKode;
use App\Models\PemetaanNilai;
use App\Models\TargetIku;
use App\Models\Upt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function index(Request $request): View
    {
        $targetIkuList = TargetIku::orderBy('tahun', 'desc')
            ->orderBy('kelompok', 'asc')
            ->get();

        $grupAktif = $request->query('grup', 'jenis_produk');
        $tabAktif = $request->query('tab', 'target_iku');

        $daftarGrup = MasterKode::select('grup')->distinct()->pluck('grup');

        $masterKodes = MasterKode::where('grup', $grupAktif)
            ->orderBy('kode', 'asc')
            ->get();

        $uptList = Upt::orderBy('kode', 'asc')->get();

        $pemetaanList = PemetaanNilai::orderBy('grup', 'asc')
            ->orderBy('kolom_sumber', 'asc')
            ->get();

        return view('pengaturan.index', [
            'targetIkuList' => $targetIkuList,
            'tabAktif' => $tabAktif,
            'grupAktif' => $grupAktif,
            'daftarGrup' => $daftarGrup,
            'masterKodes' => $masterKodes,
            'uptList' => $uptList,
            'pemetaanList' => $pemetaanList,
        ]);
    }

    public function updateTargetIku(UpdateTargetIkuRequest $request): RedirectResponse
    {
        TargetIku::updateOrCreate(
            [
                'tahun' => $request->integer('tahun'),
                'kelompok' => $request->input('kelompok'),
            ],
            [
                'target' => $request->float('target'),
            ]
        );

        return redirect()->route('pengaturan.index', ['tab' => 'target_iku'])
            ->with('status', "Target IKU {$request->input('kelompok')} {$request->integer('tahun')} berhasil diperbarui menjadi {$request->input('target')}%.");
    }
}
