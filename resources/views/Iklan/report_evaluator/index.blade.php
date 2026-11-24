<x-layouts.app title="Report Evaluator - Rekapitulasi Pengerjaan Evaluator & Pembagian UPT">
    <x-slot:header>
        Report Evaluator
    </x-slot:header>
    <x-slot:breadcrumb>
        <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a> /
        <span>Report Evaluator</span>
    </x-slot:breadcrumb>

    @php
        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $hasActiveFilter = !empty($filters['evaluator_id'])
            || !empty($filters['upt'])
            || !empty($filters['komoditi'])
            || !empty($filters['tahun'])
            || !empty($filters['bulan'])
            || !empty($filters['tanggal']);
    @endphp

    <div class="space-y-4">
        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl border border-(--border) shadow-2xs">
            <form method="GET" action="{{ route('report-evaluator.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-12 gap-3 items-end">
                <!-- 1. Filter Evaluator -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Evaluator
                    </label>
                    <select
                        name="evaluator_id"
                        class="w-full rounded-lg border border-gray-300 px-2.5 py-2 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    >
                        <option value="">Semua Evaluator</option>
                        @foreach ($evaluators as $ev)
                            <option value="{{ $ev->id }}" @selected((string) ($filters['evaluator_id'] ?? '') === (string) $ev->id)>
                                {{ $ev->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Filter Kolom UPT -->
                <div class="lg:col-span-3">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Kolom UPT (Balai / Loka POM)
                    </label>
                    <select
                        name="upt"
                        class="w-full rounded-lg border border-gray-300 px-2.5 py-2 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    >
                        <option value="">Semua UPT</option>
                        @foreach ($uptList as $uptItem)
                            <option value="{{ $uptItem->kode }}" @selected((string) ($filters['upt'] ?? '') === (string) $uptItem->kode || (string) ($filters['upt'] ?? '') === (string) $uptItem->nama)>
                                [{{ $uptItem->kode }}] {{ $uptItem->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Filter Tanggal Pengerjaan -->
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Tanggal Mengerjakan
                    </label>
                    <input
                        type="date"
                        name="tanggal"
                        value="{{ $filters['tanggal'] ?? '' }}"
                        class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    />
                </div>

                <!-- 4. Filter Komoditi -->
                <div class="lg:col-span-1">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Komoditi
                    </label>
                    <select
                        name="komoditi"
                        class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    >
                        <option value="">Semua</option>
                        <option value="OBA" @selected(in_array(($filters['komoditi'] ?? ''), ['OBA', 'OT']))>OBA</option>
                        <option value="OK" @selected(($filters['komoditi'] ?? '') === 'OK')>OK</option>
                        <option value="SK" @selected(($filters['komoditi'] ?? '') === 'SK')>SK</option>
                    </select>
                </div>

                <!-- 5. Filter Tahun & Bulan -->
                <div class="lg:col-span-1">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Tahun
                    </label>
                    <select
                        name="tahun"
                        class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    >
                        <option value="">Semua</option>
                        @foreach ($availableYears as $thn)
                            <option value="{{ $thn }}" @selected((string) ($filters['tahun'] ?? '') === (string) $thn)>{{ $thn }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Bulan
                    </label>
                    <select
                        name="bulan"
                        class="w-full rounded-lg border border-gray-300 px-2 py-2 text-xs font-medium text-gray-900 bg-white focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    >
                        <option value="">Semua</option>
                        @foreach ($namaBulanList as $blnNum => $blnLabel)
                            <option value="{{ $blnNum }}" @selected((string) ($filters['bulan'] ?? '') === (string) $blnNum)>{{ $blnLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tombol Filter & Reset -->
                <div class="lg:col-span-2 flex items-center gap-1.5">
                    <button
                        type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors cursor-pointer shadow-2xs"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Filter</span>
                    </button>
                    @if ($hasActiveFilter)
                        <a
                            href="{{ route('report-evaluator.index') }}"
                            class="px-2.5 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-colors"
                            title="Reset filter"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Rekapitulasi Pengerjaan Evaluator Berdasarkan Pembagian UPT -->
        <div class="bg-white rounded-xl border border-(--border) shadow-2xs overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-200 bg-slate-50/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h4 class="text-sm font-bold text-gray-900">
                        Rekapitulasi Pengerjaan Evaluator Berdasarkan Pembagian UPT
                    </h4>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                        Ringkasan progres evaluasi akumulasi dari seluruh komoditi, pembagian UPT yang dikerjakan, dan waktu update terakhir tiap evaluator.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <a
                        href="{{ route('report-evaluator.export', request()->query()) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors shadow-2xs"
                        title="Ekspor Tabel Rekapitulasi Pengerjaan Evaluator ke Excel (.xlsx)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-slate-100 text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-3.5 py-3 text-center border-r border-gray-200 w-12">No</th>
                            <th class="px-3.5 py-3 text-left border-r border-gray-200 min-w-44">Evaluator</th>
                            <th class="px-3.5 py-3 text-left border-r border-gray-200 min-w-80">UPT</th>
                            <th class="px-3.5 py-3 text-center border-r border-gray-200 w-28">Jumlah Baris</th>
                            <th class="px-3.5 py-3 text-center min-w-44">Terakhir Mengerjakan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($rekapGroups as $idx => $group)
                            @php
                                $uptCount = max(1, count($group['upt_list']));
                            @endphp
                            @if (empty($group['upt_list']))
                                <tr class="hover:bg-blue-50/20 transition-colors align-top border-b border-gray-200">
                                    <td class="px-3.5 py-3.5 text-center font-semibold text-gray-600 border-r border-gray-200 bg-slate-50/40 align-middle">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="px-4 py-3.5 border-r border-gray-200 align-middle bg-white">
                                        <div class="font-bold text-gray-900 text-sm">
                                            {{ $group['evaluator_name'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 border-r border-gray-200 text-gray-400 italic bg-white">
                                        Belum ada UPT dikerjakan
                                    </td>
                                    <td class="px-3.5 py-3.5 text-center border-r border-gray-200 font-bold text-gray-900 whitespace-nowrap align-middle bg-white">
                                        0 Baris
                                    </td>
                                    <td class="px-3.5 py-3.5 text-center text-gray-400 whitespace-nowrap align-middle bg-white">
                                        -
                                    </td>
                                </tr>
                            @else
                                @foreach ($group['upt_list'] as $uIdx => $uptItem)
                                    <tr @class([
                                        'hover:bg-blue-50/20 transition-colors align-top',
                                        'border-t border-gray-100' => $uIdx > 0,
                                        'border-b-2 border-gray-200' => $uIdx === $uptCount - 1,
                                    ])>
                                        @if ($uIdx === 0)
                                            <!-- No (Merged) -->
                                            <td rowspan="{{ $uptCount }}" class="px-3.5 py-3.5 text-center font-semibold text-gray-600 border-r border-gray-200 bg-slate-50/40 align-middle">
                                                {{ $idx + 1 }}
                                            </td>

                                            <!-- Evaluator (Merged) -->
                                            <td rowspan="{{ $uptCount }}" class="px-4 py-3.5 border-r border-gray-200 align-middle bg-white">
                                                <div class="font-bold text-gray-900 text-sm">
                                                    {{ $group['evaluator_name'] }}
                                                </div>
                                            </td>
                                        @endif

                                        <!-- UPT: 1 Baris 1 UPT (Berbaris ke Bawah) -->
                                        <td class="px-4 py-3 border-r border-gray-200 bg-white">
                                            <div class="flex items-center gap-2 min-w-0 min-h-6">
                                                <span class="font-bold text-gray-600 text-xs shrink-0">
                                                    {{ $uIdx + 1 }}.
                                                </span>
                                                <span class="font-bold text-gray-900 text-xs tracking-tight">
                                                    {{ $uptItem['nama'] }}
                                                </span>
                                            </div>
                                        </td>

                                        @if ($uIdx === 0)
                                            <!-- Jumlah Baris (Merged) -->
                                            <td rowspan="{{ $uptCount }}" class="px-3.5 py-3.5 text-center border-r border-gray-200 font-bold text-gray-900 whitespace-nowrap align-middle bg-white">
                                                <span class="text-sm">{{ number_format($group['total_baris'], 0, ',', '.') }}</span>
                                                <span class="block text-[10px] text-gray-500 font-normal mt-0.5">Baris Data</span>
                                            </td>

                                            <!-- Terakhir Mengerjakan (Merged) -->
                                            <td rowspan="{{ $uptCount }}" class="px-3.5 py-3.5 text-center text-gray-800 whitespace-nowrap align-middle bg-white">
                                                @if ($group['total_baris'] > 0 && $group['latest_timestamp'] > 0)
                                                    <span class="font-bold text-gray-900 block">{{ $group['hari_formatted'] }}, {{ $group['tanggal_formatted'] }}</span>
                                                    <span class="block text-[11px] text-gray-500 font-mono mt-0.5">({{ substr($group['jam_terakhir'], 0, 5) }} WIB)</span>
                                                @else
                                                    <span class="text-gray-400 font-medium">-</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-400">
                                    Belum ada data rekapitulasi pengerjaan evaluator yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
