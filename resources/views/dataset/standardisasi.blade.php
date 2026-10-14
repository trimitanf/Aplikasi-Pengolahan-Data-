<x-layouts.app title="Standardisasi dan Input Manual - {{ $dataset->nama_file_asli }}">
    <x-slot:header>
        Standardisasi dan Input Manual: {{ $dataset->nama_komoditi }} ({{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }})
    </x-slot:header>
    <x-slot:breadcrumb>
        <a href="{{ route('dataset.index') }}" class="hover:underline">Dataset</a> /
        <a href="{{ route('dataset.show', $dataset) }}" class="hover:underline">Detail</a> /
        Standardisasi
    </x-slot:breadcrumb>

    <!-- Stepper Navigation -->
    <x-stepper :activeStep="4" :datasetId="$dataset->id" />

    <!-- Top Summary Statistics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-(--border) shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Total Baris</span>
            <p class="text-xl font-extrabold text-gray-900 mt-0.5">{{ number_format($totalBaris, 0, ',', '.') }}</p>
        </div>
        <div class="bg-emerald-50/70 p-3.5 rounded-xl border border-emerald-200 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Terstandar Lengkap</span>
            <p class="text-xl font-extrabold text-emerald-800 mt-0.5">{{ number_format($terstandarCount, 0, ',', '.') }}</p>
        </div>
        <div class="bg-amber-50/70 p-3.5 rounded-xl border border-amber-200 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Perlu Koreksi</span>
            <p class="text-xl font-extrabold text-amber-800 mt-0.5">{{ number_format($perluKoreksiCount, 0, ',', '.') }}</p>
        </div>
        <div class="bg-blue-50/70 p-3.5 rounded-xl border border-blue-200 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700">Menunggu Evaluasi</span>
            <p class="text-xl font-extrabold text-blue-800 mt-0.5">{{ number_format($menungguEvaluasiCount, 0, ',', '.') }}</p>
        </div>
        <div class="bg-purple-50/70 p-3.5 rounded-xl border border-purple-200 shadow-2xs col-span-2 sm:col-span-1">
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700">Manual Terisi</span>
            <p class="text-xl font-extrabold text-purple-800 mt-0.5">
                {{ number_format($manualTerisiCount, 0, ',', '.') }} / {{ number_format($totalBaris - $menungguEvaluasiCount, 0, ',', '.') }}
            </p>
        </div>
    </div>

    <!-- Main Table & Actions Container -->
    <div
        x-data="{
            isFinal: {{ $dataset->isFinal() ? 'true' : 'false' }},
            activeCellSaving: null,
            saveCell(itemId, kolom, val) {
                if (this.isFinal) return;
                this.activeCellSaving = `${itemId}_${kolom}`;
                fetch(`/dataset/{{ $dataset->id }}/manual-cell/${itemId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ kolom: kolom, nilai: val ? parseInt(val) : null })
                })
                .then(res => res.json())
                .then(data => {
                    setTimeout(() => {
                        this.activeCellSaving = null;
                    }, 800);
                })
                .catch(err => {
                    console.error(err);
                    this.activeCellSaving = null;
                });
            }
        }"
        class="space-y-4"
    >
        <!-- Action Toolbar -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white p-4 rounded-xl border border-(--border) shadow-xs">
            <!-- Left: Filter & Search -->
            <form method="GET" action="{{ route('dataset.standardisasi', $dataset) }}" class="flex flex-wrap items-center gap-2">
                <select
                    name="filter"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none"
                    onchange="this.form.submit()"
                >
                    <option value="semua" @selected($filter === 'semua')>Semua Baris ({{ $totalBaris }})</option>
                    <option value="perlu_koreksi" @selected($filter === 'perlu_koreksi')>Perlu Koreksi ({{ $perluKoreksiCount }})</option>
                    <option value="belum_manual" @selected($filter === 'belum_manual')>Manual Belum Lengkap</option>
                    <option value="menunggu_evaluasi" @selected($filter === 'menunggu_evaluasi')>Menunggu Evaluasi ({{ $menungguEvaluasiCount }})</option>
                </select>

                <div class="relative w-56">
                    <input
                        type="text"
                        name="cari"
                        value="{{ $cari }}"
                        placeholder="Cari produk / NIE..."
                        class="w-full rounded-lg border border-gray-300 pl-7 pr-2.5 py-1.5 text-xs text-gray-900 focus:border-blue-600 focus:outline-none"
                    />
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <x-button type="submit" variant="primary" size="sm">Cari</x-button>
                @if ($cari || $filter !== 'semua')
                    <a href="{{ route('dataset.standardisasi', $dataset) }}" class="px-2 py-1.5 text-xs text-gray-500 hover:text-gray-700">Reset</a>
                @endif
            </form>

            <!-- Right: Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                @if (!$dataset->isFinal())
                    <!-- Tombol Isi Massal -->
                    <x-button
                        type="button"
                        variant="secondary"
                        size="sm"
                        @click="$dispatch('open-modal', 'modal-isi-massal')"
                    >
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Isi Massal Manual</span>
                    </x-button>

                    <!-- Tombol Konversi Ulang -->
                    <form method="POST" action="{{ route('dataset.konversi.ulang', $dataset) }}" class="inline-block">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm" title="Jalankan ulang konversi otomatis jika aturan pemetaan telah ditambahkan">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Jalankan Ulang Konversi</span>
                        </x-button>
                    </form>
                @endif

                <!-- Tombol Export Excel -->
                <a
                    href="{{ route('dataset.export', $dataset) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 text-xs font-semibold hover:bg-emerald-100 transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export Excel (.xlsx)</span>
                </a>

                @if (!$dataset->isFinal())
                    <!-- Tombol Simpan Final -->
                    <x-button
                        type="button"
                        variant="primary"
                        size="sm"
                        @click="$dispatch('open-modal', 'modal-simpan-final')"
                    >
                        <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Simpan Final</span>
                    </x-button>
                @else
                    <x-badge variant="primary" size="md">
                        ✓ DATASET SUDAH FINAL
                    </x-badge>
                @endif
            </div>
        </div>

        <!-- Panel Nilai Belum Terpetakan (Accordion / Warning Box) -->
        @if (!empty($unmappedSummaries) && !$dataset->isFinal())
            <div x-data="{ open: true }" class="bg-amber-50 rounded-xl border border-amber-300 p-4 shadow-2xs">
                <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-amber-500 text-white font-bold flex items-center justify-center text-xs">!</span>
                        <h4 class="text-sm font-bold text-amber-900">
                            Terdapat {{ count($unmappedSummaries) }} Teks Unik Belum Terpetakan ke Kode Angka
                        </h4>
                        <span class="text-xs text-amber-700 hidden sm:inline">(Tetapkan aturan sekali untuk diterapkan ke semua baris yang sama)</span>
                    </div>
                    <button type="button" class="text-amber-800 text-xs font-semibold hover:underline">
                        <span x-text="open ? 'Sembunyikan Panel' : 'Buka Panel Pemetaan'"></span>
                    </button>
                </div>

                <div x-show="open" class="mt-4 space-y-3 pt-3 border-t border-amber-200/60">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($unmappedSummaries as $unm)
                            <div class="bg-white p-3.5 rounded-lg border border-amber-200 shadow-2xs flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <x-badge variant="warning" size="sm">{{ strtoupper(str_replace('_', ' ', $unm['kolom'])) }}</x-badge>
                                        <span class="text-xs font-bold text-amber-900">{{ $unm['jumlah'] }} Baris</span>
                                    </div>
                                    <p class="text-xs font-semibold text-gray-900 font-mono break-all mt-1 bg-gray-50 p-1.5 rounded border border-gray-100">
                                        {{ $unm['nilai'] }}
                                    </p>
                                    <p class="text-[10px] text-gray-500 mt-1">
                                        Contoh produk: {{ $unm['contoh_produk'] }} (Baris {{ $unm['contoh_baris'] }})
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('dataset.mapping.save', $dataset) }}" class="flex items-center gap-2 pt-1 border-t border-gray-100">
                                    @csrf
                                    <input type="hidden" name="kolom_sumber" value="{{ match($unm['kolom']) {
                                        'upt' => 'UPT',
                                        'klaim' => 'KLASIFIKASI KLAIM',
                                        'nama_media' => 'TEMPAT PENGAMBILAN IKLAN',
                                        'rincian_tmk' => 'RINCIAN TMK',
                                        'pembuat_iklan' => 'PEMBUAT IKLAN',
                                        default => strtoupper($unm['kolom'])
                                    } }}">
                                    <input type="hidden" name="nilai_sipt" value="{{ $unm['nilai'] }}">
                                    <input type="hidden" name="grup" value="{{ $unm['kolom'] }}">

                                    <div class="flex-1">
                                        <select
                                            name="kode"
                                            class="w-full rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none"
                                            required
                                        >
                                            <option value="">-- Petakan ke Kode --</option>
                                            @php
                                                $options = $masterDict[$unm['kolom']] ?? [];
                                            @endphp
                                            @foreach ($options as $k => $lbl)
                                                <option value="{{ $k }}">{{ $k }} - {{ Str::limit($lbl, 35) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <x-button type="submit" variant="primary" size="sm">Terapkan</x-button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Standard Table: 18 Columns matching Sheet Data All -->
        <div class="bg-white rounded-xl border border-(--border) shadow-xs overflow-hidden">
            <div class="overflow-x-auto max-h-[75vh]">
                <table class="min-w-full divide-y divide-gray-200 text-xs text-center whitespace-nowrap">
                    <!-- Column Header Row with Excel styling tints -->
                    <thead class="sticky top-0 z-20 font-bold uppercase tracking-wider text-[11px] shadow-xs">
                        <tr class="bg-[#FFF59D] text-gray-800">
                            <!-- 1. NO -->
                            <th class="px-2.5 py-3 border-r border-gray-300 bg-[#FFF59D] sticky left-0 z-30 w-12">NO</th>
                            <!-- 2. UPT -->
                            <th class="px-3 py-3 border-r border-gray-300 w-14">UPT</th>
                            <!-- 3. PERIODE BULAN -->
                            <th class="px-2.5 py-3 border-r border-gray-300 w-16">PERIODE BULAN</th>
                            <!-- 4. JENIS PRODUK (Tint Peach) -->
                            <th class="px-3 py-3 border-r border-gray-300 bg-[#FCE4EC] text-pink-950 w-16">JENIS PRODUK</th>
                            <!-- 5. ASAL PRODUK -->
                            <th class="px-3 py-3 border-r border-gray-300 w-16">ASAL PRODUK</th>
                            <!-- 6. KLAIM (Tint Peach) -->
                            <th class="px-3 py-3 border-r border-gray-300 bg-[#FCE4EC] text-pink-950 w-16">KLAIM</th>
                            <!-- 7. KATEGORI MEDIA -->
                            <th class="px-3 py-3 border-r border-gray-300 w-16">KATEGORI MEDIA</th>
                            <!-- 8. KATEGORI IKLAN (Tint Green) -->
                            <th class="px-3 py-3 border-r border-gray-300 bg-[#E8F5E9] text-emerald-950 w-16">KATEGORI IKLAN</th>
                            <!-- 9. NAMA MEDIA -->
                            <th class="px-3 py-3 border-r border-gray-300 w-16">NAMA MEDIA</th>
                            <!-- 10. PENCANTUMAN NIE -->
                            <th class="px-3 py-3 border-r border-gray-300 w-16">PENCANTUMAN NIE</th>
                            <!-- 11. KESESUAIAN NIE -->
                            <th class="px-3 py-3 border-r border-gray-300 w-16">KESESUAIAN NIE</th>
                            <!-- 12. HASIL EVALUASI (Tint Cyan) -->
                            <th class="px-3 py-3 border-r border-gray-300 bg-[#E0F7FA] text-cyan-950 w-16">HASIL EVALUASI</th>
                            <!-- 13. RINCIAN TMK -->
                            <th class="px-3 py-3 border-r border-gray-300 w-16">RINCIAN TMK</th>
                            <!-- 14. JENIS TMK -->
                            <th class="px-3 py-3 border-r border-gray-300 w-16">JENIS TMK</th>
                            <!-- 15. PEMBUAT IKLAN -->
                            <th class="px-3 py-3 border-r border-gray-300 w-16">PEMBUAT IKLAN</th>
                            <!-- 16, 17, 18. TIGA KOLOM MANUAL (Soft Red) -->
                            <th class="px-3 py-3 border-r border-red-300 bg-[#FFCDD2] text-red-900 min-w-[130px]">
                                <div>KESESUAIAN EVALUASI</div>
                                <span class="inline-block mt-0.5 text-[9px] bg-red-600 text-white px-1.5 py-0.2 rounded font-normal">Input manual</span>
                            </th>
                            <th class="px-3 py-3 border-r border-red-300 bg-[#FFCDD2] text-red-900 min-w-[130px]">
                                <div>KUALITAS LAPORAN</div>
                                <span class="inline-block mt-0.5 text-[9px] bg-red-600 text-white px-1.5 py-0.2 rounded font-normal">Input manual</span>
                            </th>
                            <th class="px-3 py-3 bg-[#FFCDD2] text-red-900 min-w-[130px]">
                                <div>KETEPATAN WAKTU</div>
                                <span class="inline-block mt-0.5 text-[9px] bg-red-600 text-white px-1.5 py-0.2 rounded font-normal">Input manual</span>
                            </th>
                        </tr>
                    </thead>

                    <!-- Table Data Body -->
                    <tbody class="divide-y divide-gray-100 bg-white font-medium">
                        @forelse ($items as $item)
                            @php
                                $isMenunggu = $item->isMenungguEvaluasi();
                                $unm = $item->catatan_tidak_terpetakan ?? [];
                            @endphp
                            <tr @class([
                                'hover:bg-blue-50/50 transition-colors',
                                'bg-blue-50/20' => $isMenunggu,
                            ])>
                                <!-- 1. NO -->
                                <td class="px-2.5 py-2 font-bold text-gray-700 bg-gray-50 border-r border-gray-200 sticky left-0 z-10 shadow-2xs">
                                    {{ $item->no }}
                                </td>

                                <!-- 2. UPT -->
                                <td class="px-3 py-2 border-r border-gray-100 {{ isset($unm['upt']) ? 'bg-amber-100 text-amber-900' : '' }}" title="{{ $masterDict['upt'][$item->upt] ?? ($item->dataMentah->upt ?? '') }}">
                                    @if ($item->upt !== null)
                                        {{ $item->upt }}
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold" title="Belum terpetakan: {{ $item->dataMentah->upt ?? '' }}">?</span>
                                    @endif
                                </td>

                                <!-- 3. PERIODE BULAN -->
                                <td class="px-2.5 py-2 border-r border-gray-100 text-gray-800">
                                    {{ $item->periode_bulan }}
                                </td>

                                <!-- 4. JENIS PRODUK -->
                                <td class="px-3 py-2 border-r border-gray-100 bg-pink-50/20 {{ isset($unm['jenis_produk']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['jenis_produk'][$item->jenis_produk] ?? '' }}">
                                    @if ($item->jenis_produk !== null)
                                        {{ $item->jenis_produk }}
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold">?</span>
                                    @endif
                                </td>

                                <!-- 5. ASAL PRODUK -->
                                <td class="px-3 py-2 border-r border-gray-100 {{ isset($unm['asal_produk']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['asal_produk'][$item->asal_produk] ?? '' }}">
                                    @if ($item->asal_produk !== null)
                                        {{ $item->asal_produk }}
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold">?</span>
                                    @endif
                                </td>

                                <!-- 6. KLAIM -->
                                <td class="px-3 py-2 border-r border-gray-100 bg-pink-50/20 {{ isset($unm['klaim']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['klaim'][$item->klaim] ?? ($item->dataMentah->klasifikasi_klaim ?? '') }}">
                                    @if ($item->klaim !== null)
                                        {{ $item->klaim }}
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold" title="Klaim tak terpetakan: {{ $item->dataMentah->klasifikasi_klaim ?? '' }}">?</span>
                                    @endif
                                </td>

                                <!-- 7. KATEGORI MEDIA -->
                                <td class="px-3 py-2 border-r border-gray-100 {{ isset($unm['kategori_media']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['kategori_media'][$item->kategori_media] ?? '' }}">
                                    @if ($item->kategori_media !== null)
                                        {{ $item->kategori_media }}
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold">?</span>
                                    @endif
                                </td>

                                <!-- 8. KATEGORI IKLAN -->
                                <td class="px-3 py-2 border-r border-gray-100 bg-emerald-50/20 {{ isset($unm['kategori_iklan']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['kategori_iklan'][$item->kategori_iklan] ?? '' }}">
                                    @if ($item->kategori_iklan !== null)
                                        {{ $item->kategori_iklan }}
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold">?</span>
                                    @endif
                                </td>

                                <!-- 9. NAMA MEDIA -->
                                <td class="px-3 py-2 border-r border-gray-100 {{ isset($unm['nama_media']) ? 'bg-amber-100 text-amber-900' : '' }}" title="{{ $masterDict['nama_media'][$item->nama_media] ?? ($item->dataMentah->tempat_pengambilan_iklan ?? '') }}">
                                    @if ($item->nama_media !== null)
                                        {{ $item->nama_media }}
                                    @elseif (in_array($item->kategori_iklan, [2, 6, 7, 8]))
                                        <span class="text-gray-400">-</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-300 text-gray-900 font-bold" title="Tempat: {{ $item->dataMentah->tempat_pengambilan_iklan ?? '-' }}">?</span>
                                    @endif
                                </td>

                                <!-- 10. PENCANTUMAN NIE -->
                                <td class="px-3 py-2 border-r border-gray-100 {{ isset($unm['pencantuman_nie']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['pencantuman_nie'][$item->pencantuman_nie] ?? '' }}">
                                    @if ($item->pencantuman_nie !== null)
                                        {{ $item->pencantuman_nie }}
                                    @elseif ($item->kategori_iklan !== 1)
                                        <span class="text-gray-400">-</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold">?</span>
                                    @endif
                                </td>

                                <!-- 11. KESESUAIAN NIE -->
                                <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['kesesuaian_nie'][$item->kesesuaian_nie] ?? '' }}">
                                    @if ($item->kesesuaian_nie !== null)
                                        {{ $item->kesesuaian_nie }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                <!-- 12. HASIL EVALUASI -->
                                <td class="px-3 py-2 border-r border-gray-100 bg-cyan-50/20 font-bold" title="{{ $masterDict['hasil_evaluasi'][$item->hasil_evaluasi] ?? 'Menunggu Evaluasi' }}">
                                    @if ($item->hasil_evaluasi === 1)
                                        <span class="text-emerald-700">1</span>
                                    @elseif ($item->hasil_evaluasi === 2)
                                        <span class="text-red-700">2</span>
                                    @else
                                        <span class="text-gray-300 italic text-[10px]">menunggu</span>
                                    @endif
                                </td>

                                <!-- 13. RINCIAN TMK -->
                                <td class="px-3 py-2 border-r border-gray-100 {{ isset($unm['rincian_tmk']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['rincian_tmk'][$item->rincian_tmk] ?? '' }}">
                                    @if ($item->rincian_tmk !== null)
                                        {{ $item->rincian_tmk }}
                                    @elseif ($item->hasil_evaluasi === 2)
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold" title="{{ $item->dataMentah->rincian_tmk ?? '' }}">?</span>
                                    @else
                                        <span class="text-gray-200"></span>
                                    @endif
                                </td>

                                <!-- 14. JENIS TMK -->
                                <td class="px-3 py-2 border-r border-gray-100 {{ isset($unm['jenis_tmk']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['jenis_tmk'][$item->jenis_tmk] ?? '' }}">
                                    @if ($item->jenis_tmk !== null)
                                        {{ $item->jenis_tmk }}
                                    @elseif ($item->hasil_evaluasi === 2)
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold" title="{{ $item->dataMentah->kesimpulan_pusat ?? '' }}">?</span>
                                    @else
                                        <span class="text-gray-200"></span>
                                    @endif
                                </td>

                                <!-- 15. PEMBUAT IKLAN -->
                                <td class="px-3 py-2 border-r border-gray-100 {{ isset($unm['pembuat_iklan']) ? 'bg-amber-100' : '' }}" title="{{ $masterDict['pembuat_iklan'][$item->pembuat_iklan] ?? '' }}">
                                    @if ($item->pembuat_iklan !== null)
                                        {{ $item->pembuat_iklan }}
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-amber-400 text-gray-900 font-bold">?</span>
                                    @endif
                                </td>

                                <!-- 16. KESESUAIAN EVALUASI (MANUAL 1) -->
                                <td class="px-2 py-1.5 border-r border-red-200 bg-red-50/20">
                                    @if ($isMenunggu)
                                        <span class="text-gray-300">-</span>
                                    @elseif ($dataset->isFinal())
                                        <span class="font-bold text-gray-800">{{ $item->kesesuaian_evaluasi ?? '-' }}</span>
                                    @else
                                        <div class="relative">
                                            <select
                                                @change="saveCell({{ $item->id }}, 'kesesuaian_evaluasi', $event.target.value)"
                                                class="w-full text-center rounded border border-red-200 bg-white py-1 text-xs font-bold text-gray-900 focus:border-red-500 focus:outline-none"
                                            >
                                                <option value="" @selected(is_null($item->kesesuaian_evaluasi))>-</option>
                                                <option value="1" @selected($item->kesesuaian_evaluasi === 1)>1 (Sesuai)</option>
                                                <option value="2" @selected($item->kesesuaian_evaluasi === 2)>2 (Tidak Sesuai)</option>
                                            </select>
                                            <span x-show="activeCellSaving === '{{ $item->id }}_kesesuaian_evaluasi'" class="absolute -top-3 right-0 text-[8px] text-emerald-600 font-bold">Tersimpan</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- 17. KUALITAS LAPORAN (MANUAL 2) -->
                                <td class="px-2 py-1.5 border-r border-red-200 bg-red-50/20">
                                    @if ($isMenunggu)
                                        <span class="text-gray-300">-</span>
                                    @elseif ($dataset->isFinal())
                                        <span class="font-bold text-gray-800">{{ $item->kualitas_laporan ?? '-' }}</span>
                                    @else
                                        <div class="relative">
                                            <select
                                                @change="saveCell({{ $item->id }}, 'kualitas_laporan', $event.target.value)"
                                                class="w-full text-center rounded border border-red-200 bg-white py-1 text-xs font-bold text-gray-900 focus:border-red-500 focus:outline-none"
                                            >
                                                <option value="" @selected(is_null($item->kualitas_laporan))>-</option>
                                                <option value="1" @selected($item->kualitas_laporan === 1)>1 (Lengkap & Benar)</option>
                                                <option value="2" @selected($item->kualitas_laporan === 2)>2 (Tidak Lengkap)</option>
                                            </select>
                                            <span x-show="activeCellSaving === '{{ $item->id }}_kualitas_laporan'" class="absolute -top-3 right-0 text-[8px] text-emerald-600 font-bold">Tersimpan</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- 18. KETEPATAN WAKTU (MANUAL 3) -->
                                <td class="px-2 py-1.5 bg-red-50/20">
                                    @if ($isMenunggu)
                                        <span class="text-gray-300">-</span>
                                    @elseif ($dataset->isFinal())
                                        <span class="font-bold text-gray-800">{{ $item->ketepatan_waktu ?? '-' }}</span>
                                    @else
                                        <div class="relative">
                                            <select
                                                @change="saveCell({{ $item->id }}, 'ketepatan_waktu', $event.target.value)"
                                                class="w-full text-center rounded border border-red-200 bg-white py-1 text-xs font-bold text-gray-900 focus:border-red-500 focus:outline-none"
                                            >
                                                <option value="" @selected(is_null($item->ketepatan_waktu))>-</option>
                                                <option value="1" @selected($item->ketepatan_waktu === 1)>1 (Tepat Waktu)</option>
                                                <option value="2" @selected($item->ketepatan_waktu === 2)>2 (Tidak Tepat)</option>
                                            </select>
                                            <span x-show="activeCellSaving === '{{ $item->id }}_ketepatan_waktu'" class="absolute -top-3 right-0 text-[8px] text-emerald-600 font-bold">Tersimpan</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="18" class="px-4 py-12 text-center text-gray-400">
                                    Tidak ada data yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="p-4 border-t border-gray-200">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Isi Massal Tiga Kolom Manual -->
    <x-modal name="modal-isi-massal" title="Isi Massal Tiga Kolom Evaluator" maxWidth="md">
        <form method="POST" action="{{ route('dataset.bulk-manual', $dataset) }}" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <label for="bulk_kolom" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                    Pilih Kolom Manual <span class="text-red-500">*</span>
                </label>
                <select
                    id="bulk_kolom"
                    name="kolom"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-blue-600 focus:outline-none"
                    required
                >
                    <option value="kesesuaian_evaluasi">16. KESESUAIAN EVALUASI</option>
                    <option value="kualitas_laporan">17. KUALITAS LAPORAN</option>
                    <option value="ketepatan_waktu">18. KETEPATAN WAKTU</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="bulk_nilai" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                    Nilai Kode yang Ditetapkan <span class="text-red-500">*</span>
                </label>
                <select
                    id="bulk_nilai"
                    name="nilai"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-blue-600 focus:outline-none"
                    required
                >
                    <option value="1">1 (Sesuai / Lengkap & Benar / Tepat Waktu)</option>
                    <option value="2">2 (Tidak Sesuai / Tidak Lengkap / Tidak Tepat Waktu)</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="bulk_cakupan" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                    Terapkan Pada Baris <span class="text-red-500">*</span>
                </label>
                <select
                    id="bulk_cakupan"
                    name="cakupan"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-blue-600 focus:outline-none"
                    required
                >
                    <option value="belum_terisi">Hanya baris yang belum terisi (kosong)</option>
                    <option value="semua_dievaluasi">Semua baris terhitung (menimpa yang sudah ada)</option>
                </select>
            </div>

            <p class="text-[11px] text-gray-500 leading-relaxed">
                Catatan: Baris berstatus "Menunggu Evaluasi" otomatis dilewati dari pengisian manual.
            </p>

            <div class="pt-2 flex justify-end gap-2.5">
                <x-button type="button" variant="secondary" size="sm" @click="$dispatch('close-modal', 'modal-isi-massal')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="sm">
                    Terapkan Isi Massal
                </x-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Konfirmasi Simpan Final -->
    <x-modal name="modal-simpan-final" title="Konfirmasi Simpan Final Dataset" maxWidth="md">
        <div class="space-y-3.5">
            <div class="p-3 bg-blue-50 text-blue-900 rounded-xl border border-blue-200 text-xs leading-relaxed">
                <strong>Simpan Final Atomik:</strong>
                <ul class="list-disc list-inside mt-1 space-y-0.5">
                    <li>Semua sel pada kolom wajib harus sudah terpetakan.</li>
                    <li>Tiga kolom manual (Kesesuaian, Kualitas, Ketepatan) harus terisi lengkap.</li>
                    <li>Sistem akan menerbitkan <strong>Nomor Urut Resmi (NO)</strong> melanjutkan NO terakhir pada tahun {{ $dataset->periode_tahun }}.</li>
                    <li>Dataset yang sudah Final <strong>dikunci permanen</strong> dan otomatis masuk ke perhitungan Rekap & Dashboard.</li>
                </ul>
            </div>
            <p class="text-xs text-gray-600">
                Apakah seluruh verifikasi dan evaluasi telah selesai dilakukan?
            </p>
        </div>

        <x-slot:footer>
            <x-button type="button" variant="secondary" size="sm" @click="$dispatch('close-modal', 'modal-simpan-final')">
                Batal
            </x-button>
            <form method="POST" action="{{ route('dataset.simpan-final', $dataset) }}">
                @csrf
                <x-button type="submit" variant="primary" size="sm">
                    Ya, Simpan Final Sekarang
                </x-button>
            </form>
        </x-slot:footer>
    </x-modal>
</x-layouts.app>
