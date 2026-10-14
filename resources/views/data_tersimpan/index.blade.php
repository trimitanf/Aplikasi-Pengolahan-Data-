<x-layouts.app title="Data Tersimpan (Final)">
    <x-slot:header>
        Data Tersimpan (Standardisasi Final)
    </x-slot:header>
    <x-slot:breadcrumb>
        Data Tersimpan
    </x-slot:breadcrumb>

    <!-- Filters & Export Toolbar -->
    <x-card>
        <form method="GET" action="{{ route('data-tersimpan.index') }}" class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 flex-1">
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
                        <option value="OT" @selected($filters['komoditi'] === 'OT')>Obat Tradisional (OT)</option>
                        <option value="SK" @selected($filters['komoditi'] === 'SK')>Suplemen Kesehatan (SK)</option>
                        <option value="OK" @selected($filters['komoditi'] === 'OK')>Obat Kuasi (OK)</option>
                    </select>
                </div>
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

    <!-- Table of Finalized Standardized Data -->
    <div class="bg-white rounded-xl border border-(--border) shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-700">
                Total Data Final Terkunci: <span class="font-bold text-gray-900">{{ number_format($totalFinal, 0, ',', '.') }}</span> baris
            </span>
            <span class="text-xs text-gray-400">18 Kolom Kode Numerik Standar</span>
        </div>

        <div class="overflow-x-auto max-h-[75vh]">
            <table class="min-w-full divide-y divide-gray-200 text-xs text-center whitespace-nowrap">
                <thead class="sticky top-0 z-20 font-bold uppercase tracking-wider text-[11px] shadow-xs">
                    <tr class="bg-[#FFF59D] text-gray-800">
                        <th class="px-2.5 py-3 border-r border-gray-300 bg-[#FFF59D] sticky left-0 z-30 w-12">NO</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-14">UPT</th>
                        <th class="px-2.5 py-3 border-r border-gray-300 w-16">PERIODE BULAN</th>
                        <th class="px-3 py-3 border-r border-gray-300 bg-[#FCE4EC] text-pink-950 w-16">JENIS PRODUK</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">ASAL PRODUK</th>
                        <th class="px-3 py-3 border-r border-gray-300 bg-[#FCE4EC] text-pink-950 w-16">KLAIM</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">KATEGORI MEDIA</th>
                        <th class="px-3 py-3 border-r border-gray-300 bg-[#E8F5E9] text-emerald-950 w-16">KATEGORI IKLAN</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">NAMA MEDIA</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">PENCANTUMAN NIE</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">KESESUAIAN NIE</th>
                        <th class="px-3 py-3 border-r border-gray-300 bg-[#E0F7FA] text-cyan-950 w-16">HASIL EVALUASI</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">RINCIAN TMK</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">JENIS TMK</th>
                        <th class="px-3 py-3 border-r border-gray-300 w-16">PEMBUAT IKLAN</th>
                        <th class="px-3 py-3 border-r border-red-300 bg-[#FFCDD2] text-red-900 w-16">KESESUAIAN EVALUASI</th>
                        <th class="px-3 py-3 border-r border-red-300 bg-[#FFCDD2] text-red-900 w-16">KUALITAS LAPORAN</th>
                        <th class="px-3 py-3 bg-[#FFCDD2] text-red-900 w-16">KETEPATAN WAKTU</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white font-medium">
                    @forelse ($items as $item)
                        <tr class="hover:bg-blue-50/50 transition-colors">
                            <td class="px-2.5 py-2 font-bold text-gray-700 bg-gray-50 border-r border-gray-200 sticky left-0 z-10 shadow-2xs">
                                {{ $item->no }}
                            </td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['upt'][$item->upt] ?? '' }}">{{ $item->upt }}</td>
                            <td class="px-2.5 py-2 border-r border-gray-100">{{ $item->periode_bulan }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 bg-pink-50/20" title="{{ $masterDict['jenis_produk'][$item->jenis_produk] ?? '' }}">{{ $item->jenis_produk }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['asal_produk'][$item->asal_produk] ?? '' }}">{{ $item->asal_produk }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 bg-pink-50/20" title="{{ $masterDict['klaim'][$item->klaim] ?? '' }}">{{ $item->klaim }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['kategori_media'][$item->kategori_media] ?? '' }}">{{ $item->kategori_media }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 bg-emerald-50/20" title="{{ $masterDict['kategori_iklan'][$item->kategori_iklan] ?? '' }}">{{ $item->kategori_iklan }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['nama_media'][$item->nama_media] ?? '' }}">{{ $item->nama_media ?? '-' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['pencantuman_nie'][$item->pencantuman_nie] ?? '' }}">{{ $item->pencantuman_nie ?? '-' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['kesesuaian_nie'][$item->kesesuaian_nie] ?? '' }}">{{ $item->kesesuaian_nie ?? '-' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100 bg-cyan-50/20 font-bold" title="{{ $masterDict['hasil_evaluasi'][$item->hasil_evaluasi] ?? '' }}">{{ $item->hasil_evaluasi }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['rincian_tmk'][$item->rincian_tmk] ?? '' }}">{{ $item->rincian_tmk ?? '' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['jenis_tmk'][$item->jenis_tmk] ?? '' }}">{{ $item->jenis_tmk ?? '' }}</td>
                            <td class="px-3 py-2 border-r border-gray-100" title="{{ $masterDict['pembuat_iklan'][$item->pembuat_iklan] ?? '' }}">{{ $item->pembuat_iklan }}</td>
                            <td class="px-3 py-2 border-r border-red-200 bg-red-50/20 font-bold text-gray-900" title="{{ $masterDict['kesesuaian_evaluasi'][$item->kesesuaian_evaluasi] ?? '' }}">{{ $item->kesesuaian_evaluasi }}</td>
                            <td class="px-3 py-2 border-r border-red-200 bg-red-50/20 font-bold text-gray-900" title="{{ $masterDict['kualitas_laporan'][$item->kualitas_laporan] ?? '' }}">{{ $item->kualitas_laporan }}</td>
                            <td class="px-3 py-2 bg-red-50/20 font-bold text-gray-900" title="{{ $masterDict['ketepatan_waktu'][$item->ketepatan_waktu] ?? '' }}">{{ $item->ketepatan_waktu }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" class="px-4 py-12 text-center text-gray-400">
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
</x-layouts.app>
