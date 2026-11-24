@php
    $displayKomoditi = match($filterKomoditi ?? $dataset->komoditi) {
        'OBA', 'OT' => 'Obat Bahan Alam (OBA)',
        'OK' => 'Obat Kuasi (OK)',
        'SK' => 'Suplemen Kesehatan (SK)',
        default => $dataset->nama_komoditi,
    };
@endphp
<x-layouts.app title="Data Mentah SIPT (26 Kolom) - {{ !empty($hasCommodityMismatch) ? '-' : $dataset->nama_file_asli }}">
    <x-slot:header>
        Data Mentah SIPT: {{ $displayKomoditi }} ({{ $dataset->nama_bulan }} {{ $filterTahun ?? $dataset->periode_tahun }})
    </x-slot:header>
    <x-slot:breadcrumb>
        <a href="{{ route('dataset.index') }}" class="hover:underline">Dataset</a> / Data Mentah SIPT
    </x-slot:breadcrumb>

    <div
        x-data="datasetShowApp({
            status: '{{ $dataset->status }}',
            isPolling: {{ in_array($dataset->status, ['Diunggah', 'Diproses']) ? 'true' : 'false' }},
            isFinal: {{ $dataset->isFinal() ? 'true' : 'false' }},
            datasetId: {{ $dataset->id }},
            csrfToken: '{{ csrf_token() }}',
            statusUrl: '{{ route('dataset.status', $dataset) }}',
            bulkUrl: '{{ route('dataset.mentah-bulk-manual', $dataset) }}',
            visibleRowIds: [{{ $rows->pluck('id')->implode(',') }}]
        })"
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

        <!-- Notice if Processing -->
        <div x-show="isPolling" class="p-4 rounded-xl border border-blue-200 bg-blue-50 text-blue-900 flex items-center gap-3 shadow-xs">
            <svg class="animate-spin h-5 w-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="text-xs font-medium">
                Sistem sedang memproses pembacaan dan penyimpanan baris data file SIPT...
            </div>
        </div>

        <!-- Brief Notification: Unstandardized Master Codes (misal: UPT) -->
        <x-unstandardized-code-alert :warnings="$unstandardizedWarnings ?? []" />

        @if ($dataset->status === 'Gagal')
            <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-900 flex items-start gap-3 shadow-xs" role="alert">
                <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs space-y-1">
                    <p class="font-bold text-red-950">
                        Pemrosesan Dataset Gagal
                    </p>
                    <p class="text-red-800 leading-relaxed">
                        {{ $dataset->issues()->where('tingkat', 'error')->latest()->value('pesan') ?? $dataset->catatan ?? 'Terjadi kesalahan sistem saat memproses antrean berkas dataset ini.' }}
                    </p>
                </div>
            </div>
        @endif

        <!-- 1. Top Summary Cards (Gaya Bawaan Laravel Sederhana) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">File Dataset SIPT</span>
                    <span class="text-sm font-bold text-gray-900 truncate block" title="{{ !empty($hasCommodityMismatch) ? '-' : $dataset->nama_file_asli }}">{{ !empty($hasCommodityMismatch) ? '-' : $dataset->nama_file_asli }}</span>
                    <span class="text-[11px] text-gray-500">{{ $displayKomoditi }} &bull; {{ $dataset->nama_bulan }} {{ $filterTahun ?? $dataset->periode_tahun }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M8 4v16" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Data Mentah</span>
                    <span class="text-xl font-bold text-gray-900">{{ !empty($hasCommodityMismatch) ? 0 : number_format($dataset->jumlah_baris, 0, ',', '.') }} Baris</span>
                    <span class="text-[11px] text-emerald-700 font-medium ml-1">(26 Kolom SIPT)</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Baris Tertampil</span>
                    <span class="text-xl font-bold text-indigo-700">{{ number_format($rows->total(), 0, ',', '.') }} Baris</span>
                    <span class="text-[11px] text-gray-500 font-medium ml-1">
                        {{ ($cari || !empty($filterUpt) || !empty($filterEvaluatorId)) ? '(Hasil Filter)' : '(Semua Data)' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Main Table Card (Kotak Putih Sederhana & Bersih) -->
        <div class="bg-white rounded-xl border border-gray-200 border-t-2 border-t-emerald-600 shadow-xs overflow-hidden">
            <!-- Card Header: Title Left & Action Buttons Right -->
            <div class="px-5 py-3.5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-gray-900">
                        Tabel Data Mentah SIPT
                    </h3>
                    <span class="sr-only">Data Mentah Hasil Penarikan SIPT (26 Kolom Asli) Isi Massal Manual</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">
                        {{ number_format($rows->total(), 0, ',', '.') }} Baris
                    </span>
                    @if (! $canEdit)
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-300">
                            Mode Lihat Saja
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Ekspor Excel SIPT Mentah (26 Kolom + Evaluasi) -->
                    <a
                        href="{{ route('dataset.export-mentah', array_merge(['dataset' => $dataset->id], request()->query())) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 text-xs font-bold hover:bg-emerald-100 transition-colors shadow-2xs"
                        title="Ekspor seluruh data mentah SIPT 26 kolom beserta status evaluasi ke file Excel (.xlsx)"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor Excel (.xlsx)</span>
                    </a>

                    @if (!empty($hasCommodityMismatch))
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-semibold cursor-not-allowed">
                            Data Terstandarisasi &rarr;
                        </span>
                    @else
                        <a
                            href="{{ route('dataset.standardisasi', $dataset) }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors shadow-2xs"
                        >
                            <span>Data Terstandarisasi &rarr;</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Tab Navigasi Status Pengerjaan (Menjawab kebutuhan Baris Hilang & Kolaborasi) -->
            <div class="px-5 pt-3 pb-0 bg-white border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-1 -mb-px">
                    <a
                        href="{{ route('dataset.show', array_merge(request()->except(['page', 'status_tugas']), ['dataset' => $dataset->id, 'status_tugas' => 'semua'])) }}"
                        class="px-3.5 py-2 text-xs font-bold border-b-2 transition-colors {{ ($filterStatusTugas ?? 'semua') === 'semua' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800' }}"
                    >
                        Semua Data
                    </a>
                    <a
                        href="{{ route('dataset.show', array_merge(request()->except(['page', 'status_tugas']), ['dataset' => $dataset->id, 'status_tugas' => 'tugas_saya'])) }}"
                        class="px-3.5 py-2 text-xs font-bold border-b-2 transition-colors {{ ($filterStatusTugas ?? '') === 'tugas_saya' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800' }}"
                    >
                        Tugas Saya
                    </a>
                    <a
                        href="{{ route('dataset.show', array_merge(request()->except(['page', 'status_tugas']), ['dataset' => $dataset->id, 'status_tugas' => 'belum_diambil'])) }}"
                        class="px-3.5 py-2 text-xs font-bold border-b-2 transition-colors {{ ($filterStatusTugas ?? '') === 'belum_diambil' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800' }}"
                    >
                        Data Tersedia
                    </a>
                    <a
                        href="{{ route('dataset.show', array_merge(request()->except(['page', 'status_tugas']), ['dataset' => $dataset->id, 'status_tugas' => 'selesai'])) }}"
                        class="px-3.5 py-2 text-xs font-bold border-b-2 transition-colors {{ ($filterStatusTugas ?? '') === 'selesai' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800' }}"
                    >
                        Riwayat Selesai
                    </a>
                </div>

                <!-- Toggle Sembunyikan Baris Selesai ("Baris Hilang Setelah Dikerjakan") -->
                <div class="pb-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200 shadow-2xs">
                        <input
                            type="checkbox"
                            name="sembunyikan_selesai"
                            value="1"
                            @checked(!empty($sembunyikanSelesai))
                            onchange="window.location.href = '{{ route('dataset.show', array_merge(request()->except(['page', 'sembunyikan_selesai']), ['dataset' => $dataset->id, 'sembunyikan_selesai' => empty($sembunyikanSelesai) ? 1 : 0])) }}'"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                        />
                        <span>Sembunyikan baris yang sudah selesai dievaluasi</span>
                    </label>
                </div>
            </div>

            <!-- Controls Row: Show Entries + Filters (Left) & Search (Right) -->
            <div class="px-5 py-3 bg-gray-50/60 border-b border-gray-200">
                <form method="GET" action="{{ route('dataset.show', $dataset) }}" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    @if (request()->filled('status_tugas'))
                        <input type="hidden" name="status_tugas" value="{{ request('status_tugas') }}">
                    @endif
                    @if (request()->filled('sembunyikan_selesai'))
                        <input type="hidden" name="sembunyikan_selesai" value="{{ request('sembunyikan_selesai') }}">
                    @endif

                    <!-- Left: Show Entries & Dropdown Filters -->
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
                            name="komoditi"
                            onchange="this.form.submit()"
                            class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-900 bg-white cursor-pointer"
                        >
                            <option value="OBA" @selected(in_array($filterKomoditi, ['OBA', 'OT']))>Obat Bahan Alam (OBA)</option>
                            <option value="OK" @selected($filterKomoditi === 'OK')>Obat Kuasi (OK)</option>
                            <option value="SK" @selected($filterKomoditi === 'SK')>Suplemen Kesehatan (SK)</option>
                        </select>

                        <select
                            name="upt"
                            onchange="this.form.submit()"
                            class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-900 bg-white max-w-64 cursor-pointer"
                        >
                            <option value="">Semua UPT</option>
                            @if (!empty($uptStatusList) && $uptStatusList->isNotEmpty())
                                @foreach ($uptStatusList as $item)
                                    <option value="{{ $item['kode'] ?? $item['raw_name'] }}" @selected(($filterUpt ?? '') == ($item['kode'] ?? $item['raw_name']))>
                                        [{{ $item['status_label'] }}] {{ $item['nama'] }} ({{ $item['total_baris'] }} baris)
                                    </option>
                                @endforeach
                            @else
                                @foreach ($uptList as $upt)
                                    <option value="{{ $upt->kode }}" @selected(($filterUpt ?? '') == $upt->kode)>
                                        [{{ $upt->kode }}] {{ $upt->nama }}
                                    </option>
                                @endforeach
                            @endif
                        </select>

                        @if (auth()->user()?->isAdmin())
                        <select
                            name="evaluator_id"
                            onchange="this.form.submit()"
                            class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-900 bg-white max-w-48 cursor-pointer"
                        >
                            <option value="">Semua Evaluator</option>
                            @foreach ($evaluators as $ev)
                                <option value="{{ $ev->id }}" @selected(($filterEvaluatorId ?? '') == $ev->id)>
                                    {{ $ev->name }}
                                </option>
                            @endforeach
                        </select>
                        @endif

                        <button
                            type="button"
                            @click="wrapText = !wrapText"
                            :class="wrapText ? 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold' : 'bg-white text-gray-600 border-gray-300 font-medium'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border text-xs cursor-pointer transition-colors shadow-2xs"
                            title="Beralih antara mode Bungkus Teks (Wrap Text) dan Ringkas (Truncate)"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10m-10 6h16" />
                            </svg>
                            <span x-text="wrapText ? 'Wrap Text: Aktif' : 'Wrap Text: Nonaktif'">Wrap Text: Aktif</span>
                        </button>
                    </div>

                    <!-- Right: Search Input & Submit -->
                    <div class="flex items-center gap-2">
                        <label for="search-mentah" class="text-xs text-gray-600 font-medium whitespace-nowrap">Search:</label>
                        <input
                            id="search-mentah"
                            type="text"
                            name="cari"
                            value="{{ $cari }}"
                            placeholder="Produk, NIE, UPT..."
                            class="w-full sm:w-52 rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 bg-white focus:border-blue-600 focus:outline-none"
                        />
                        <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold cursor-pointer">
                            Filter
                        </button>
                        @if ($cari || !empty($filterUpt) || !empty($filterEvaluatorId) || !empty($filterStatusTugas && $filterStatusTugas !== 'semua') || !empty($sembunyikanSelesai) || $filterTahun != $dataset->periode_tahun || $filterKomoditi != $dataset->komoditi || $filterBulan != $dataset->periode_bulan)
                            <a href="{{ route('dataset.show', $dataset) }}" class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-xs text-gray-600 hover:bg-gray-100">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if (!empty($filterEvaluatorId) && !empty($evaluatorWorkSummary) && $evaluatorWorkSummary->has((int) $filterEvaluatorId))
                @php $sel = $evaluatorWorkSummary->get((int) $filterEvaluatorId); @endphp
                <div class="px-5 py-2.5 bg-emerald-50 border-b border-emerald-200/80 flex items-center justify-between text-xs text-emerald-900">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white font-bold text-[10px]">✓</span>
                        <span>
                            Menampilkan baris tugas <strong>{{ $sel['nama'] }}</strong> ({{ $sel['jumlah'] }} baris) pada UPT:
                            <strong>{{ $sel['upt']->implode(', ') ?: '-' }}</strong>
                        </span>
                    </div>
                    <a
                        href="{{ route('dataset.show', array_filter(['dataset' => $dataset->id, 'upt' => $filterUpt ?? null, 'cari' => $cari ?? null])) }}"
                        class="text-xs font-bold text-emerald-700 hover:text-emerald-900 underline underline-offset-2"
                    >
                        Tampilkan Semua Baris &rarr;
                    </a>
                </div>
            @endif

            <!-- UPT Action Banner (Opsi A: Klaim Mandiri & Penguncian) -->
            @if (!empty($filterUpt) && !$dataset->isFinal())
                @php
                    $uptInfo = $currentUptInfo ?? null;
                    $uptNameDisplay = $uptInfo['nama'] ?? $filterUpt;
                    $claimState = $uptInfo['claim_status'] ?? 'available';
                    $evalName = $uptInfo['evaluator_name'] ?? 'Evaluator lain';
                @endphp
                <div class="mx-5 my-3 p-3.5 rounded-xl border flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs {{ $claimState === 'mine_in_progress' ? 'bg-blue-50 border-blue-200 text-blue-900' : ($claimState === 'mine_done' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : ($claimState === 'available' ? 'bg-slate-50 border-slate-200 text-slate-800' : 'bg-amber-50 border-amber-200 text-amber-900')) }}">
                    <div class="flex items-center gap-2 font-semibold">
                        @if ($claimState === 'available')
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-700 font-bold">⚪</span>
                            <span>UPT: <strong class="text-gray-950 font-bold">{{ $uptNameDisplay }}</strong> &bull; Status: <span class="text-slate-600 font-bold">Tersedia (Belum ada yang mengambil)</span></span>
                        @elseif ($claimState === 'mine_in_progress')
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white font-bold">⏳</span>
                            <span>UPT: <strong class="text-blue-950 font-bold">{{ $uptNameDisplay }}</strong> &bull; <span class="font-bold text-blue-700">Sedang Anda Kerjakan</span> ({{ $uptInfo['selesai_count'] ?? 0 }} / {{ $uptInfo['total_baris'] ?? 0 }} baris selesai)</span>
                        @elseif ($claimState === 'mine_done')
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white font-bold">✓</span>
                            <span>UPT: <strong class="text-emerald-950 font-bold">{{ $uptNameDisplay }}</strong> &bull; <span class="font-bold text-emerald-700">✓ Selesai Anda Evaluasi Seluruhnya</span></span>
                        @else
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-500 text-white font-bold">🔒</span>
                            <span>UPT: <strong class="text-amber-950 font-bold">{{ $uptNameDisplay }}</strong> &bull; <span class="font-bold text-amber-800">Sedang/Sudah Dievaluasi oleh: {{ $evalName }} (Terkunci / Mode Lihat Saja)</span></span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        @if ($claimState === 'available')
                            <form method="POST" action="{{ route('dataset.assign-upt', $dataset) }}">
                                @csrf
                                <input type="hidden" name="upt" value="{{ $filterUpt }}">
                                <input type="hidden" name="action" value="claim">
                                <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-colors shadow-2xs cursor-pointer whitespace-nowrap inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    <span>Ambil & Klaim UPT Ini</span>
                                </button>
                            </form>
                        @elseif ($claimState === 'mine_in_progress')
                            <form method="POST" action="{{ route('dataset.assign-upt', $dataset) }}" onsubmit="return confirm('Apakah Anda yakin ingin melepas klaim UPT ini? UPT akan dapat diambil oleh evaluator lain.')">
                                @csrf
                                <input type="hidden" name="upt" value="{{ $filterUpt }}">
                                <input type="hidden" name="action" value="release">
                                <button type="submit" class="px-3.5 py-1.5 bg-white border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg text-xs font-semibold transition-colors cursor-pointer whitespace-nowrap">
                                    Lepas Klaim UPT
                                </button>
                            </form>
                        @endif

                        @if (auth()->check() && auth()->user()->isAdmin())
                            <!-- Admin Reassign Control -->
                            <form method="POST" action="{{ route('dataset.assign-upt', $dataset) }}" class="flex items-center gap-1.5 pl-3 border-l border-gray-300">
                                @csrf
                                <input type="hidden" name="upt" value="{{ $filterUpt }}">
                                <span class="text-[11px] text-gray-500 font-medium">Admin:</span>
                                <select name="evaluator_id" class="rounded border border-gray-300 bg-white px-2 py-1 text-xs">
                                    @foreach ($evaluators as $ev)
                                        <option value="{{ $ev->id }}" @selected(($uptInfo['evaluator_id'] ?? null) == $ev->id)>{{ $ev->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-2.5 py-1 bg-gray-800 text-white rounded text-xs font-semibold hover:bg-gray-900 cursor-pointer">
                                    Tugaskan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Standard Clean Table (26 SIPT Columns + Evaluator Columns) -->
            <div class="overflow-x-auto overflow-y-auto max-h-[65vh]">
                <table class="min-w-full divide-y divide-gray-200 text-xs" :class="wrapText ? '' : 'whitespace-nowrap'">
                    <thead class="bg-slate-100 text-gray-800 font-bold uppercase tracking-wider text-[11px] sticky top-0 z-10 border-b border-gray-300 shadow-xs">
                        <tr>
                            <th class="px-3 py-3 text-center border-r border-gray-300 bg-slate-200/90 w-14 sticky left-0 z-20">NO</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-44">UPT</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-32">TANGGAL PENGAMBILAN IKLAN</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-32">TGL KIRIM KA UPT KE PUSAT</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-32">TANGGAL EVALUASI PUSAT</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-32">JENIS PRODUK</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-48">NAMA PRODUK</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36">NOMOR IZIN EDAR</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-56">KHASIAT/INDIKASI</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-44">PENDAFTAR</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-28">ASAL PRODUK</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-32">KATEGORI MEDIA</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36">MEDIA IKLAN</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-48">TEMPAT PENGAMBILAN IKLAN</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-40">PENCANTUMAN NOMOR IZIN EDAR</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-52">LINK URL IKLAN</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-64">KLAIM DALAM PROMOSI/IKLAN</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-64">KLASIFIKASI KLAIM</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36">PEMBUAT IKLAN</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-44">HASIL PENILAIAN IKLAN UPT</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36">KESIMPULAN UPT</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36">KESIMPULAN PUSAT</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-44">KESESUAIAN HASIL EVALUASI</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36">KATEGORI TMK</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-48">RINCIAN TMK</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-48">TINDAK LANJUT</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36 bg-blue-50/70 text-blue-900 font-bold">KUALITAS LAPORAN</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36 bg-blue-50/70 text-blue-900 font-bold">KETEPATAN WAKTU</th>
                            <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36 bg-blue-50/70 text-blue-900 font-bold">EVALUATOR</th>
                            <th class="px-3 py-3 text-center min-w-44 bg-blue-50/70 text-blue-900 font-bold">WAKTU</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($rows as $row)
                            @php
                                $isMine = auth()->check() && (int) auth()->id() === (int) $row->evaluator_id;
                                $isUnassigned = is_null($row->evaluator_id);
                                $isAdmin = auth()->check() && auth()->user()->isAdmin();
                                $canEditThisRow = ! $dataset->isFinal() && ($isAdmin || $isMine || $isUnassigned);
                                $isLockedByOther = ! $isAdmin && ! $isMine && ! $isUnassigned;
                            @endphp
                            <tr class="hover:bg-blue-50/30 transition-colors {{ $isLockedByOther ? 'bg-slate-50/70' : '' }}">
                                <td class="px-3 py-2.5 text-center font-bold text-gray-800 bg-slate-50 border-r border-gray-200 sticky left-0 z-10 align-top whitespace-nowrap">
                                    {{ $row->no !== null && $row->no !== '' ? $row->no : $row->nomor_baris }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-900 border-r border-gray-100 align-top min-w-44" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->upt }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->tanggal_pengambilan_iklan }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->tgl_kirim_ka_upt_ke_pusat }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->tanggal_evaluasi_pusat }}</td>
                                <td class="px-3 py-2.5 text-gray-800 border-r border-gray-100 align-top min-w-32" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->jenis_produk }}</td>
                                <td class="px-3 py-2.5 text-gray-900 font-medium border-r border-gray-100 align-top min-w-48 max-w-xs" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->nama_produk }}">{{ $row->nama_produk }}</td>
                                <td class="px-3 py-2.5 font-mono text-gray-800 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->nomor_izin_edar }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-56 max-w-sm" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->khasiat_indikasi }}">{{ $row->khasiat_indikasi }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-44 max-w-xs" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->pendaftar }}">{{ $row->pendaftar }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap text-center">{{ $row->asal_produk }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->kategori_media }}</td>
                                <td class="px-3 py-2.5 text-gray-800 border-r border-gray-100 align-top min-w-36" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->media_iklan }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-48 max-w-xs" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->tempat_pengambilan_iklan }}">{{ $row->tempat_pengambilan_iklan }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-40" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->pencantuman_nomor_izin_edar }}</td>
                                <td class="px-3 py-2.5 text-blue-600 border-r border-gray-100 align-top font-mono min-w-52 max-w-sm" :class="wrapText ? 'whitespace-normal break-all' : 'truncate'" title="{{ $row->link_url_iklan }}" style="word-break: break-all; overflow-wrap: anywhere;">
                                    @if ($row->link_url_iklan && str_starts_with($row->link_url_iklan, 'http'))
                                        <a href="{{ $row->link_url_iklan }}" target="_blank" rel="noopener noreferrer" class="hover:underline break-all block" :class="wrapText ? '' : 'truncate'" style="word-break: break-all; overflow-wrap: anywhere;">{{ $row->link_url_iklan }}</a>
                                    @else
                                        <span class="break-all block" :class="wrapText ? '' : 'truncate'" style="word-break: break-all; overflow-wrap: anywhere;">{{ $row->link_url_iklan }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-64 max-w-md" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->klaim_dalam_promosi_iklan }}">{{ $row->klaim_dalam_promosi_iklan }}</td>
                                <td class="px-3 py-2.5 text-gray-800 border-r border-gray-100 align-top min-w-64 max-w-md" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->klasifikasi_klaim }}">{{ $row->klasifikasi_klaim }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-36" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->pembuat_iklan }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-44 max-w-xs" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'">{{ $row->hasil_penilaian_iklan_upt }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-36 font-semibold" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->kesimpulan_upt }}">{{ $row->kesimpulan_upt }}</td>
                                <td class="px-3 py-2.5 text-gray-900 border-r border-gray-100 align-top min-w-36 font-semibold" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->kesimpulan_pusat }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-44" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->kesesuaian_hasil_evaluasi }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-36" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->kategori_tmk }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-48 max-w-xs" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->rincian_tmk }}">{{ $row->rincian_tmk }}</td>
                                <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-48 max-w-xs" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->tindak_lanjut }}">{{ $row->tindak_lanjut }}</td>

                                <td class="px-2 py-2.5 border-r border-gray-100 text-center align-top min-w-36">
                                    @if ($isLockedByOther)
                                        @if ($row->kualitas_laporan === 1)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                1 (Lengkap & Benar)
                                            </span>
                                        @elseif ($row->kualitas_laporan === 2)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                2 (Tidak Lengkap)
                                            </span>
                                        @else
                                            <span class="text-gray-400 font-mono text-xs">-</span>
                                        @endif
                                    @else
                                        <select
                                            data-col-select="kualitas_laporan"
                                            @focus="activateBulkOption({{ $row->id }}, 'kualitas_laporan', $event.target.value)"
                                            @change="saveCell({{ $row->id }}, 'kualitas_laporan', $event.target.value)"
                                            @disabled(! $canEditThisRow)
                                            class="w-full text-center rounded border border-gray-300 py-1 px-1 text-xs font-medium {{ $canEditThisRow ? 'bg-white text-gray-900 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}"
                                        >
                                            <option value="" @selected(is_null($row->kualitas_laporan))>-</option>
                                            <option value="1" @selected($row->kualitas_laporan === 1)>1 (Lengkap & Benar)</option>
                                            <option value="2" @selected($row->kualitas_laporan === 2)>2 (Tidak Lengkap)</option>
                                        </select>
                                        <div x-show="activeBulk.kualitas_laporan.rowId === {{ $row->id }} && activeBulk.kualitas_laporan.value !== ''" x-cloak class="mt-1">
                                            <button
                                                type="button"
                                                @click="applyToAllRows('kualitas_laporan', activeBulk.kualitas_laporan.value)"
                                                class="w-full px-2 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] transition-colors cursor-pointer"
                                            >
                                                Terapkan Semua
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-2 py-2.5 border-r border-gray-100 text-center align-top min-w-36">
                                    @if ($isLockedByOther)
                                        @if ($row->ketepatan_waktu === 1)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                1 (Tepat Waktu)
                                            </span>
                                        @elseif ($row->ketepatan_waktu === 2)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                2 (Tidak Tepat)
                                            </span>
                                        @else
                                            <span class="text-gray-400 font-mono text-xs">-</span>
                                        @endif
                                    @else
                                        <select
                                            data-col-select="ketepatan_waktu"
                                            @focus="activateBulkOption({{ $row->id }}, 'ketepatan_waktu', $event.target.value)"
                                            @change="saveCell({{ $row->id }}, 'ketepatan_waktu', $event.target.value)"
                                            @disabled(! $canEditThisRow)
                                            class="w-full text-center rounded border border-gray-300 py-1 px-1 text-xs font-medium {{ $canEditThisRow ? 'bg-white text-gray-900 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}"
                                        >
                                            <option value="" @selected(is_null($row->ketepatan_waktu))>-</option>
                                            <option value="1" @selected($row->ketepatan_waktu === 1)>1 (Tepat Waktu)</option>
                                            <option value="2" @selected($row->ketepatan_waktu === 2)>2 (Tidak Tepat)</option>
                                        </select>
                                        <div x-show="activeBulk.ketepatan_waktu.rowId === {{ $row->id }} && activeBulk.ketepatan_waktu.value !== ''" x-cloak class="mt-1">
                                            <button
                                                type="button"
                                                @click="applyToAllRows('ketepatan_waktu', activeBulk.ketepatan_waktu.value)"
                                                class="w-full px-2 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] transition-colors cursor-pointer"
                                            >
                                                Terapkan Semua
                                            </button>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-2 py-2.5 border-r border-gray-100 text-center align-top min-w-36">
                                    @if ($isLockedByOther)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200" title="Sudah dievaluasi oleh {{ $row->evaluator?->name ?? 'Evaluator lain' }} (Terkunci)">
                                            <svg class="w-3.5 h-3.5 shrink-0 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                            <span class="truncate max-w-32">{{ $row->evaluator?->name ?? 'Evaluator' }}</span>
                                        </span>
                                    @elseif ($isAdmin)
                                        <div class="relative min-w-36">
                                            <select
                                                name="evaluator_id"
                                                data-col-select="evaluator_id"
                                                data-row-evaluator="{{ $row->id }}"
                                                @focus="activateBulkOption({{ $row->id }}, 'evaluator_id', $event.target.value)"
                                                @change="saveCell({{ $row->id }}, 'evaluator_id', $event.target.value)"
                                                class="w-full rounded border border-gray-300 py-1 px-1.5 text-xs font-semibold focus:outline-none bg-white text-gray-900 cursor-pointer"
                                            >
                                                <option value="">-- Pilih Evaluator --</option>
                                                @foreach ($evaluators as $ev)
                                                    <option value="{{ $ev->id }}" @selected((int)$row->evaluator_id === (int)$ev->id)>
                                                        {{ $ev->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div x-show="activeBulk.evaluator_id.rowId === {{ $row->id }} && activeBulk.evaluator_id.value !== ''" x-cloak class="mt-1">
                                                <button
                                                    type="button"
                                                    @click="applyToAllRows('evaluator_id', activeBulk.evaluator_id.value)"
                                                    class="w-full px-2 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] transition-colors cursor-pointer"
                                                >
                                                    Terapkan Semua
                                                </button>
                                            </div>
                                        </div>
                                    @elseif ($isMine)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            <svg class="w-3.5 h-3.5 shrink-0 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            <span>{{ auth()->user()->name }} (Saya)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500 italic">
                                            Tersedia
                                        </span>
                                    @endif
                                </td>

                                @php
                                    $waktuProses = $row->updated_at ?? $row->created_at ?? $dataset->updated_at ?? ($row->evaluator?->last_login_at ?? auth()->user()?->last_login_at ?? now());
                                @endphp
                                <td class="px-3 py-2.5 text-center text-xs text-gray-700 font-medium align-top min-w-44" data-row-waktu="{{ $row->id }}">
                                    <span class="font-bold text-gray-900 block">{{ $waktuProses->translatedFormat('d F Y') }}</span>
                                    <span class="block text-[10px] text-gray-500 mt-0.5">{{ $waktuProses->format('H:i:s') }} WIB</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="30" class="px-4 py-12 text-center text-gray-400">
                                    @if (!empty($hasCommodityMismatch))
                                        Belum ada dataset yang diunggah untuk komoditi {{ $displayKomoditi }} pada periode ini.
                                    @elseif ($cari)
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

            <!-- Table Footer: Showing X to Y of Z entries & Pagination -->
            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-gray-600">
                <div>
                    Showing <strong>{{ $rows->firstItem() ?? 0 }}</strong> to <strong>{{ $rows->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($rows->total(), 0, ',', '.') }}</strong> entries
                </div>
                @if ($rows->hasPages())
                    <div>
                        {{ $rows->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function datasetShowApp(config) {
            return {
                status: config.status,
                isPolling: config.isPolling,
                isFinal: config.isFinal,
                activeCellSaving: null,
                toastMessage: null,
                toastType: 'error',
                pollInterval: null,
                wrapText: true,
                activeBulk: {
                    kualitas_laporan: { rowId: null, value: '' },
                    ketepatan_waktu: { rowId: null, value: '' },
                    evaluator_id: { rowId: null, value: '' }
                },
                showToast(msg, type = 'error') {
                    this.toastType = type;
                    this.toastMessage = msg;
                    setTimeout(() => { this.toastMessage = null; }, 5000);
                },
                activateBulkOption(rowId, kolom, val) {
                    if (val !== '' && val !== null && val !== undefined) {
                        this.activeBulk[kolom] = { rowId: rowId, value: String(val) };
                    }
                },
                updateRowWaktuHtml(rowId, waktu) {
                    const cell = document.querySelector(`[data-row-waktu="${rowId}"]`);
                    if (cell && waktu) {
                        cell.innerHTML = `
                            <span class="font-bold text-gray-900 block">${waktu.tanggal} ${waktu.bulan} ${waktu.tahun}</span>
                            <span class="block text-[10px] text-gray-500 mt-0.5">${waktu.jam} WIB</span>
                        `;
                    }
                },
                saveEvaluatorCell(itemId, val) {
                    return this.saveCell(itemId, 'evaluator_id', val);
                },
                saveCell(itemId, kolom, val) {
                    this.activeCellSaving = `${itemId}_${kolom}`;
                    this.activeBulk[kolom] = {
                        rowId: val !== '' && val !== null ? itemId : null,
                        value: val !== '' && val !== null ? String(val) : ''
                    };
                    fetch(`/dataset/${config.datasetId}/data-mentah/manual-cell/${itemId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ kolom: kolom, nilai: val ? parseInt(val) : null })
                    })
                    .then(async res => {
                        if (!res.ok) {
                            const errData = await res.json().catch(() => ({}));
                            throw new Error(errData.error || errData.message || 'Gagal menyimpan sel.');
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data && data.waktu) {
                            this.updateRowWaktuHtml(itemId, data.waktu);
                        }
                        if (data && data.evaluator_id) {
                            const evSelect = document.querySelector(`select[data-row-evaluator="${itemId}"]`);
                            if (evSelect && !evSelect.value) {
                                evSelect.value = String(data.evaluator_id);
                            }
                        }
                        setTimeout(() => { this.activeCellSaving = null; }, 500);
                    })
                    .catch(err => {
                        this.activeCellSaving = null;
                        this.showToast(err.message || 'Terjadi kesalahan saat menyimpan data.');
                    });
                },
                applyToAllRows(kolom, val) {
                    if (val === '' || val === null || val === undefined) return;

                    // Update all dropdowns in this column on screen immediately
                    document.querySelectorAll(`select[data-col-select="${kolom}"]`).forEach(sel => {
                        if (!sel.disabled) {
                            sel.value = String(val);
                        }
                    });

                    fetch(config.bulkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            kolom: kolom,
                            nilai: parseInt(val),
                            row_ids: config.visibleRowIds
                        })
                    })
                    .then(async res => {
                        if (!res.ok) {
                            const errData = await res.json().catch(() => ({}));
                            throw new Error(errData.error || errData.message || 'Gagal menerapkan ke semua baris.');
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data && data.waktu && Array.isArray(config.visibleRowIds)) {
                            config.visibleRowIds.forEach(rId => {
                                this.updateRowWaktuHtml(rId, data.waktu);
                            });
                        }
                        if (data && data.auto_evaluator_id) {
                            document.querySelectorAll('select[data-col-select="evaluator_id"]').forEach(sel => {
                                if (!sel.disabled && !sel.value) {
                                    sel.value = String(data.auto_evaluator_id);
                                }
                            });
                        }
                        this.activeBulk[kolom] = { rowId: null, value: '' };
                        this.showToast(data.message || 'Berhasil menerapkan ke seluruh baris!', 'success');
                    })
                    .catch(err => {
                        this.showToast(err.message || 'Gagal menerapkan ke seluruh baris.');
                    });
                },
                init() {
                    if (this.isPolling) {
                        this.pollInterval = setInterval(() => {
                            fetch(config.statusUrl)
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
            };
        }
    </script>
</x-layouts.app>
