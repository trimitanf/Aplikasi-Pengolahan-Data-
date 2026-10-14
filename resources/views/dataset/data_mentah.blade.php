<x-layouts.app title="Data Mentah SIPT (26 Kolom) - {{ $dataset->nama_file_asli }}">
    <x-slot:header>
        Data Mentah SIPT: {{ $dataset->nama_komoditi }} {{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }}
    </x-slot:header>
    <x-slot:breadcrumb>
        <a href="{{ route('dataset.index') }}" class="hover:underline">Dataset</a> /
        <a href="{{ route('dataset.show', $dataset) }}" class="hover:underline">Detail</a> /
        Data Mentah
    </x-slot:breadcrumb>

    <!-- Header Actions & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-4 rounded-xl border border-[var(--border)] shadow-xs">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('dataset.show', $dataset) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50"
            >
                &larr; Kembali ke Detail
            </a>
            <div class="text-xs text-gray-600">
                Menampilkan <span class="font-bold text-gray-900">{{ number_format($rows->total(), 0, ',', '.') }}</span> baris data mentah
            </div>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('dataset.data-mentah', $dataset) }}" class="flex items-center gap-2">
            <div class="relative w-72">
                <input
                    type="text"
                    name="cari"
                    value="{{ $cari }}"
                    placeholder="Cari UPT, produk, NIE, media..."
                    class="w-full rounded-lg border border-gray-300 pl-8 pr-3 py-1.5 text-xs text-gray-900 focus:border-blue-600 focus:outline-none"
                />
                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <x-button type="submit" variant="primary" size="sm">
                Cari
            </x-button>
            @if ($cari)
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
    <div class="bg-white rounded-xl border border-[var(--border)] shadow-xs overflow-hidden">
        <div class="overflow-x-auto max-h-[75vh]">
            <table class="min-w-full divide-y divide-gray-200 text-xs whitespace-nowrap">
                <thead class="bg-slate-100 text-gray-700 font-bold uppercase tracking-wider sticky top-0 z-10 shadow-xs">
                    <tr>
                        <th class="px-3 py-3 text-center border-r border-gray-200 bg-slate-200/90 w-12 sticky left-0 z-20">Baris</th>
                        <th class="px-3 py-3 text-center border-r border-gray-200 w-14">NO SIPT</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[200px]">UPT</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[120px]">TGL PENGAMBILAN</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[120px]">TGL KIRIM KA UPT</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[120px]">TGL EVALUASI PUSAT</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[140px]">JENIS PRODUK</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[200px]">NAMA PRODUK</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[150px]">NOMOR IZIN EDAR</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[200px]">KHASIAT / INDIKASI</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[180px]">PENDAFTAR</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[120px]">ASAL PRODUK</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[130px]">KATEGORI MEDIA</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[140px]">MEDIA IKLAN</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[200px]">TEMPAT PENGAMBILAN IKLAN</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[160px]">PENCANTUMAN NIE</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[200px]">LINK URL IKLAN</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[240px]">KLAIM PROMOSI</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[200px]">KLASIFIKASI KLAIM</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[140px]">PEMBUAT IKLAN</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[160px]">HASIL PENILAIAN UPT</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[160px]">KESIMPULAN UPT</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[160px]">KESIMPULAN PUSAT</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[180px]">KESESUAIAN HASIL EVALUASI</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[160px]">KATEGORI TMK</th>
                        <th class="px-3 py-3 text-left border-r border-gray-200 min-w-[180px]">RINCIAN TMK</th>
                        <th class="px-3 py-3 text-left min-w-[180px]">TINDAK LANJUT</th>
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
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->tanggal_pengambilan_iklan }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->tgl_kirim_ka_upt_ke_pusat }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->tanggal_evaluasi_pusat }}</td>
                            <td class="px-3 py-2 font-medium text-gray-800 border-r border-gray-100">{{ $row->jenis_produk }}</td>
                            <td class="px-3 py-2 text-gray-800 border-r border-gray-100 truncate max-w-xs" title="{{ $row->nama_produk }}">{{ $row->nama_produk }}</td>
                            <td class="px-3 py-2 font-mono text-gray-700 border-r border-gray-100">{{ $row->nomor_izin_edar }}</td>
                            <td class="px-3 py-2 text-gray-600 border-r border-gray-100 truncate max-w-xs" title="{{ $row->khasiat_indikasi }}">{{ $row->khasiat_indikasi }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100 truncate max-w-xs" title="{{ $row->pendaftar }}">{{ $row->pendaftar }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->asal_produk }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->kategori_media }}</td>
                            <td class="px-3 py-2 font-medium text-gray-800 border-r border-gray-100">{{ $row->media_iklan }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100 truncate max-w-xs" title="{{ $row->tempat_pengambilan_iklan }}">{{ $row->tempat_pengambilan_iklan }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->pencantuman_nomor_izin_edar }}</td>
                            <td class="px-3 py-2 text-blue-600 border-r border-gray-100 truncate max-w-xs font-mono">
                                @if ($row->link_url_iklan)
                                    <a href="{{ $row->link_url_iklan }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                        {{ $row->link_url_iklan }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600 border-r border-gray-100 truncate max-w-xs" title="{{ $row->klaim_dalam_promosi_iklan }}">{{ $row->klaim_dalam_promosi_iklan }}</td>
                            <td class="px-3 py-2 text-gray-800 border-r border-gray-100 truncate max-w-xs" title="{{ $row->klasifikasi_klaim }}">{{ $row->klasifikasi_klaim }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->pembuat_iklan }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100">{{ $row->hasil_penilaian_iklan_upt }}</td>
                            <td class="px-3 py-2 text-gray-700 border-r border-gray-100 truncate max-w-xs" title="{{ $row->kesimpulan_upt }}">{{ $row->kesimpulan_upt }}</td>
                            <td class="px-3 py-2 font-medium text-gray-900 border-r border-gray-100">{{ $row->kesimpulan_pusat }}</td>
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
</x-layouts.app>
