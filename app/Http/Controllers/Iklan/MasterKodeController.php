<?php

namespace App\Http\Controllers\Iklan;

use App\Http\Controllers\Controller;

use App\Models\DataIklan;
use App\Models\MasterKode;
use App\Models\PemetaanNilai;
use App\Models\Upt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterKodeController extends Controller
{
    /**
     * Menampilkan daftar Kamus Kode Standar & Master Data UPT.
     */
    public function index(Request $request): View
    {
        $daftarGrup = MasterKode::select('grup')
            ->distinct()
            ->orderBy('grup', 'asc')
            ->pluck('grup')
            ->toArray();

        // Tambahkan 'upt' sebagai grup master yang fleksibel (tidak terpaku jumlah 83)
        if (! in_array('upt', $daftarGrup)) {
            $daftarGrup[] = 'upt';
            sort($daftarGrup);
        }

        $grupAktif = $request->query('grup', 'jenis_produk');
        if (! in_array($grupAktif, $daftarGrup) && ! empty($daftarGrup)) {
            $grupAktif = $daftarGrup[0];
        }

        $cari = trim((string) $request->query('cari', ''));

        if ($grupAktif === 'upt') {
            $uptQuery = Upt::query();
            if ($cari !== '') {
                $uptQuery->where(function ($q) use ($cari) {
                    $q->where('nama', 'like', "%{$cari}%")
                        ->orWhere('kode', 'like', "%{$cari}%");
                });
            }
            $items = $uptQuery->orderBy('kode', 'asc')->paginate(30)->withQueryString();
            $nextKode = ((int) Upt::max('kode')) + 1;
        } else {
            $kodeQuery = MasterKode::where('grup', $grupAktif);
            if ($cari !== '') {
                $kodeQuery->where(function ($q) use ($cari) {
                    $q->where('nama', 'like', "%{$cari}%")
                        ->orWhere('kode', 'like', "%{$cari}%")
                        ->orWhere('kelompok_rekap', 'like', "%{$cari}%");
                });
            }
            $items = $kodeQuery->orderBy('kode', 'asc')->paginate(30)->withQueryString();
            $nextKode = ((int) MasterKode::where('grup', $grupAktif)->max('kode')) + 1;
        }

        $stats = [
            'total_kode' => MasterKode::count(),
            'total_grup' => MasterKode::distinct('grup')->count('grup'),
            'total_upt' => Upt::count(),
        ];

        return view('master_kode.index', [
            'daftarGrup' => $daftarGrup,
            'grupAktif' => $grupAktif,
            'items' => $items,
            'cari' => $cari,
            'stats' => $stats,
            'nextKode' => $nextKode,
        ]);
    }

    /**
     * Menyimpan kode standar atau UPT baru ke Master Data.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'grup' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'kode' => ['required', 'integer', 'min:1', 'max:65535'],
            'nama' => ['required', 'string', 'max:1000'],
            'kelompok_rekap' => ['nullable', 'string', 'max:50'],
        ], [
            'grup.regex' => 'Nama grup hanya boleh menggunakan huruf kecil, angka, dan underscore (_).',
        ]);

        $grup = strtolower(trim($validated['grup']));
        $kode = (int) $validated['kode'];
        $nama = trim($validated['nama']);

        if ($grup === 'upt') {
            $existingUpt = Upt::where('kode', $kode)->first();
            if ($existingUpt) {
                return back()->withErrors([
                    'kode' => "Kode UPT #{$kode} sudah digunakan oleh '{$existingUpt->nama}'.",
                ])->withInput();
            }

            Upt::create([
                'kode' => $kode,
                'nama' => $nama,
            ]);

            return redirect()->route('kamus-kode.index', ['grup' => 'upt'])
                ->with('status', "UPT baru #{$kode} ('{$nama}') berhasil ditambahkan ke Master Data UPT.");
        }

        $existing = MasterKode::where('grup', $grup)->where('kode', $kode)->first();
        if ($existing) {
            return back()->withErrors([
                'kode' => "Kode #{$kode} sudah digunakan pada grup '{$grup}' ({$existing->nama}).",
            ])->withInput();
        }

        MasterKode::create([
            'grup' => $grup,
            'kode' => $kode,
            'nama' => $nama,
            'kelompok_rekap' => $validated['kelompok_rekap'] ? trim($validated['kelompok_rekap']) : null,
        ]);

        return redirect()->route('kamus-kode.index', ['grup' => $grup])
            ->with('status', "Kode standar #{$kode} ('{$nama}') berhasil ditambahkan ke grup '{$grup}'.");
    }

    /**
     * Memperbarui data kode standar pada Master Data.
     */
    public function update(Request $request, MasterKode $masterKode): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:1000'],
            'kelompok_rekap' => ['nullable', 'string', 'max:50'],
        ]);

        $masterKode->update([
            'nama' => trim($validated['nama']),
            'kelompok_rekap' => $validated['kelompok_rekap'] ? trim($validated['kelompok_rekap']) : null,
        ]);

        return redirect()->route('kamus-kode.index', ['grup' => $masterKode->grup])
            ->with('status', "Kode standar #{$masterKode->kode} berhasil diperbarui.");
    }

    /**
     * Memperbarui data UPT pada Master Data UPT.
     */
    public function updateUpt(Request $request, Upt $upt): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'nama' => ['required', 'string', 'max:1000'],
        ]);

        $newKode = isset($validated['kode']) ? (int) $validated['kode'] : $upt->kode;

        if ($newKode !== (int) $upt->kode) {
            $conflict = Upt::where('kode', $newKode)->where('id', '!=', $upt->id)->first();
            if ($conflict) {
                return back()->withErrors([
                    'error' => "Kode UPT #{$newKode} sudah digunakan oleh '{$conflict->nama}'.",
                ]);
            }

            // Sinkronisasi kode jika sudah dipakai di pemetaan_nilai atau data_iklan
            PemetaanNilai::where('grup', 'upt')->where('kode', $upt->kode)->update(['kode' => $newKode]);
            DataIklan::where('upt', $upt->kode)->update(['upt' => $newKode]);
        }

        $upt->update([
            'kode' => $newKode,
            'nama' => trim($validated['nama']),
        ]);

        return redirect()->route('kamus-kode.index', ['grup' => 'upt'])
            ->with('status', "Data UPT #{$upt->kode} ('{$upt->nama}') berhasil diperbarui.");
    }

    /**
     * Menghapus kode standar dari Master Data.
     */
    public function destroy(MasterKode $masterKode): RedirectResponse
    {
        $grup = $masterKode->grup;
        $kode = $masterKode->kode;
        $nama = $masterKode->nama;

        // Cek integritas referensi: apakah kode ini digunakan di pemetaan_nilai
        $pemetaanCount = PemetaanNilai::where('grup', $grup)->where('kode', $kode)->count();
        if ($pemetaanCount > 0) {
            return back()->withErrors([
                'error' => "Kode #{$kode} ('{$nama}') tidak dapat dihapus karena masih digunakan oleh {$pemetaanCount} aturan konversi SIPT aktif.",
            ]);
        }

        $masterKode->delete();

        return redirect()->route('kamus-kode.index', ['grup' => $grup])
            ->with('status', "Kode standar #{$kode} ('{$nama}') berhasil dihapus dari Master Data.");
    }

    /**
     * Menghapus UPT dari Master Data UPT.
     */
    public function destroyUpt(Upt $upt): RedirectResponse
    {
        $kode = $upt->kode;
        $nama = $upt->nama;

        $pemetaanCount = PemetaanNilai::where('grup', 'upt')->where('kode', $kode)->count();
        if ($pemetaanCount > 0) {
            return back()->withErrors([
                'error' => "UPT #{$kode} ('{$nama}') tidak dapat dihapus karena masih digunakan oleh {$pemetaanCount} aturan konversi SIPT.",
            ]);
        }

        $dataIklanCount = DataIklan::where('upt', $kode)->count();
        if ($dataIklanCount > 0) {
            return back()->withErrors([
                'error' => "UPT #{$kode} ('{$nama}') tidak dapat dihapus karena sudah tercatat pada {$dataIklanCount} baris data iklan.",
            ]);
        }

        $upt->delete();

        return redirect()->route('kamus-kode.index', ['grup' => 'upt'])
            ->with('status', "UPT #{$kode} ('{$nama}') berhasil dihapus dari Master Data UPT.");
    }
}
