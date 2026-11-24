<x-layouts.app title="Data Terstandarisasi">
    <x-slot:header>
        Data Terstandarisasi
    </x-slot:header>
    <x-slot:breadcrumb>
        Data Terstandarisasi
    </x-slot:breadcrumb>


    <!-- Filters & Export Toolbar -->
    <x-card>
        <form method="GET" action="{{ route('data-tersimpan.index') }}" class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 {{ auth()->user()?->isAdmin() ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }} gap-3 flex-1">
                <!-- Tahun -->
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Tahun</label>
                    <select
                        name="tahun"
                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                    >
                        @for ($y = now()->year + 1; $y >= 2024; $y--)
                            <option value="{{ $y }}" @selected($filters['tahun'] == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Rentang Bulan -->
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Bulan Awal</label>
                    <select
                        name="bulan_awal"
                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                    >
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($filters['bulan_awal'] == $m)>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Bulan Akhir</label>
                    <select
                        name="bulan_akhir"
                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                    >
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($filters['bulan_akhir'] == $m)>
                                {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Komoditi -->
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Komoditi</label>
                    <select
                        name="komoditi"
                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                    >
                        <option value="">Semua Komoditi</option>
                        <option value="OBA" @selected(($filters['komoditi'] ?? null) === 'OBA' || ($filters['komoditi'] ?? null) === 'OT')>Obat Bahan Alam (OBA)</option>
                        <option value="OK" @selected(($filters['komoditi'] ?? null) === 'OK')>Obat Kuasi (OK)</option>
                        <option value="SK" @selected(($filters['komoditi'] ?? null) === 'SK')>Suplemen Kesehatan (SK)</option>
                    </select>
                </div>

                <!-- UPT -->
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">UPT</label>
                    <select
                        name="upt"
                        class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                    >
                        <option value="">Semua UPT</option>
                        @foreach ($uptList ?? [] as $u)
                            <option value="{{ $u->kode }}" @selected(($filters['upt'] ?? null) == $u->kode)>
                                {{ $u->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Evaluator (Hanya di Laman Admin) -->
                @if (auth()->user()?->isAdmin())
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Evaluator</label>
                        <select
                            name="evaluator_id"
                            class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                        >
                            <option value="">Semua Evaluator</option>
                            @foreach ($evaluators as $ev)
                                <option value="{{ $ev->id }}" @selected(($filters['evaluator_id'] ?? null) == $ev->id)>
                                    {{ $ev->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <x-button type="submit" variant="primary" size="sm">
                    Terapkan Filter
                </x-button>

                <a
                    href="{{ route('data-tersimpan.export', request()->query()) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 text-xs font-semibold hover:bg-emerald-100 transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export Excel (.xlsx)</span>
                </a>
            </div>
        </form>
    </x-card>

    <!-- Active Pelanggaran Banner -->
    @if (!empty($activePelanggaranName) || !empty($filters['pelanggaran']))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-rose-500 text-white flex items-center justify-center font-black text-base shrink-0 shadow-xs">
                    !
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-xs sm:text-sm font-extrabold text-rose-950">
                            Menampilkan Baris Terindikasi TMK: <span class="text-rose-700 underline decoration-rose-400">{{ $activePelanggaranName ?? 'Pelanggaran TMK' }}</span>
                        </h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-200 text-rose-900">
                            Filter Aktif
                        </span>
                    </div>
                    <p class="text-[11px] text-rose-700 mt-0.5">
                        Ditemukan <strong>{{ number_format($totalFinal, 0, ',', '.') }}</strong> baris pengawasan yang terindeks pelanggaran ini. Kolom <strong>HASIL EVALUASI</strong> dan <strong>RINCIAN TMK</strong> di bawah disorot secara otomatis.
                    </p>
                </div>
            </div>
            <a
                href="{{ route('data-tersimpan.index', array_merge(request()->except(['pelanggaran', 'hasil_evaluasi', 'page']))) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-rose-300 bg-white text-rose-800 hover:bg-rose-100 text-xs font-bold shrink-0 transition-colors shadow-2xs"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Reset Filter Pelanggaran</span>
            </a>
        </div>
    @endif

    <!-- Active Dimension Filter Banner -->
    @if (!empty($activeDimensionInfo))
        <div class="p-4 rounded-xl {{ ($filters['hasil_evaluasi'] ?? null) == 2 ? 'bg-rose-50 border-rose-200' : (($filters['hasil_evaluasi'] ?? null) == 1 ? 'bg-emerald-50 border-emerald-200' : 'bg-blue-50 border-blue-200') }} border flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg {{ ($filters['hasil_evaluasi'] ?? null) == 2 ? 'bg-rose-500' : (($filters['hasil_evaluasi'] ?? null) == 1 ? 'bg-emerald-500' : 'bg-blue-600') }} text-white flex items-center justify-center font-black text-base shrink-0 shadow-xs">
                    {{ ($filters['hasil_evaluasi'] ?? null) == 2 ? '!' : (($filters['hasil_evaluasi'] ?? null) == 1 ? '✓' : '📊') }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-xs sm:text-sm font-extrabold {{ ($filters['hasil_evaluasi'] ?? null) == 2 ? 'text-rose-950' : (($filters['hasil_evaluasi'] ?? null) == 1 ? 'text-emerald-950' : 'text-blue-950') }}">
                            Menampilkan Data Terfilter: <span class="underline decoration-2">{{ $activeDimensionInfo['name'] }}: {{ $activeDimensionInfo['value_name'] }}</span>
                            @if (($filters['hasil_evaluasi'] ?? null) == 2)
                                <span class="text-rose-700 font-bold">(Khusus Tidak Memenuhi Ketentuan / TMK)</span>
                            @elseif (($filters['hasil_evaluasi'] ?? null) == 1)
                                <span class="text-emerald-700 font-bold">(Khusus Memenuhi Ketentuan / MK)</span>
                            @endif
                        </h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($filters['hasil_evaluasi'] ?? null) == 2 ? 'bg-rose-200 text-rose-900' : (($filters['hasil_evaluasi'] ?? null) == 1 ? 'bg-emerald-200 text-emerald-900' : 'bg-blue-200 text-blue-900') }}">
                            Filter Visual Aktif
                        </span>
                    </div>
                    <p class="text-[11px] {{ ($filters['hasil_evaluasi'] ?? null) == 2 ? 'text-rose-700' : (($filters['hasil_evaluasi'] ?? null) == 1 ? 'text-emerald-700' : 'text-blue-700') }} mt-0.5">
                        Ditemukan <strong>{{ number_format($totalFinal, 0, ',', '.') }}</strong> baris data final. Kolom <strong>{{ strtoupper($activeDimensionInfo['name']) }}</strong> dan <strong>HASIL EVALUASI</strong> disorot otomatis di tabel.
                    </p>
                </div>
            </div>
            <a
                href="{{ route('data-tersimpan.index', array_merge(request()->except([$activeDimensionInfo['key'], 'hasil_evaluasi', 'highlight_col', 'page']))) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border {{ ($filters['hasil_evaluasi'] ?? null) == 2 ? 'border-rose-300 text-rose-800 hover:bg-rose-100' : (($filters['hasil_evaluasi'] ?? null) == 1 ? 'border-emerald-300 text-emerald-800 hover:bg-emerald-100' : 'border-blue-300 text-blue-800 hover:bg-blue-100') }} bg-white text-xs font-bold shrink-0 transition-colors shadow-2xs"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Reset Filter Dimensi</span>
            </a>
        </div>
    @endif

    <!-- Table of Finalized Standardized Data -->
    <div class="bg-white rounded-xl border border-(--border) shadow-xs overflow-hidden">
        <div class="overflow-x-auto max-h-[75vh]" id="tableContainer">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-center whitespace-nowrap">
                <thead class="sticky top-0 z-20 font-bold uppercase tracking-wider text-[11px] shadow-xs">
                    <tr class="bg-[#FFF59D] text-gray-800">
                        <th class="px-2.5 py-3 border-r border-gray-300 bg-[#FFF59D] sticky left-0 z-30 w-12">NO</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-14">UPT</th>
                        <th class="px-2.5 py-3 border-r border-gray-300 w-16">PERIODE BULAN</th>
                        <th class="px-3 py-3 border-r border-gray-300 bg-[#FCE4EC] text-pink-950 w-16">JENIS PRODUK</th>
                        <th id="th-col-asal_produk" class="px-3 py-3 border-r border-gray-300 {{ ($highlightCol ?? '') === 'asal_produk' ? 'bg-blue-200 text-blue-950 ring-2 ring-inset ring-blue-500 font-black' : '' }} w-16">
                            ASAL PRODUK
                        </th>
                        <th class="px-3 py-3 border-r border-gray-300 bg-[#FCE4EC] text-pink-950 w-16">KLAIM</th>
                        <th id="th-col-kategori_media" class="px-3 py-3 border-r border-gray-300 {{ ($highlightCol ?? '') === 'kategori_media' ? 'bg-blue-200 text-blue-950 ring-2 ring-inset ring-blue-500 font-black' : '' }} w-16">
                            KATEGORI MEDIA
                        </th>
                        <th id="th-col-kategori_iklan" class="px-3 py-3 border-r border-gray-300 {{ ($highlightCol ?? '') === 'kategori_iklan' ? 'bg-blue-200 text-blue-950 ring-2 ring-inset ring-blue-500 font-black' : 'bg-[#E8F5E9] text-emerald-950' }} w-16">
                            KATEGORI IKLAN
                        </th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">NAMA MEDIA</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">PENCANTUMAN NIE</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">KESESUAIAN NIE</th>
                        <th id="th-col-hasil_evaluasi" class="px-3 py-3 border-r border-gray-300 {{ !empty($filters['pelanggaran']) || (($filters['hasil_evaluasi'] ?? null) == 2) ? 'bg-rose-200 text-rose-950 ring-2 ring-inset ring-rose-400 font-black' : ((($filters['hasil_evaluasi'] ?? null) == 1) ? 'bg-emerald-200 text-emerald-950 ring-2 ring-inset ring-emerald-400 font-black' : 'bg-[#E0F7FA] text-cyan-950') }} w-16">
                            HASIL EVALUASI
                        </th>
                        <th class="px-3 py-3 border-r border-gray-300 {{ !empty($filters['pelanggaran']) ? 'bg-rose-300 text-rose-950 ring-2 ring-inset ring-rose-500 font-black' : '' }} w-16">
                            RINCIAN TMK
                        </th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">JENIS TMK</th>
                        <th id="th-col-pembuat_iklan" class="px-3 py-3 border-r border-gray-300 {{ ($highlightCol ?? '') === 'pembuat_iklan' ? 'bg-blue-200 text-blue-950 ring-2 ring-inset ring-blue-500 font-black' : '' }} w-16">
                            PEMBUAT IKLAN
                        </th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">KESESUAIAN EVALUASI</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">KUALITAS LAPORAN</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">KETEPATAN WAKTU</th>
                        <!-- 19. EVALUATOR -->
                        <th class="px-3 py-3 border-r border-gray-300 bg-blue-100 text-blue-950 w-28">EVALUATOR</th>
                        <!-- 20. WAKTU -->
                        <th class="px-3 py-3 bg-indigo-100 text-indigo-950 min-w-44 text-center">WAKTU</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white font-medium">
                    @forelse ($items as $item)
                        <tr class="hover:bg-blue-50/50 transition-colors {{ !empty($filters['pelanggaran']) || (($filters['hasil_evaluasi'] ?? null) == 2) ? 'bg-rose-50/30' : '' }}">
                            <td class="px-2.5 py-2 font-bold text-gray-700 bg-gray-50 border-r border-gray-200 sticky left-0 z-10 shadow-2xs" title="Baris SIPT: {{ $item->dataMentah?->nomor_baris ?? $item->no }}">
                                {{ $items->firstItem() + $loop->index }}
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['upt'][$item->upt] ?? '' }}">{{ $item->upt }}</td>
                            <td class="px-2.5 py-2 border-r border-gray-100">{{ $item->periode_bulan }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 bg-pink-50/20" title="{{ $masterDict['jenis_produk'][$item->jenis_produk] ?? '' }}">{{ $item->jenis_produk }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 {{ ($highlightCol ?? '') === 'asal_produk' ? 'bg-blue-100/90 font-extrabold text-blue-950 ring-1 ring-inset ring-blue-400' : '' }}" title="{{ $masterDict['asal_produk'][$item->asal_produk] ?? '' }}">
                                @if (($highlightCol ?? '') === 'asal_produk')
                                    <span class="px-1.5 py-0.5 rounded bg-blue-200 text-blue-900 font-black">
                                        {{ $item->asal_produk }} ({{ $masterDict['asal_produk'][$item->asal_produk] ?? '' }})
                                    </span>
                                @else
                                    {{ $item->asal_produk }}
                                @endif
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 bg-pink-50/20" title="{{ $masterDict['klaim'][$item->klaim] ?? '' }}">{{ $item->klaim }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 {{ ($highlightCol ?? '') === 'kategori_media' ? 'bg-blue-100/90 font-extrabold text-blue-950 ring-1 ring-inset ring-blue-400' : '' }}" title="{{ $masterDict['kategori_media'][$item->kategori_media] ?? '' }}">
                                @if (($highlightCol ?? '') === 'kategori_media')
                                    <span class="px-1.5 py-0.5 rounded bg-blue-200 text-blue-900 font-black">
                                        {{ $item->kategori_media }} ({{ $masterDict['kategori_media'][$item->kategori_media] ?? '' }})
                                    </span>
                                @else
                                    {{ $item->kategori_media }}
                                @endif
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 {{ ($highlightCol ?? '') === 'kategori_iklan' ? 'bg-blue-100/90 font-extrabold text-blue-950 ring-1 ring-inset ring-blue-400' : 'bg-emerald-50/20' }}" title="{{ $masterDict['kategori_iklan'][$item->kategori_iklan] ?? '' }}">
                                @if (($highlightCol ?? '') === 'kategori_iklan')
                                    <span class="px-1.5 py-0.5 rounded bg-blue-200 text-blue-900 font-black">
                                        {{ $item->kategori_iklan }} ({{ $masterDict['kategori_iklan'][$item->kategori_iklan] ?? '' }})
                                    </span>
                                @else
                                    {{ $item->kategori_iklan }}
                                @endif
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['nama_media'][$item->nama_media] ?? '' }}">{{ $item->nama_media ?? '-' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['pencantuman_nie'][$item->pencantuman_nie] ?? '' }}">{{ $item->pencantuman_nie ?? '-' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['kesesuaian_nie'][$item->kesesuaian_nie] ?? '' }}">{{ $item->kesesuaian_nie ?? '-' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 {{ !empty($filters['pelanggaran']) || (($filters['hasil_evaluasi'] ?? null) == 2) ? 'bg-rose-100 font-extrabold text-rose-900' : ((($filters['hasil_evaluasi'] ?? null) == 1) ? 'bg-emerald-100 font-extrabold text-emerald-900' : 'bg-cyan-50/20 font-bold') }}" title="{{ $masterDict['hasil_evaluasi'][$item->hasil_evaluasi] ?? '' }}">
                                @if (($filters['hasil_evaluasi'] ?? null) == 2 || !empty($filters['pelanggaran']))
                                    <span class="px-1.5 py-0.5 rounded bg-rose-200 text-rose-900 text-[11px] font-black inline-block">
                                        {{ $item->hasil_evaluasi }} (TMK)
                                    </span>
                                @elseif (($filters['hasil_evaluasi'] ?? null) == 1)
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-900 text-[11px] font-black inline-block">
                                        {{ $item->hasil_evaluasi }} (MK)
                                    </span>
                                @else
                                    {{ $item->hasil_evaluasi }}
                                @endif
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 {{ !empty($filters['pelanggaran']) ? 'bg-rose-100/90 font-extrabold text-rose-950 ring-1 ring-inset ring-rose-300' : '' }}" title="{{ $masterDict['rincian_tmk'][$item->rincian_tmk] ?? '' }}">
                                @if (!empty($filters['pelanggaran']))
                                    <span class="px-2 py-0.5 rounded bg-rose-600 text-white text-[11px] font-black inline-block shadow-2xs">
                                        {{ $item->rincian_tmk ?? '' }} ({{ $masterDict['rincian_tmk'][$item->rincian_tmk] ?? 'TMK' }})
                                    </span>
                                @else
                                    {{ $item->rincian_tmk ?? '' }}
                                @endif
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['jenis_tmk'][$item->jenis_tmk] ?? '' }}">{{ $item->jenis_tmk ?? '' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 {{ ($highlightCol ?? '') === 'pembuat_iklan' ? 'bg-blue-100/90 font-extrabold text-blue-950 ring-1 ring-inset ring-blue-400' : '' }}" title="{{ $masterDict['pembuat_iklan'][$item->pembuat_iklan] ?? '' }}">
                                @if (($highlightCol ?? '') === 'pembuat_iklan')
                                    <span class="px-1.5 py-0.5 rounded bg-blue-200 text-blue-900 font-black">
                                        {{ $item->pembuat_iklan }} ({{ $masterDict['pembuat_iklan'][$item->pembuat_iklan] ?? '' }})
                                    </span>
                                @else
                                    {{ $item->pembuat_iklan }}
                                @endif
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100 font-bold text-gray-900" title="{{ $masterDict['kesesuaian_evaluasi'][$item->kesesuaian_evaluasi] ?? '' }}">{{ $item->kesesuaian_evaluasi }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 font-bold text-gray-900" title="{{ $masterDict['kualitas_laporan'][$item->kualitas_laporan] ?? '' }}">{{ $item->kualitas_laporan }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 font-bold text-gray-900" title="{{ $masterDict['ketepatan_waktu'][$item->ketepatan_waktu] ?? '' }}">{{ $item->ketepatan_waktu }}</td>
                            <!-- 19. EVALUATOR -->
                            <td class="px-3 py-2 bg-blue-50/30 text-gray-800 text-[11px] font-semibold border-r border-gray-200">
                                {{ $item->evaluator?->name ?? ($item->dataMentah?->evaluator?->name ?? ($item->dataset?->uploader?->name ?? '-')) }}
                            </td>
                            @php
                                $waktuProses = $item->updated_at ?? $item->created_at ?? $item->dataset?->finalized_at ?? ($item->evaluator?->last_login_at ?? auth()->user()?->last_login_at ?? now());
                            @endphp
                            <!-- 20. WAKTU -->
                            <td class="px-3 py-2 text-center text-xs text-gray-800 bg-indigo-50/10 font-medium">
                                <span class="font-bold text-gray-900 block">{{ $waktuProses->translatedFormat('d F Y') }}</span>
                                <span class="block text-[10px] text-gray-600 font-medium mt-0.5">{{ $waktuProses->format('H:i:s') }} WIB</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="px-4 py-12 text-center text-gray-400">
                                <x-empty-state
                                    title="Belum Ada Data Tersimpan Final"
                                    description="Data yang telah distandardisasi dan difinalkan akan tersimpan secara permanen di sini."
                                />
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

    @if (!empty($highlightCol))
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    const el = document.getElementById('th-col-{{ $highlightCol }}');
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    }
                }, 200);
            });
        </script>
        @endpush
    @endif
</x-layouts.app>
