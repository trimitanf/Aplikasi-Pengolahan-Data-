<x-layouts.app title="Data Mentah SIPT (26 Kolom) - {{ $dataset->nama_file_asli }}">
    <x-slot:header>
        Data Mentah SIPT: {{ $dataset->nama_komoditi }} {{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }}
    </x-slot:header>
    <x-slot:breadcrumb>
        <a href="{{ route('dataset.index') }}" class="hover:underline">Dataset</a> /
        <a href="{{ route('dataset.show', $dataset) }}" class="hover:underline">Detail</a> /
        Data Mentah
    </x-slot:breadcrumb>

    <!-- Stepper Navigation -->
    <x-stepper :activeStep="3" :datasetId="$dataset->id" />

    <!-- Brief Notification: Unstandardized Master Codes (misal: UPT) -->
    <x-unstandardized-code-alert :warnings="$unstandardizedWarnings ?? []" />

    <!-- Alpine.js root scope: wrapText state shared between header & table -->
    <div x-data="{ wrapText: true }">

    <!-- Header Actions & Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-(--border) shadow-xs">
        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ route('dataset.show', $dataset) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50"
            >
                &larr; Kembali ke Detail
            </a>

            <div class="inline-flex items-center gap-2 text-xs">
                <span class="text-gray-500 font-medium">Evaluator:</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-semibold bg-blue-100 text-blue-800">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ $dataset->uploader?->name ?? 'Belum Ditentukan' }}</span>
                </span>
            </div>

            <div class="text-xs text-gray-600">
                Menampilkan <span class="font-bold text-gray-900">{{ number_format($rows->total(), 0, ',', '.') }}</span> baris
            </div>

            <!-- Toggle Wrap Text -->
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

        <!-- Search Form -->
        <form method="GET" action="{{ route('dataset.data-mentah', $dataset) }}" class="flex flex-wrap items-center gap-2">
            @foreach (request()->except(['cari', 'upt', 'page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <div class="w-56">
                <select
                    name="upt"
                    class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 focus:border-blue-600 focus:outline-none bg-white cursor-pointer"
                    onchange="this.form.submit()"
                >
                    <option value="">Semua UPT</option>
                    @foreach ($uptList as $upt)
                        <option value="{{ $upt->kode }}" @selected(($filterUpt ?? '') == $upt->kode)>
                            [{{ $upt->kode }}] {{ $upt->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="relative w-64">
                <input
                    type="text"
                    name="cari"
                    value="{{ $cari }}"
                    placeholder="Cari produk, NIE, media..."
                    class="w-full rounded-lg border border-gray-300 pl-8 pr-3 py-1.5 text-xs text-gray-900 focus:border-blue-600 focus:outline-none"
                />
                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <x-button type="submit" variant="primary" size="sm">
                Cari
            </x-button>
            @if ($cari || !empty($filterUpt))
                <a
                    href="{{ route('dataset.data-mentah', $dataset) }}"
                    class="px-2.5 py-1.5 rounded-lg border border-gray-300 text-xs text-gray-600 hover:bg-gray-50"
                >
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- 26 Columns Data Table Container -->
    <div class="bg-white rounded-xl border border-(--border) shadow-xs overflow-hidden">
        <div class="overflow-x-auto max-h-[75vh]">
            <table class="min-w-full divide-y divide-gray-200 text-xs" :class="wrapText ? '' : 'whitespace-nowrap'">
                <thead class="bg-slate-100 text-gray-800 font-bold uppercase tracking-wider sticky top-0 z-10 shadow-xs border-b border-gray-300">
                    <tr>
                        <th class="px-3 py-3 text-center border-r border-gray-200 bg-slate-200/90 w-14 sticky left-0 z-20">NO</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-50">UPT</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-30">TGL PENGAMBILAN</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-30">TGL KIRIM KA UPT</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-30">TGL EVALUASI PUSAT</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-35">JENIS PRODUK</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-50">NAMA PRODUK</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-37.5">NOMOR IZIN EDAR</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-56">KHASIAT / INDIKASI</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-45">PENDAFTAR</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-30">ASAL PRODUK</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-32.5">KATEGORI MEDIA</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-35">MEDIA IKLAN</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-50">TEMPAT PENGAMBILAN IKLAN</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-40">PENCANTUMAN NIE</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-56">LINK URL IKLAN</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-64">KLAIM PROMOSI/IKLAN</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-64">KLASIFIKASI KLAIM</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-35">PEMBUAT IKLAN</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-44">HASIL PENILAIAN UPT</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-40">KESIMPULAN UPT</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-40">KESIMPULAN PUSAT</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-45">KESESUAIAN HASIL EVALUASI</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-40">KATEGORI TMK</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-48">RINCIAN TMK</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-48">TINDAK LANJUT</th>
                        <!-- PENUGASAN EVALUATOR (SKEMA B) -->
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36 bg-blue-50/80 text-blue-900 font-bold">
                            EVALUATOR
                        </th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 min-w-36 bg-blue-50/80 text-blue-900 font-bold">KUALITAS LAPORAN</th>
                        <th class="px-3 py-3 text-center min-w-36 bg-blue-50/80 text-blue-900 font-bold">KETEPATAN WAKTU</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($rows as $row)
                        @php
                            $isMine = auth()->check() && (int) auth()->id() === (int) $row->evaluator_id;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-3 py-2.5 text-center font-bold text-gray-700 bg-slate-50 border-r border-gray-200 sticky left-0 z-10 align-top whitespace-nowrap">
                                {{ $row->no !== null && $row->no !== '' ? $row->no : $row->nomor_baris }}
                            </td>
                            <td class="px-3 py-2.5 font-medium text-gray-900 border-r border-gray-100 align-top min-w-50" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->upt }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->tanggal_pengambilan_iklan }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->tgl_kirim_ka_upt_ke_pusat }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->tanggal_evaluasi_pusat }}</td>
                            <td class="px-3 py-2.5 font-medium text-gray-800 border-r border-gray-100 align-top min-w-35" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->jenis_produk }}</td>
                            <td class="px-3 py-2.5 text-gray-800 border-r border-gray-100 align-top min-w-50 max-w-xs" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->nama_produk }}">{{ $row->nama_produk }}</td>
                            <td class="px-3 py-2.5 font-mono text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->nomor_izin_edar }}</td>
                            <td class="px-3 py-2.5 text-gray-600 border-r border-gray-100 align-top min-w-56 max-w-sm" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->khasiat_indikasi }}">{{ $row->khasiat_indikasi }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-45 max-w-xs" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->pendaftar }}">{{ $row->pendaftar }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap text-center">{{ $row->asal_produk }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top whitespace-nowrap">{{ $row->kategori_media }}</td>
                            <td class="px-3 py-2.5 font-medium text-gray-800 border-r border-gray-100 align-top min-w-35" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->media_iklan }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-50 max-w-xs" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->tempat_pengambilan_iklan }}">{{ $row->tempat_pengambilan_iklan }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-40" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->pencantuman_nomor_izin_edar }}</td>
                            <td class="px-3 py-2.5 text-blue-600 border-r border-gray-100 align-top font-mono min-w-56 max-w-sm" :class="wrapText ? 'whitespace-normal break-all' : 'truncate'" title="{{ $row->link_url_iklan }}" style="word-break: break-all; overflow-wrap: anywhere;">
                                @if ($row->link_url_iklan)
                                    <a href="{{ $row->link_url_iklan }}" target="_blank" rel="noopener noreferrer" class="hover:underline break-all block" :class="wrapText ? '' : 'truncate'" style="word-break: break-all; overflow-wrap: anywhere;">
                                        {{ $row->link_url_iklan }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-gray-600 border-r border-gray-100 align-top min-w-64 max-w-md" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->klaim_dalam_promosi_iklan }}">{{ $row->klaim_dalam_promosi_iklan }}</td>
                            <td class="px-3 py-2.5 text-gray-800 border-r border-gray-100 align-top min-w-64 max-w-md" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->klasifikasi_klaim }}">{{ $row->klasifikasi_klaim }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-35" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->pembuat_iklan }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-44 max-w-xs" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'">{{ $row->hasil_penilaian_iklan_upt }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-40 font-semibold" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'" title="{{ $row->kesimpulan_upt }}">{{ $row->kesimpulan_upt }}</td>
                            <td class="px-3 py-2.5 font-semibold text-gray-900 border-r border-gray-100 align-top min-w-40" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->kesimpulan_pusat }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-45" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->kesesuaian_hasil_evaluasi }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-40" :class="wrapText ? 'whitespace-normal break-words' : 'truncate'">{{ $row->kategori_tmk }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-48 max-w-xs" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->rincian_tmk }}">{{ $row->rincian_tmk }}</td>
                            <td class="px-3 py-2.5 text-gray-700 border-r border-gray-100 align-top min-w-48 max-w-xs" :class="wrapText ? 'whitespace-pre-line break-words' : 'truncate'" title="{{ $row->tindak_lanjut }}">{{ $row->tindak_lanjut }}</td>

                            <!-- PENUGASAN EVALUATOR (SKEMA B) -->
                            <td class="px-3 py-2.5 text-center border-r border-gray-100 bg-blue-50/20 align-top min-w-36">
                                @if ($row->evaluator)
                                    <span @class([
                                        'inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold',
                                        'bg-blue-600 text-white font-bold shadow-2xs' => $isMine,
                                        'bg-slate-200 text-slate-700' => ! $isMine,
                                    ])>
                                        {{ $row->evaluator->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-[10px] italic">-</span>
                                @endif
                            </td>

                            <td class="px-3 py-2.5 text-center border-r border-gray-100 font-bold align-top">{{ $row->kualitas_laporan ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-center font-bold align-top">{{ $row->ketepatan_waktu ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="30" class="px-4 py-12 text-center text-gray-400">
                                @if ($cari)
                                    Tidak ditemukan baris yang cocok dengan kata kunci pencarian "{{ $cari }}".
                                @else
                                    Belum ada data mentah yang tersimpan.
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

    </div>{{-- /x-data wrapText --}}
</x-layouts.app>
