<x-layouts.app title="Dashboard Pengawasan Iklan OTSK">
    <x-slot:header>
        Dashboard Pengawasan Iklan OTSK
    </x-slot:header>
    <x-slot:breadcrumb>
        Dashboard
    </x-slot:breadcrumb>

    @php
        $kpi = $metrics['kpi'];
        $iku = $metrics['iku'];
        $filter = $metrics['filter'];
        $formatRibuan = fn($num) => number_format((float)$num, 0, ',', '.');
        $formatPersen = fn($num) => number_format((float)$num, 2, ',', '.') . '%';
    @endphp

    <div
        x-data="dashboardAnalytics({
            monthlyData: {{ json_encode($metrics['monthly_trend']) }},
            dimensionsData: {{ json_encode($metrics['dimensions']) }},
            topTmkData: {{ json_encode($metrics['top_tmk']) }}
        })"
        class="space-y-6"
    >
        <!-- Filter & Header Actions Toolbar -->
        <x-card>
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
                    <!-- Tahun -->
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Tahun Pengawasan</label>
                        <select
                            name="tahun"
                            class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                        >
                            @foreach ($metrics['available_years'] as $y)
                                <option value="{{ $y }}" @selected($tahun == $y)>{{ $y }}</option>
                            @endforeach
                            @if (!in_array(now()->year, $metrics['available_years']))
                                <option value="{{ now()->year }}" @selected($tahun == now()->year)>{{ now()->year }}</option>
                            @endif
                        </select>
                    </div>

                    <!-- Komoditi -->
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Komoditi</label>
                        <select
                            name="komoditi"
                            class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                        >
                            <option value="SEMUA" @selected($komoditi === 'SEMUA')>Semua Komoditi (OBA & SK)</option>
                            <option value="OBA" @selected($komoditi === 'OBA')>Obat Bahan Alam (OT & OK)</option>
                            <option value="SK" @selected($komoditi === 'SK')>Suplemen Kesehatan (SK)</option>
                        </select>
                    </div>

                    <!-- UPT -->
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider">Balai / Loka POM (UPT)</label>
                        <select
                            name="upt"
                            class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-900 font-medium focus:border-blue-600 focus:outline-none"
                        >
                            <option value="">Semua UPT (Nasional)</option>
                            @foreach ($metrics['upt_list'] as $upt)
                                <option value="{{ $upt->kode }}" @selected($uptKode == $upt->kode)>
                                    [{{ $upt->kode }}] {{ $upt->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 shrink-0">
                    <x-button type="submit" variant="primary" size="sm">
                        <span>Terapkan Filter</span>
                    </x-button>

                    <button
                        type="button"
                        @click="showGuideModal = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-200 bg-blue-50/80 text-blue-700 text-xs font-semibold hover:bg-blue-100 transition-colors shadow-2xs"
                    >
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Panduan Alur</span>
                    </button>

                    <a
                        href="{{ route('rekap.index', ['tahun' => $tahun]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 text-xs font-semibold hover:bg-emerald-100 transition-colors shadow-2xs"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Lihat Rekap Excel</span>
                    </a>
                </div>
            </form>
        </x-card>

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- 1. Total Pengawasan (Dievaluasi) -->
            <div class="bg-white p-5 rounded-2xl border border-(--border) shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Total Pengawasan</span>
                    <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl lg:text-3xl font-extrabold text-gray-900">
                        {{ $formatRibuan($kpi['total_pengawasan']) }}
                    </div>
                    <p class="text-[11px] text-gray-500 mt-0.5">Iklan telah dievaluasi</p>
                </div>
            </div>

            <!-- 2. Memenuhi Ketentuan (MK) -->
            <div class="bg-white p-5 rounded-2xl border border-(--border) shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Memenuhi (MK)</span>
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl lg:text-3xl font-extrabold text-emerald-700">
                        {{ $formatRibuan($kpi['mk']) }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <x-badge variant="success" size="sm">
                            {{ $formatPersen($kpi['persen_mk']) }}
                        </x-badge>
                        <span class="text-[10px] text-gray-400">dari total</span>
                    </div>
                </div>
            </div>

            <!-- 3. Tidak Memenuhi Ketentuan (TMK) -->
            <div class="bg-white p-5 rounded-2xl border border-(--border) shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-rose-700">Tidak Memenuhi (TMK)</span>
                    <span class="p-2 rounded-xl bg-rose-50 text-rose-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl lg:text-3xl font-extrabold text-rose-700">
                        {{ $formatRibuan($kpi['tmk']) }}
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <x-badge variant="danger" size="sm">
                            {{ $formatPersen($kpi['persen_tmk']) }}
                        </x-badge>
                        <span class="text-[10px] text-gray-400">pelanggaran</span>
                    </div>
                </div>
            </div>

            <!-- 4. Menunggu Evaluasi -->
            <div class="bg-white p-5 rounded-2xl border border-(--border) shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Menunggu Evaluasi</span>
                    <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl lg:text-3xl font-extrabold text-amber-800">
                        {{ $formatRibuan($kpi['menunggu_evaluasi']) }}
                    </div>
                    <p class="text-[11px] text-amber-600 mt-0.5">Belum dinilai pusat</p>
                </div>
            </div>

            <!-- 5. Total Data Final Terkunci -->
            <div class="bg-white p-5 rounded-2xl border border-(--border) shadow-xs flex flex-col justify-between col-span-2 sm:col-span-1 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Data Final Terkunci</span>
                    <span class="p-2 rounded-xl bg-slate-100 text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl lg:text-3xl font-extrabold text-(--brand-navy)">
                        {{ $formatRibuan($kpi['total_final']) }}
                    </div>
                    <a href="{{ route('data-tersimpan.index') }}" class="text-[11px] text-blue-600 hover:underline mt-0.5 inline-block">
                        Lihat Data Tersimpan &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- IKU Progress Section (OBA & SK) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- IKU OBA Card -->
            <div class="bg-white rounded-2xl border border-(--border) p-6 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-700">Indikator Kinerja Utama (IKU)</span>
                        <h3 class="text-base font-extrabold text-gray-900">Obat Bahan Alam (OBA)</h3>
                        <p class="text-xs text-gray-500">Komoditi Obat Tradisional & Obat Kuasi</p>
                    </div>
                    @if ($iku['oba']['tercapai'])
                        <x-badge variant="success" size="sm">Target Tercapai</x-badge>
                    @else
                        <x-badge variant="warning" size="sm">Dalam Proses</x-badge>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-3 p-3.5 rounded-xl bg-slate-50 mb-4 border border-gray-100">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-gray-400">Target IKU</span>
                        <p class="text-lg font-extrabold text-gray-900">{{ number_format($iku['oba']['target'], 2, ',', '.') }}%</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-gray-400">Realisasi % MK</span>
                        <p class="text-lg font-extrabold text-blue-700">{{ $formatPersen($iku['oba']['realisasi_persen_mk']) }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-gray-400">Capaian IKU</span>
                        <p class="text-lg font-extrabold {{ $iku['oba']['tercapai'] ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $formatPersen($iku['oba']['capaian_persen']) }}
                        </p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-gray-600">Progress Capaian terhadap Target IKU</span>
                        <span class="text-gray-900">{{ number_format(min($iku['oba']['capaian_persen'], 100), 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div
                            class="h-3 rounded-full transition-all duration-700 {{ $iku['oba']['tercapai'] ? 'bg-emerald-500' : 'bg-blue-600' }}"
                            style="width: {{ min($iku['oba']['capaian_persen'], 100) }}%"
                        ></div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-2">
                        Dari total {{ $formatRibuan($iku['oba']['total']) }} iklan OBA dievaluasi ({{ $formatRibuan($iku['oba']['mk']) }} MK).
                    </p>
                </div>
            </div>

            <!-- IKU SK Card -->
            <div class="bg-white rounded-2xl border border-(--border) p-6 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Indikator Kinerja Utama (IKU)</span>
                        <h3 class="text-base font-extrabold text-gray-900">Suplemen Kesehatan (SK)</h3>
                        <p class="text-xs text-gray-500">Komoditi Suplemen Kesehatan</p>
                    </div>
                    @if ($iku['sk']['tercapai'])
                        <x-badge variant="success" size="sm">Target Tercapai</x-badge>
                    @else
                        <x-badge variant="warning" size="sm">Dalam Proses</x-badge>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-3 p-3.5 rounded-xl bg-slate-50 mb-4 border border-gray-100">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-gray-400">Target IKU</span>
                        <p class="text-lg font-extrabold text-gray-900">{{ number_format($iku['sk']['target'], 2, ',', '.') }}%</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-gray-400">Realisasi % MK</span>
                        <p class="text-lg font-extrabold text-emerald-700">{{ $formatPersen($iku['sk']['realisasi_persen_mk']) }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-gray-400">Capaian IKU</span>
                        <p class="text-lg font-extrabold {{ $iku['sk']['tercapai'] ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $formatPersen($iku['sk']['capaian_persen']) }}
                        </p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-gray-600">Progress Capaian terhadap Target IKU</span>
                        <span class="text-gray-900">{{ number_format(min($iku['sk']['capaian_persen'], 100), 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div
                            class="h-3 rounded-full transition-all duration-700 {{ $iku['sk']['tercapai'] ? 'bg-emerald-500' : 'bg-emerald-600' }}"
                            style="width: {{ min($iku['sk']['capaian_persen'], 100) }}%"
                        ></div>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-2">
                        Dari total {{ $formatRibuan($iku['sk']['total']) }} iklan SK dievaluasi ({{ $formatRibuan($iku['sk']['mk']) }} MK).
                    </p>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: Tren Bulanan % MK & 5 Pelanggaran TMK -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Chart: Tren Bulanan % MK (2 Cols) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-(--border) p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm md:text-base font-extrabold text-gray-900">
                                Tren Persentase Memenuhi Ketentuan (% MK) Bulanan
                            </h3>
                            <p class="text-xs text-gray-500">Perbandingan pergerakan realisasi bulanan OBA & SK terhadap Target IKU {{ $tahun }}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            Jan – Des {{ $tahun }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 relative h-72">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between text-xs text-gray-500 gap-2">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span>
                            <span>OBA (% MK)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                            <span>SK (% MK)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 border-t-2 border-dashed border-blue-400 inline-block"></span>
                            <span>Target OBA ({{ $iku['oba']['target'] }}%)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-4 border-t-2 border-dashed border-emerald-400 inline-block"></span>
                            <span>Target SK ({{ $iku['sk']['target'] }}%)</span>
                        </div>
                    </div>
                    <span class="text-[11px] italic text-gray-400">* Nilai kosong menunjukkan belum ada data final di bulan tersebut</span>
                </div>
            </div>

            <!-- Right Chart: 5 Pelanggaran TMK Terbanyak (1 Col) -->
            <div class="bg-white rounded-2xl border border-(--border) p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm md:text-base font-extrabold text-gray-900">
                                5 Pelanggaran TMK
                            </h3>
                            <p class="text-xs text-gray-500">Frekuensi rincian ketidaksesuaian</p>
                        </div>
                        <x-badge variant="danger" size="sm">
                            {{ $formatRibuan($metrics['top_tmk']['total_tmk']) }} TMK
                        </x-badge>
                    </div>
                </div>

                <div class="mt-4 relative h-72">
                    <canvas id="topTmkChart"></canvas>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500">
                    <p class="text-[11px]">Klaim berlebihan dan produk tidak terdaftar (TIE) mendominasi temuan pengawasan iklan.</p>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Stacked Bar Multi-Dimensi Switcher (Alpine.js) -->
        <div class="bg-white rounded-2xl border border-(--border) p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">
                        Distribusi Pengawasan: Memenuhi Ketentuan (MK) vs Pelanggaran (TMK)
                    </h3>
                    <p class="text-xs text-gray-500">Pilih dimensi untuk melihat perbandingan komposisi kepatuhan iklan</p>
                </div>

                <!-- Dimension Switcher Pills -->
                <div class="inline-flex rounded-xl bg-gray-100 p-1 gap-1 border border-gray-200">
                    <button
                        type="button"
                        @click="switchDimension('asal_produk')"
                        :class="currentDimension === 'asal_produk' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-gray-600 font-medium hover:text-gray-900'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer"
                    >
                        Asal Produk
                    </button>
                    <button
                        type="button"
                        @click="switchDimension('kategori_media')"
                        :class="currentDimension === 'kategori_media' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-gray-600 font-medium hover:text-gray-900'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer"
                    >
                        Kategori Media
                    </button>
                    <button
                        type="button"
                        @click="switchDimension('kategori_iklan')"
                        :class="currentDimension === 'kategori_iklan' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-gray-600 font-medium hover:text-gray-900'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer"
                    >
                        Kategori Iklan
                    </button>
                    <button
                        type="button"
                        @click="switchDimension('pembuat_iklan')"
                        :class="currentDimension === 'pembuat_iklan' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-gray-600 font-medium hover:text-gray-900'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all cursor-pointer"
                    >
                        Pembuat Iklan
                    </button>
                </div>
            </div>

            <!-- Stacked Bar Canvas -->
            <div class="relative h-80">
                <canvas id="dimensionStackedChart"></canvas>
            </div>
        </div>

        <!-- NIE E-Commerce Summary Comparison Table -->
        <div class="bg-white rounded-2xl border border-(--border) shadow-xs overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">
                        Ringkasan Pencantuman & Kesesuaian Nomor Izin Edar (NIE) E-Commerce
                    </h3>
                    <p class="text-xs text-gray-500">Evaluasi khusus iklan pada kanal E-Commerce untuk komoditi OBA dan SK</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    Kanal: E-Commerce
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs divide-y divide-gray-200">
                    <thead class="bg-slate-50 font-bold uppercase tracking-wider text-gray-700 text-[11px]">
                        <tr>
                            <th class="px-4 py-3 text-left">Komoditi</th>
                            <th class="px-4 py-3 text-right">Total Iklan E-Com</th>
                            <th class="px-4 py-3 text-right bg-blue-50/50">Ada NIE</th>
                            <th class="px-4 py-3 text-right bg-blue-50/50">% Ada NIE</th>
                            <th class="px-4 py-3 text-right">Tidak Ada NIE</th>
                            <th class="px-4 py-3 text-right bg-emerald-50/50">NIE Sesuai</th>
                            <th class="px-4 py-3 text-right bg-emerald-50/50">% Sesuai (dari Ada)</th>
                            <th class="px-4 py-3 text-right">NIE Tdk Sesuai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white font-medium">
                        <!-- OBA Row -->
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3.5 font-bold text-gray-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <span>Obat Bahan Alam (OBA)</span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-gray-900">
                                {{ $formatRibuan($metrics['nie_ecommerce']['oba']['total']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-blue-700 bg-blue-50/30">
                                {{ $formatRibuan($metrics['nie_ecommerce']['oba']['nie_ada']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-blue-800 bg-blue-50/30">
                                {{ $formatPersen($metrics['nie_ecommerce']['oba']['persen_ada']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-rose-600">
                                {{ $formatRibuan($metrics['nie_ecommerce']['oba']['nie_tidak_ada']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-emerald-700 bg-emerald-50/30">
                                {{ $formatRibuan($metrics['nie_ecommerce']['oba']['nie_sesuai']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-800 bg-emerald-50/30">
                                {{ $formatPersen($metrics['nie_ecommerce']['oba']['persen_sesuai']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-rose-600">
                                {{ $formatRibuan($metrics['nie_ecommerce']['oba']['nie_tidak_sesuai']) }}
                            </td>
                        </tr>

                        <!-- SK Row -->
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3.5 font-bold text-gray-900 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span>Suplemen Kesehatan (SK)</span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-gray-900">
                                {{ $formatRibuan($metrics['nie_ecommerce']['sk']['total']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-blue-700 bg-blue-50/30">
                                {{ $formatRibuan($metrics['nie_ecommerce']['sk']['nie_ada']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-blue-800 bg-blue-50/30">
                                {{ $formatPersen($metrics['nie_ecommerce']['sk']['persen_ada']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-rose-600">
                                {{ $formatRibuan($metrics['nie_ecommerce']['sk']['nie_tidak_ada']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-emerald-700 bg-emerald-50/30">
                                {{ $formatRibuan($metrics['nie_ecommerce']['sk']['nie_sesuai']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-800 bg-emerald-50/30">
                                {{ $formatPersen($metrics['nie_ecommerce']['sk']['persen_sesuai']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-rose-600">
                                {{ $formatRibuan($metrics['nie_ecommerce']['sk']['nie_tidak_sesuai']) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panduan Alur Modal (Bantuan Evaluator) -->
        <x-modal name="guideModal" maxWidth="3xl">
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        @if (file_exists(public_path('assets/BADAN_POM.png')))
                            <img src="{{ asset('assets/BADAN_POM.png') }}" alt="Logo BPOM" class="h-10 w-auto object-contain">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-(--brand-navy) text-white flex items-center justify-center font-bold text-sm">
                                BPOM
                            </div>
                        @endif
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Panduan Alur Sistem Pengolahan Data Iklan</h3>
                            <p class="text-xs text-gray-500">Tim Pengolahan Iklan, Direktorat Pengawasan OTSK</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'guideModal')"
                        class="text-gray-400 hover:text-gray-600 text-lg font-bold"
                    >
                        &times;
                    </button>
                </div>

                <div class="space-y-4 text-xs text-gray-700 leading-relaxed">
                    <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-900">
                        <span class="font-bold">Prinsip Sistem:</span> Aplikasi ini meniru alur konvensional penarikan SIPT bulanan, standardisasi kode angka, pengisian 3 kolom merah oleh evaluator, penyusunan rekapitulasi, dan visualisasi capaian IKU secara otomatis tanpa error hitung.
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/70 space-y-2">
                            <span class="font-bold text-blue-700 text-sm">1. Unggah Data Mentah SIPT</span>
                            <p>Evaluator menarik export data mentah bulanan dari SIPT (format <code>.xls</code> HTML atau <code>.xlsx</code>). Sistem membaca tabel raw 26 kolom dan memvalidasi keabsahan data.</p>
                        </div>
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/70 space-y-2">
                            <span class="font-bold text-blue-700 text-sm">2. Konversi Rule-Based & Manual</span>
                            <p>Sistem secara otomatis mengonversi teks mentah menjadi kode angka berpedoman pada Kamus Kode Standar. 3 kolom merah (Kesesuaian Evaluasi, Kualitas Laporan, Ketepatan Waktu) diisi manual dengan autosave.</p>
                        </div>
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/70 space-y-2">
                            <span class="font-bold text-blue-700 text-sm">3. Simpan & Kunci Data Final</span>
                            <p>Setelah seluruh baris berstatus terstandar lengkap, evaluator mengunci dataset. Sistem memberikan nomor urut tahunan resmi (1-N) yang atomik dan tidak dapat dimodifikasi lagi.</p>
                        </div>
                        <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/70 space-y-2">
                            <span class="font-bold text-blue-700 text-sm">4. Rekapitulasi & Ekspor Excel</span>
                            <p>Menu Rekap menghitung capaian IKU OBA & SK berdampingan secara instan. Fitur ekspor menghasilkan file Excel dengan rumus asli (SUM, division, COUNT) yang identik dengan template manual.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200 flex justify-end">
                    <x-button type="button" variant="primary" size="sm" @click="$dispatch('close-modal', 'guideModal')">
                        Mengerti
                    </x-button>
                </div>
            </div>
        </x-modal>
    </div>

    <!-- Chart.js and Alpine Component Script -->
    @push('scripts')
    <script>
        function dashboardAnalytics(config) {
            return {
                showGuideModal: false,
                currentDimension: 'asal_produk',
                monthlyChart: null,
                dimensionChart: null,
                topTmkChart: null,

                init() {
                    this.$nextTick(() => {
                        this.initMonthlyTrendChart();
                        this.initTopTmkChart();
                        this.initDimensionChart();
                    });
                },

                initMonthlyTrendChart() {
                    const ctx = document.getElementById('monthlyTrendChart');
                    if (!ctx) return;

                    const data = config.monthlyData;

                    this.monthlyChart = new window.Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'OBA (% MK)',
                                    data: data.oba,
                                    borderColor: '#2563EB',
                                    backgroundColor: '#2563EB',
                                    borderWidth: 2.5,
                                    tension: 0.3,
                                    spanGaps: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: 'SK (% MK)',
                                    data: data.sk,
                                    borderColor: '#10B981',
                                    backgroundColor: '#10B981',
                                    borderWidth: 2.5,
                                    tension: 0.3,
                                    spanGaps: true,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: 'Target OBA (67%)',
                                    data: data.target_oba,
                                    borderColor: '#93C5FD',
                                    borderWidth: 1.5,
                                    borderDash: [5, 5],
                                    pointRadius: 0,
                                    fill: false,
                                },
                                {
                                    label: 'Target SK (83%)',
                                    data: data.target_sk,
                                    borderColor: '#6EE7B7',
                                    borderWidth: 1.5,
                                    borderDash: [5, 5],
                                    pointRadius: 0,
                                    fill: false,
                                },
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const val = context.parsed.y;
                                            return val !== null ? `${context.dataset.label}: ${val.toFixed(2)}%` : `${context.dataset.label}: Belum ada data`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    min: 0,
                                    max: 100,
                                    ticks: {
                                        callback: (val) => val + '%',
                                        stepSize: 20,
                                        font: { size: 10 }
                                    },
                                    grid: { color: '#F3F4F6' }
                                },
                                x: {
                                    ticks: { font: { size: 10 } },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                },

                initTopTmkChart() {
                    const ctx = document.getElementById('topTmkChart');
                    if (!ctx) return;

                    const data = config.topTmkData;

                    this.topTmkChart = new window.Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Kasus TMK',
                                data: data.values,
                                backgroundColor: [
                                    '#EF4444',
                                    '#F87171',
                                    '#FCA5A5',
                                    '#FECDD3',
                                    '#FFE4E6'
                                ],
                                borderRadius: 6,
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const idx = context.dataIndex;
                                            const count = context.parsed.x;
                                            const pct = data.percentages[idx] ?? 0;
                                            return `${count.toLocaleString('id-ID')} kasus (${pct}%)`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: { font: { size: 10 } },
                                    grid: { color: '#F3F4F6' }
                                },
                                y: {
                                    ticks: { font: { size: 10 } },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                },

                initDimensionChart() {
                    const ctx = document.getElementById('dimensionStackedChart');
                    if (!ctx) return;

                    const curData = config.dimensionsData[this.currentDimension];

                    this.dimensionChart = new window.Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: curData.labels,
                            datasets: [
                                {
                                    label: 'Memenuhi Ketentuan (MK)',
                                    data: curData.mk,
                                    backgroundColor: '#10B981',
                                    borderRadius: 4,
                                },
                                {
                                    label: 'Tidak Memenuhi Ketentuan (TMK)',
                                    data: curData.tmk,
                                    backgroundColor: '#EF4444',
                                    borderRadius: 4,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    stacked: true,
                                    ticks: { font: { size: 10 } },
                                    grid: { display: false }
                                },
                                y: {
                                    stacked: true,
                                    ticks: { font: { size: 10 } },
                                    grid: { color: '#F3F4F6' }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { font: { size: 11, weight: 'bold' } }
                                },
                                tooltip: {
                                    callbacks: {
                                        footer: function(tooltipItems) {
                                            let sum = 0;
                                            tooltipItems.forEach(item => { sum += item.parsed.y; });
                                            return `Total: ${sum.toLocaleString('id-ID')} iklan`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                },

                switchDimension(dimensionKey) {
                    this.currentDimension = dimensionKey;
                    const curData = config.dimensionsData[dimensionKey];
                    if (this.dimensionChart && curData) {
                        this.dimensionChart.data.labels = curData.labels;
                        this.dimensionChart.data.datasets[0].data = curData.mk;
                        this.dimensionChart.data.datasets[1].data = curData.tmk;
                        this.dimensionChart.update();
                    }
                }
            };
        }
    </script>
    @endpush
</x-layouts.app>
