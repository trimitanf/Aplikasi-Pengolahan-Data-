<x-layouts.app title="Data Terstandarisasi - {{ $dataset->nama_file_asli }}">
    <x-slot:header>
        Data Terstandarisasi: {{ $dataset->nama_komoditi }} ({{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }})
    </x-slot:header>
    <x-slot:breadcrumb>
        <a href="{{ route('standarisasi.index') }}" class="hover:underline">Data Terstandarisasi</a> /
        <span>{{ $dataset->nama_komoditi }} ({{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }})</span>
    </x-slot:breadcrumb>

    <div
        x-data="{
            isFinal: {{ $dataset->isFinal() ? 'true' : 'false' }},
            activeCellSaving: null,
            toastMessage: null,
            toastType: 'error',
            showToast(msg, type = 'error') {
                this.toastType = type;
                this.toastMessage = msg;
                setTimeout(() => { this.toastMessage = null; }, 5000);
            },
            saveProgressStandarisasi() {
                this.showToast('Seluruh hasil standarisasi dan koreksi kode berhasil disimpan!', 'success');
                setTimeout(() => { window.location.reload(); }, 700);
            },
            saveStdCell(itemId, kolom, val) {
                if (!val) return;
                this.activeCellSaving = `${itemId}_${kolom}`;
                fetch(`/dataset/{{ $dataset->id }}/manual-cell/${itemId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ kolom: kolom, nilai: val })
                })
                .then(async res => {
                    if (!res.ok) {
                        const errData = await res.json().catch(() => ({}));
                        throw new Error(errData.error || errData.message || 'Gagal menyimpan koreksi sel.');
                    }
                    return res.json();
                })
                .then(() => {
                    this.activeCellSaving = null;
                    this.showToast('Kode berhasil disimpan! Memperbarui tabel...', 'success');
                    setTimeout(() => { window.location.reload(); }, 350);
                })
                .catch(err => {
                    this.activeCellSaving = null;
                    this.showToast(err.message || 'Terjadi kesalahan saat menyimpan kode.');
                });
            }
        }"
        class="space-y-5"
    >
        <!-- Floating Toast Notification -->
        <div
            x-show="toastMessage"
            x-cloak
            x-transition
            :class="toastType === 'success' ? 'bg-emerald-600' : 'bg-rose-600'"
            class="fixed bottom-6 right-6 z-50 max-w-md text-white px-4 py-3 rounded-xl shadow-xl flex items-center justify-between gap-3 text-xs font-semibold"
        >
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = null" class="text-white/80 hover:text-white p-1 cursor-pointer">✕</button>
        </div>

        <!-- Brief Notification: Unstandardized Master Codes (misal: UPT) -->
        <x-unstandardized-code-alert :warnings="$unstandardizedWarnings ?? []" />

        <!-- 1. Top Summary Cards (Gaya Bawaan Laravel Sederhana) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M8 4v16" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Baris</span>
                    <span class="text-xl font-bold text-gray-900">{{ number_format($totalBaris, 0, ',', '.') }} Baris</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Terstandar Lengkap</span>
                    <span class="text-xl font-bold text-emerald-700">{{ number_format($totalBaris, 0, ',', '.') }} Baris</span>
                </div>
            </div>
        </div>

        <!-- 2. Main Table Card (Kotak Putih Sederhana & Bersih) -->
        <div id="tabel-standardisasi" class="bg-white rounded-xl border border-gray-200 border-t-2 border-t-emerald-600 shadow-xs overflow-hidden">
            <!-- Card Header: Title + Dataset Switcher (Left) & Action Buttons (Right) -->
            <div class="px-5 py-3.5 border-b border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h3 class="text-sm font-bold text-gray-900">
                        Tabel Data Terstandarisasi
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                        {{ $dataset->nama_file_asli }}
                    </span>
                </div>

                <!-- Right Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('dataset.show', $dataset) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold transition-colors"
                    >
                        <span>&larr; Data Mentah</span>
                    </a>

                    @if (! $canEdit)
                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold border border-slate-200">
                            Mode Lihat Saja
                        </span>
                    @endif

                    <a
                        href="{{ route('dataset.export', array_merge(['dataset' => $dataset->id], array_filter(request()->only(['upt', 'evaluator_id', 'cari'])))) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors shadow-2xs cursor-pointer"
                        title="Ekspor data sesuai filter yang dipilih atau ekspor keseluruhan jika tanpa filter"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor Excel (.xlsx)</span>
                    </a>

                    <a
                        href="{{ route('rekap.index', ['tahun' => $dataset->periode_tahun]) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors cursor-pointer shadow-2xs"
                    >
                        <span>Rekap &rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Controls Row: Show Entries + Filters (Left) & Search (Right) -->
            <div class="px-5 py-3 bg-gray-50/60 border-b border-gray-200">
                <form method="GET" action="{{ route('dataset.standardisasi', $dataset) }}" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <!-- Left: Show Entries & Filters -->
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-700">
                        <div class="inline-flex items-center gap-1.5">
                            <span>Show</span>
                            <select
                                name="per_page"
                                onchange="this.form.submit()"
                                class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-900 focus:border-blue-600 focus:outline-none cursor-pointer"
                            >
                                @foreach ([50, 100, 500, 1000] as $size)
                                    <option value="{{ $size }}" @selected((int) request('per_page', 500) === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                            <span>entries</span>
                        </div>

                        <span class="text-gray-300 hidden sm:inline">|</span>

                        <select
                            name="upt"
                            class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-900 font-medium bg-white cursor-pointer max-w-52"
                            onchange="this.form.submit()"
                        >
                            <option value="">Semua UPT</option>
                            @foreach ($uptList as $upt)
                                <option value="{{ $upt->kode }}" @selected(($filterUpt ?? '') == $upt->kode)>
                                    [{{ $upt->kode }}] {{ $upt->nama }}
                                </option>
                            @endforeach
                        </select>

                        @if (auth()->user()?->isAdmin())
                            <select
                                name="evaluator_id"
                                class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs text-gray-900 font-medium bg-white cursor-pointer max-w-44"
                                onchange="this.form.submit()"
                            >
                                <option value="">Semua Evaluator</option>
                                @foreach ($evaluators as $ev)
                                    <option value="{{ $ev->id }}" @selected(($filterEvaluatorId ?? '') == $ev->id)>
                                        {{ $ev->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- Right: Search Input & Submit -->
                    <div class="flex items-center gap-2">
                        <label for="search-std" class="text-xs text-gray-600 font-medium whitespace-nowrap">Search:</label>
                        <input
                            id="search-std"
                            type="text"
                            name="cari"
                            value="{{ $cari }}"
                            placeholder="Produk, NIE, UPT..."
                            class="w-full sm:w-52 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900 focus:border-blue-600 focus:outline-none"
                        />
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 cursor-pointer">
                            Cari
                        </button>
                        @if ($cari || !empty($filterUpt) || !empty($filterEvaluatorId))
                            <a href="{{ route('dataset.standardisasi', $dataset) }}" class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-xs text-gray-600 hover:bg-gray-100">Reset</a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Standard Clean Table: 18 Kolom Standar BPOM (+ Evaluator & Waktu) -->
            <div class="overflow-x-auto overflow-y-auto max-h-[60vh]">
                <table class="min-w-full divide-y divide-gray-200 text-xs text-center whitespace-nowrap">
                    <thead class="bg-slate-50 text-gray-700 font-bold uppercase tracking-wider text-[11px] sticky top-0 z-10 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-3 border-r border-gray-200 bg-slate-100 sticky left-0 z-20 w-12">NO</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-14">UPT</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">PERIODE BULAN</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">JENIS PRODUK</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">ASAL PRODUK</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">KLAIM</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">KATEGORI MEDIA</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">KATEGORI IKLAN</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">NAMA MEDIA</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">PENCANTUMAN NIE</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">KESESUAIAN NIE</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">HASIL EVALUASI</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">RINCIAN TMK</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">JENIS TMK</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">PEMBUAT IKLAN</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">KESESUAIAN EVALUASI</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">KUALITAS LAPORAN</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-16">KETEPATAN WAKTU</th>
                            <th class="px-3 py-3 border-r border-gray-200 w-28">EVALUATOR</th>
                            <th class="px-3 py-3 min-w-44 text-center">WAKTU</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white font-medium">
                        @forelse ($items as $item)
                            @php
                                $displayNo = $dataset->isFinal() ? $item->no : ($item->dataMentah?->nomor_baris ?? $item->no);
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- 1. NO -->
                                <td class="px-3 py-2 font-bold text-gray-800 bg-slate-50 border-r border-gray-200 sticky left-0 z-10" title="Baris SIPT: {{ $item->dataMentah?->nomor_baris ?? $item->no }}">
                                    {{ $items->firstItem() + $loop->index }}
                                </td>

                                <!-- 2. UPT -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['upt'][$item->upt] ?? ($item->dataMentah->upt ?? '') }}">
                                    {{ $item->upt ?? '-' }}
                                </td>

                                <!-- 3. PERIODE BULAN -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['bulan'][$item->periode_bulan] ?? '' }}">
                                    {{ $item->periode_bulan ?? '-' }}
                                </td>

                                <!-- 4. JENIS PRODUK -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['jenis_produk'][$item->jenis_produk] ?? '' }}">
                                    {{ $item->jenis_produk ?? '-' }}
                                </td>

                                <!-- 5. ASAL PRODUK -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['asal_produk'][$item->asal_produk] ?? '' }}">
                                    {{ $item->asal_produk ?? '-' }}
                                </td>

                                <!-- 6. KLAIM -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['klaim'][$item->klaim] ?? '' }}">
                                    {{ $item->klaim ?? '-' }}
                                </td>

                                <!-- 7. KATEGORI MEDIA -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['kategori_media'][$item->kategori_media] ?? '' }}">
                                    {{ $item->kategori_media ?? '-' }}
                                </td>

                                <!-- 8. KATEGORI IKLAN -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['kategori_iklan'][$item->kategori_iklan] ?? '' }}">
                                    {{ $item->kategori_iklan ?? '-' }}
                                </td>

                                <!-- 9. NAMA MEDIA -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['nama_media'][$item->nama_media] ?? '' }}">
                                    {{ $item->nama_media ?? '-' }}
                                </td>

                                <!-- 10. PENCANTUMAN NIE -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['pencantuman_nie'][$item->pencantuman_nie] ?? '' }}">
                                    {{ $item->pencantuman_nie ?? '-' }}
                                </td>

                                <!-- 11. KESESUAIAN NIE -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['kesesuaian_nie'][$item->kesesuaian_nie] ?? '' }}">
                                    {{ $item->kesesuaian_nie ?? '-' }}
                                </td>

                                <!-- 12. HASIL EVALUASI -->
                                <td class="px-3 py-2 border-r border-gray-100 font-bold text-gray-800" title="{{ $masterDict['hasil_evaluasi'][$item->hasil_evaluasi] ?? '' }}">
                                    @if ($item->hasil_evaluasi === 1)
                                        <span class="text-emerald-700">1</span>
                                    @elseif ($item->hasil_evaluasi === 2)
                                        <span class="text-red-700">2</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                <!-- 13. RINCIAN TMK -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['rincian_tmk'][$item->rincian_tmk] ?? '' }}">
                                    {{ $item->rincian_tmk ?? '-' }}
                                </td>

                                <!-- 14. JENIS TMK -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['jenis_tmk'][$item->jenis_tmk] ?? '' }}">
                                    {{ $item->jenis_tmk ?? '-' }}
                                </td>

                                <!-- 15. PEMBUAT IKLAN -->
                                <td class="px-3 py-2 border-r border-gray-100 text-gray-800" title="{{ $masterDict['pembuat_iklan'][$item->pembuat_iklan] ?? '' }}">
                                    {{ $item->pembuat_iklan ?? '-' }}
                                </td>

                                <!-- 16. KESESUAIAN EVALUASI -->
                                <td class="px-3 py-2 border-r border-gray-100 font-bold text-gray-800" title="{{ $masterDict['kesesuaian_evaluasi'][$item->kesesuaian_evaluasi] ?? '' }}">
                                    {{ $item->kesesuaian_evaluasi ?? '-' }}
                                </td>

                                <!-- 17. KUALITAS LAPORAN -->
                                <td class="px-3 py-2 border-r border-gray-100 font-bold text-gray-800" title="{{ $masterDict['kualitas_laporan'][$item->kualitas_laporan] ?? '' }}">
                                    {{ $item->kualitas_laporan ?? '-' }}
                                </td>

                                <!-- 18. KETEPATAN WAKTU -->
                                <td class="px-3 py-2 border-r border-gray-100 font-bold text-gray-800" title="{{ $masterDict['ketepatan_waktu'][$item->ketepatan_waktu] ?? '' }}">
                                    {{ $item->ketepatan_waktu ?? '-' }}
                                </td>

                                <!-- 19. EVALUATOR -->
                                <td class="px-3 py-2 text-gray-800 text-[11px] font-semibold border-r border-gray-100">
                                    {{ $item->evaluator?->name ?? ($item->dataMentah?->evaluator?->name ?? ($dataset->uploader?->name ?? '-')) }}
                                </td>

                                @php
                                    $waktuProses = $item->updated_at ?? $item->created_at ?? $dataset->updated_at ?? ($item->evaluator?->last_login_at ?? auth()->user()?->last_login_at ?? now());
                                @endphp
                                <!-- 20. WAKTU -->
                                <td class="px-3 py-2 text-center text-xs text-gray-700 font-medium" data-row-waktu="{{ $item->id }}">
                                    <span class="font-bold text-gray-900 block">{{ $waktuProses->translatedFormat('d F Y') }}</span>
                                    <span class="block text-[10px] text-gray-500 mt-0.5">{{ $waktuProses->format('H:i:s') }} WIB</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="px-4 py-12 text-center text-gray-400">
                                    Tidak ada data yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Table Footer: Showing X to Y of Z entries & Pagination -->
            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-gray-600">
                <div>
                    Showing <strong>{{ $items->firstItem() ?? 0 }}</strong> to <strong>{{ $items->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($items->total(), 0, ',', '.') }}</strong> entries
                </div>
                @if ($items->hasPages())
                    <div>
                        {{ $items->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
