<x-layouts.app title="Data Mentah SIPT (26 Kolom) - {{ $dataset->nama_file_asli }}">
    <x-slot:header>
        Data Mentah SIPT: {{ $dataset->nama_komoditi }} {{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }}
    </x-slot:header>
    <x-slot:breadcrumb>
        <a href="{{ route('dataset.index') }}" class="hover:underline">Dataset</a> / Detail Data Mentah
    </x-slot:breadcrumb>

    <!-- Stepper Progression: Data Mentah Langsung Aktif di Tahap 3 -->
    <x-stepper :activeStep="$activeStep" :datasetId="$dataset->id" />

    <!-- Live Polling Wrapper if Processing -->
    <div
        x-data="{
            status: '{{ $dataset->status }}',
            isPolling: {{ in_array($dataset->status, ['Diunggah', 'Diproses']) ? 'true' : 'false' }},
            pollInterval: null,
            init() {
                if (this.isPolling) {
                    this.pollInterval = setInterval(() => {
                        fetch('{{ route('dataset.status', $dataset) }}')
                            .then(res => res.json())
                            .then(data => {
                                if (data.status !== 'Diunggah' && data.status !== 'Diproses') {
                                    clearInterval(this.pollInterval);
                                    window.location.reload();
                                }
                            })
                            .catch(err => console.error(err));
                    }, 2000);
                }
            }
        }"
        class="space-y-5"
    >
        <!-- Notice if Processing -->
        <div x-show="isPolling" class="p-4 rounded-xl border border-blue-200 bg-blue-50 text-blue-900 flex items-center gap-3 shadow-xs">
            <svg class="animate-spin h-5 w-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="text-xs font-medium">
                Sistem sedang memproses pembacaan dan penyimpanan baris data file SIPT. Halaman akan diperbarui otomatis...
            </div>
        </div>

        @if (($filterTahun != $dataset->periode_tahun || $filterKomoditi != $dataset->komoditi || $filterBulan != $dataset->periode_bulan) && (request()->has('tahun') || request()->has('komoditi') || request()->has('bulan')))
            <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-xs leading-relaxed">
                    <strong>Pemberitahuan Filter:</strong> Belum ditemukan file dataset untuk komoditi
                    <strong>{{ match($filterKomoditi) { 'OT' => 'Obat Tradisional (OT)', 'OK' => 'Obat Kuasi (OK)', 'SK' => 'Suplemen Kesehatan (SK)', default => $filterKomoditi } }}</strong>
                    periode <strong>{{ \Carbon\Carbon::create(null, $filterBulan)->locale('id')->translatedFormat('F') }} {{ $filterTahun }}</strong>.
                    Menampilkan data dataset yang sedang aktif: <strong>{{ $dataset->nama_file_asli }}</strong>.
                    <a href="{{ route('dataset.index') }}" class="underline font-bold text-amber-800 ml-1">Unggah Dataset Baru &rarr;</a>
                </div>
            </div>
        @endif

        <!-- Summary & Action Header Card -->
        <x-card variant="soft">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <!-- Info Left -->
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <x-badge variant="primary">{{ $dataset->komoditi }}</x-badge>
                        <h3 class="text-xl font-bold text-gray-900">{{ $dataset->nama_file_asli }}</h3>
                        <x-badge variant="success" size="md">
                            Data Mentah Tersimpan ({{ number_format($dataset->jumlah_baris, 0, ',', '.') }} Baris)
                        </x-badge>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs text-gray-600 pt-1">
                        <div>
                            <span class="block text-gray-400 font-medium uppercase text-[10px]">Periode Target:</span>
                            <span class="font-bold text-gray-900 text-sm">{{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 font-medium uppercase text-[10px]">Rentang Tanggal SIPT:</span>
                            <span class="font-semibold text-gray-800">
                                {{ $dataset->periode_mulai ? $dataset->periode_mulai->format('d/m/Y') : '-' }} s/d {{ $dataset->periode_akhir ? $dataset->periode_akhir->format('d/m/Y') : '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-gray-400 font-medium uppercase text-[10px]">Total Baris SIPT:</span>
                            <span class="font-bold text-gray-900 text-sm">{{ number_format($dataset->jumlah_baris, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 font-medium uppercase text-[10px]">Menunggu Evaluasi:</span>
                            <span class="font-bold text-amber-700 text-sm">{{ number_format($dataset->jumlah_menunggu_evaluasi, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons Right -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <a
                        href="{{ route('dataset.standardisasi', $dataset) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold text-white shadow-sm hover:opacity-95 transition-opacity"
                        style="background-image: var(--grad-accent);"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span>Lanjut ke Standardisasi (18 Kolom Numerik) &rarr;</span>
                    </a>

                    @if ($dataset->canBeDeleted())
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'modal-hapus-dataset')"
                            class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-lg text-xs font-semibold text-red-600 bg-white border border-red-200 hover:bg-red-50 cursor-pointer shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus Dataset</span>
                        </button>
                    @endif
                </div>
            </div>
        </x-card>

        <!-- Filter Bar: Tahun, Komoditi, Bulan, dan Pencarian -->
        <div class="bg-white p-4 rounded-xl border border-(--border) shadow-xs">
            <form method="GET" action="{{ route('dataset.show', $dataset) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <!-- Filter Komoditi -->
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Komoditi
                    </label>
                    <select
                        name="komoditi"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    >
                        <option value="OT" @selected($filterKomoditi === 'OT')>Obat Tradisional (OT)</option>
                        <option value="OK" @selected($filterKomoditi === 'OK')>Obat Kuasi (OK)</option>
                        <option value="SK" @selected($filterKomoditi === 'SK')>Suplemen Kesehatan (SK)</option>
                    </select>
                </div>

                <!-- Filter Tahun -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Tahun
                    </label>
                    <select
                        name="tahun"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    >
                        @foreach ($availableYears as $thn)
                            <option value="{{ $thn }}" @selected((int)$filterTahun === (int)$thn)>{{ $thn }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Bulan
                    </label>
                    <select
                        name="bulan"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    >
                        @php
                            $namaBulanList = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ];
                        @endphp
                        @foreach ($namaBulanList as $blnNum => $blnLabel)
                            <option value="{{ $blnNum }}" @selected((int)$filterBulan === $blnNum)>{{ $blnLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Cari Kata Kunci -->
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Pencarian Kata Kunci
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="cari"
                            value="{{ $cari }}"
                            placeholder="Cari produk, NIE, UPT, media..."
                            class="w-full rounded-lg border border-gray-300 pl-8 pr-3 py-2 text-xs text-gray-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Tombol Submit & Reset -->
                <div class="lg:col-span-2 flex items-center gap-2">
                    <x-button type="submit" variant="primary" size="md" class="w-full justify-center">
                        Filter
                    </x-button>
                    @if ($cari || $filterTahun != $dataset->periode_tahun || $filterKomoditi != $dataset->komoditi || $filterBulan != $dataset->periode_bulan)
                        <a
                            href="{{ route('dataset.show', $dataset) }}"
                            class="px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-100 transition-colors shrink-0"
                            title="Reset filter ke dataset aktif"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Data Mentah SIPT (26 Kolom Asli Sesuai Tarikan SIPT) -->
        <div class="bg-white rounded-xl border border-(--border) shadow-xs overflow-hidden">
            <!-- Table Header Bar -->
            <div class="px-5 py-3.5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50/70">
                <div>
                    <h4 class="text-sm font-bold text-gray-900">
                        Data Mentah Hasil Penarikan SIPT (26 Kolom Asli)
                    </h4>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                        Data tersimpan langsung di database tanpa perubahan agar evaluasi dan rekapitulasi akurat.
                    </p>
                </div>
                <div class="text-xs text-gray-600 font-medium shrink-0">
                    Menampilkan <span class="font-bold text-gray-900">{{ number_format($rows->total(), 0, ',', '.') }}</span> total baris
                    @if ($cari)
                        (kata kunci: <span class="font-bold text-blue-700">"{{ $cari }}"</span>)
                    @endif
                </div>
            </div>

            <!-- Table Container with Horizontal and Vertical Scrolling -->
            <div class="overflow-x-auto max-h-[72vh]">
                <table class="min-w-full divide-y divide-gray-200 text-xs whitespace-nowrap">
                    <thead class="bg-slate-100 text-gray-700 font-bold uppercase tracking-wider sticky top-0 z-10 shadow-2xs">
                        <tr>
                            <th class="px-3 py-3 text-center border-r border-gray-200 bg-slate-200/90 w-12 sticky left-0 z-20">Baris</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 w-14">NO SIPT</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-48">UPT</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-32">TGL PENGAMBILAN</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-32">TGL KIRIM KA UPT</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-32">TGL EVALUASI PUSAT</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-36">JENIS PRODUK</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-52">NAMA PRODUK</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-40">NOMOR IZIN EDAR</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-56">KHASIAT / INDIKASI</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-48">PENDAFTAR</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-32">ASAL PRODUK</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-32">KATEGORI MEDIA</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-36">MEDIA IKLAN</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-52">TEMPAT PENGAMBILAN IKLAN</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-40">PENCANTUMAN NIE</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-56">LINK URL IKLAN</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-64">KLAIM PROMOSI</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-52">KLASIFIKASI KLAIM</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-36">PEMBUAT IKLAN</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-40">HASIL PENILAIAN UPT</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-44">KESIMPULAN UPT</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-44">KESIMPULAN PUSAT</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-44">KESESUAIAN HASIL EVALUASI</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-40">KATEGORI TMK</th>
                            <th class="px-3 py-3 text-left border-r border-gray-200 min-w-48">RINCIAN TMK</th>
                            <th class="px-3 py-3 text-left min-w-48">TINDAK LANJUT</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($rows as $row)
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                <td class="px-3 py-2 text-center font-bold text-gray-500 bg-slate-50 border-r border-gray-200 sticky left-0 z-10">
                                    {{ $row->nomor_baris }}
                                </td>
                                <td class="px-3 py-2 text-center text-gray-600 border-r border-gray-100">{{ $row->no }}</td>
                                <td class="px-3 py-2 font-medium text-gray-900 border-r border-gray-100">{{ $row->upt }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100 font-mono">{{ $row->tanggal_pengambilan_iklan }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100 font-mono">{{ $row->tgl_kirim_ka_upt_ke_pusat }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100 font-mono">{{ $row->tanggal_evaluasi_pusat }}</td>
                                <td class="px-3 py-2 font-medium text-gray-800 border-r border-gray-100">{{ $row->jenis_produk }}</td>
                                <td class="px-3 py-2 text-gray-900 font-medium border-r border-gray-100 truncate max-w-xs" title="{{ $row->nama_produk }}">{{ $row->nama_produk }}</td>
                                <td class="px-3 py-2 font-mono text-gray-800 border-r border-gray-100">{{ $row->nomor_izin_edar }}</td>
                                <td class="px-3 py-2 text-gray-600 border-r border-gray-100 truncate max-w-xs" title="{{ $row->khasiat_indikasi }}">{{ $row->khasiat_indikasi }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100 truncate max-w-xs" title="{{ $row->pendaftar }}">{{ $row->pendaftar }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->asal_produk }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->kategori_media }}</td>
                                <td class="px-3 py-2 font-medium text-gray-800 border-r border-gray-100">{{ $row->media_iklan }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100 truncate max-w-xs" title="{{ $row->tempat_pengambilan_iklan }}">{{ $row->tempat_pengambilan_iklan }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->pencantuman_nomor_izin_edar }}</td>
                                <td class="px-3 py-2 text-blue-600 border-r border-gray-100 truncate max-w-xs font-mono">
                                    @if ($row->link_url_iklan && str_starts_with($row->link_url_iklan, 'http'))
                                        <a href="{{ $row->link_url_iklan }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                            {{ $row->link_url_iklan }}
                                        </a>
                                    @else
                                        {{ $row->link_url_iklan ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-600 border-r border-gray-100 truncate max-w-xs" title="{{ $row->klaim_dalam_promosi_iklan }}">{{ $row->klaim_dalam_promosi_iklan }}</td>
                                <td class="px-3 py-2 text-gray-800 border-r border-gray-100 truncate max-w-xs" title="{{ $row->klasifikasi_klaim }}">{{ $row->klasifikasi_klaim }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->pembuat_iklan }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->hasil_penilaian_iklan_upt }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100 truncate max-w-xs" title="{{ $row->kesimpulan_upt }}">{{ $row->kesimpulan_upt }}</td>
                                <td class="px-3 py-2 font-medium text-gray-900 border-r border-gray-100">
                                    @if (empty($row->kesimpulan_pusat) || $row->kesimpulan_pusat === '-')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">
                                            Menunggu Evaluasi
                                        </span>
                                    @else
                                        {{ $row->kesimpulan_pusat }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->kesesuaian_hasil_evaluasi }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->kategori_tmk }}</td>
                                <td class="px-3 py-2 text-gray-700 border-r border-gray-100 truncate max-w-xs" title="{{ $row->rincian_tmk }}">{{ $row->rincian_tmk }}</td>
                                <td class="px-3 py-2 text-gray-700 truncate max-w-xs" title="{{ $row->tindak_lanjut }}">{{ $row->tindak_lanjut }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="27" class="px-4 py-12 text-center text-gray-400">
                                    @if ($cari)
                                        Tidak ditemukan baris yang cocok dengan kata kunci pencarian "{{ $cari }}".
                                    @else
                                        Belum ada data mentah yang tersimpan pada dataset ini.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rows->hasPages())
                <div class="p-4 border-t border-gray-200">
                    {{ $rows->links() }}
                </div>
            @endif
        </div>

        <!-- Catatan Pemeriksaan Otomatis (Non-Blocking Notes) -->
        @if ($issues->isNotEmpty())
            <details class="group bg-white rounded-xl border border-gray-200 shadow-2xs overflow-hidden">
                <summary class="p-4 cursor-pointer flex items-center justify-between text-xs font-semibold text-gray-700 bg-gray-50/70 hover:bg-gray-100/70 transition-colors">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Catatan Pemeriksaan Otomatis Sistem ({{ $issues->count() }} Anomali Non-Blocking)</span>
                        <span class="text-gray-400 font-normal hidden sm:inline">- Tidak memblokir data, seluruh baris telah tersimpan.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($errorCount > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">{{ $errorCount }} Error</span>
                        @endif
                        @if ($warningCount > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">{{ $warningCount }} Warning</span>
                        @endif
                        <svg class="w-4 h-4 text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </summary>

                <div class="overflow-x-auto max-h-64 border-t border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 text-[10px] font-bold text-gray-500 uppercase tracking-wider sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 text-center w-20">Baris</th>
                                <th class="px-4 py-2.5 text-left w-44">Kolom</th>
                                <th class="px-4 py-2.5 text-center w-24">Tingkat</th>
                                <th class="px-4 py-2.5 text-left">Deskripsi Masalah / Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($issues as $issue)
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-4 py-2 text-center font-bold text-gray-700">
                                        {{ $issue->nomor_baris ?? 'Umum' }}
                                    </td>
                                    <td class="px-4 py-2 font-mono text-[11px] text-gray-800">
                                        {{ $issue->kolom ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        @if ($issue->tingkat === 'error')
                                            <span class="inline-flex px-1.5 py-0.5 text-[10px] font-bold rounded bg-red-100 text-red-700">ERROR</span>
                                        @else
                                            <span class="inline-flex px-1.5 py-0.5 text-[10px] font-bold rounded bg-amber-100 text-amber-700">WARNING</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-700 text-xs">
                                        {{ $issue->pesan }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    </div>

    <!-- Modal Konfirmasi Hapus Dataset -->
    <x-modal name="modal-hapus-dataset" title="Konfirmasi Penghapusan Dataset" maxWidth="md">
        <div class="space-y-4">
            <div class="p-3 bg-red-50 text-red-800 rounded-xl border border-red-200 text-xs leading-relaxed">
                <strong>Peringatan:</strong> Seluruh data mentah untuk file <strong>{{ $dataset->nama_file_asli }}</strong> akan dihapus permanen dari server. Tindakan ini tidak dapat dibatalkan.
            </div>
            <p class="text-sm text-gray-600">
                Apakah Anda yakin ingin melanjutkan penghapusan?
            </p>
        </div>

        <x-slot:footer>
            <x-button type="button" variant="secondary" size="sm" @click="$dispatch('close-modal', 'modal-hapus-dataset')">
                Batal
            </x-button>
            <form method="POST" action="{{ route('dataset.destroy', $dataset) }}">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger" size="sm">
                    Ya, Hapus Permanen
                </x-button>
            </form>
        </x-slot:footer>
    </x-modal>
</x-layouts.app>
