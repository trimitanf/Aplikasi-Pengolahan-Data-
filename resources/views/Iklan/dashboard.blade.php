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
        $uptObj = is_iterable($metrics['upt_list'] ?? null) ? collect($metrics['upt_list'])->firstWhere('kode', $uptKode) : null;
        $uptNamaLengkap = $uptKode ? (is_object($uptObj) ? ($uptObj->nama ?? "UPT $uptKode") : (is_array($uptObj) ? ($uptObj['nama'] ?? "UPT $uptKode") : "UPT $uptKode")) : 'Semua UPT (Nasional)';
        $komoditiLabel = match ($komoditi) {
            'OBA', 'OT' => 'Obat Bahan Alam (OBA)',
            'OK' => 'Obat Kuasi (OK)',
            'SK' => 'Suplemen Kesehatan (SK)',
            default => 'Semua Komoditi',
        };

        // Narasi dinamis Pelanggaran TMK
        $tmkTotalKasus = (int) ($metrics['top_tmk']['total_tmk'] ?? $kpi['tmk']);
        $validTopItems = collect($metrics['top_tmk']['items'] ?? [])
            ->filter(fn($item) => ($item['total'] ?? 0) > 0)
            ->values();

        if ($tmkTotalKasus === 0 || $validTopItems->isEmpty()) {
            $narasiTmk = 'Belum terdapat temuan pelanggaran (TMK) pada periode dan cakupan filter ini.';
        } elseif ($validTopItems->count() === 1) {
            $firstItem = $validTopItems[0];
            $narasiTmk = "Pelanggaran didominasi oleh {$firstItem['label']} sebanyak {$formatRibuan($firstItem['total'])} kasus ({$firstItem['persen']}%).";
        } else {
            $firstItem = $validTopItems[0];
            $secondItem = $validTopItems[1];
            $narasiTmk = "Temuan terbanyak didominasi oleh {$firstItem['label']} ({$formatRibuan($firstItem['total'])} kasus) dan {$secondItem['label']} ({$formatRibuan($secondItem['total'])} kasus).";
        }
    @endphp

    @push('styles')
    <style>
        /* Standarisasi Design System Corporate Dashboard (1px solid #E2E8F0, rounded 12px, soft shadow) */
        .dash-card {
            background-color: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05) !important;
        }
        .dash-subcard {
            background-color: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.03) !important;
        }
        .exec-card {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Standarisasi white-space nowrap pada badge & item legenda agar tidak pecah baris */
        .dash-badge,
        .dash-legend-item,
        .badge-nowrap,
        [class*="dash-badge"] {
            white-space: nowrap !important;
        }

        .print-chart-snapshot {
            display: none;
        }

        /* Saat menyiapkan snapshot chart sebelum dialog print dibuka
           Ukuran landscape @2x DPI: proporsional dengan grid landscape A4 */
        body.preparing-print-snapshot .print-pie-canvas-wrap {
            width: 100px !important;
            height: 100px !important;
        }
        body.preparing-print-snapshot .print-trend-canvas-wrap {
            /* Landscape: kolom kiri ~60% dari 257mm ≈ ~154mm → 580px @2x */
            width: 580px !important;
            height: 130px !important;
        }
        body.preparing-print-snapshot .print-tmk-canvas-wrap {
            /* Landscape: kolom kanan ~40% ≈ ~100mm → 380px @2x */
            width: 380px !important;
            height: 130px !important;
        }
        body.preparing-print-snapshot .print-top-upt-canvas-wrap {
            /* Landscape: kolom kanan ~60% ≈ ~150mm → 480px @2x */
            width: 480px !important;
            height: 130px !important;
        }
        body.preparing-print-snapshot .print-distribusi-canvas-wrap {
            /* Landscape: 2 kolom ≈ ~125mm each → 470px @2x */
            width: 470px !important;
            height: 85px !important;
        }

        /* ============================================================
           SISTEM LAYOUT ANTI-OVERFLOW & GRID ALIGNMENT EKSPLISIT
           ============================================================ */
        .dash-main-container {
            min-width: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
            overflow-x: hidden !important;
        }

        /* Baris 1: 5 KPI Cards Sejajar */
        .grid-kpi-row-5 {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            gap: 12px !important;
            width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        .kpi-card-compact {
            background-color: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 12px !important;
            padding: 14px 16px !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            transition: all 0.2s ease !important;
        }
        .kpi-card-compact:hover {
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.08) !important;
        }

        /* Baris 2: Donut Chart (4fr) & Top UPT TMK (8fr) */
        .grid-row-2 {
            display: grid !important;
            grid-template-columns: minmax(0, 4fr) minmax(0, 8fr) !important;
            gap: 16px !important;
            width: 100% !important;
            align-items: stretch !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        /* Baris 3: Tren Bulanan (7fr) & Pelanggaran TMK (5fr) */
        .grid-row-3 {
            display: grid !important;
            grid-template-columns: minmax(0, 7fr) minmax(0, 5fr) !important;
            gap: 16px !important;
            width: 100% !important;
            align-items: stretch !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        /* Baris 4: 4 Kartu Distribusi (2 Kolom) */
        .grid-row-4 {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 16px !important;
            width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        /* Seluruh direct child di dalam CSS Grid WAJIB min-width: 0 agar canvas tidak mendorong kolom */
        .grid-kpi-row-5 > *,
        .grid-row-2 > *,
        .grid-row-3 > *,
        .grid-row-4 > *,
        .dash-card,
        .dash-subcard,
        .kpi-card-compact {
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .grid-kpi-row-5 {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }
        @media (max-width: 1100px) {
            .grid-row-2,
            .grid-row-3 {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
        @media (max-width: 768px) {
            .grid-kpi-row-5 {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .grid-row-4 {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
        @media (max-width: 540px) {
            .grid-kpi-row-5 {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }

        @media print {
            /* ============================================================
               A4 LANDSCAPE — LEBAR 297mm x TINGGI 210mm (TEPAT 2 HALAMAN)
               ============================================================ */
            @page {
                size: A4 landscape;
                margin: 7mm 10mm 10mm 10mm;
                @bottom-left {
                    content: "Laporan Monitoring Pengawasan Iklan OTSK \2014 BPOM";
                    font-family: "Segoe UI", Arial, sans-serif;
                    font-size: 6.5pt;
                    color: #64748B;
                }
                @bottom-right {
                    content: "Halaman " counter(page) " dari " counter(pages);
                    font-family: "Segoe UI", Arial, sans-serif;
                    font-size: 6.5pt;
                    color: #64748B;
                }
            }

            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-sizing: border-box !important;
            }

            aside,
            header,
            nav,
            .no-print,
            .print\:hidden,
            button,
            .btn-no-print,
            .x-modal,
            [x-cloak],
            [style*="display: none"],
            [style*="display:none"] {
                display: none !important;
            }

            .print-footer-static {
                display: none !important;
            }

            html, body {
                background: #ffffff !important;
                color: #0f172a !important;
                font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, "Helvetica Neue", Arial, sans-serif !important;
                font-size: 8px !important;
                line-height: 1.25 !important;
                width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            body > div,
            body > div > div,
            main {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                display: block !important;
            }

            /* Seluruh kartu dalam print menggunakan padding kompak agar tidak meluber */
            .dash-card,
            .dash-subcard,
            .exec-card {
                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 6px !important;
                box-shadow: none !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                margin-bottom: 5px !important;
                padding: 6px 10px !important;
            }

            /* Sembunyikan canvas aktif saat cetak — ganti dengan snapshot img tajam */
            .screen-chart-canvas {
                display: none !important;
            }
            .print-chart-snapshot {
                display: block !important;
                width: 100% !important;
                height: auto !important;
                max-width: 100% !important;
                object-fit: contain !important;
            }

            /* ============================================================
               PENGATURAN KOP RESMI LAPORAN (PRINT)
               ============================================================ */
            .print-kop-wrapper {
                border-bottom: 2px solid #003B7A !important;
                padding-bottom: 4px !important;
                margin-bottom: 5px !important;
            }
            .print-kop-inner {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 10px !important;
            }
            .print-kop-brand {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
            }
            .print-kop-logo {
                height: 30px !important;
                width: auto !important;
                object-fit: contain !important;
            }

            /* Strip Metadata Audit Filter */
            .print-metadata-strip {
                display: flex !important;
                flex-wrap: wrap !important;
                align-items: center !important;
                gap: 8px 16px !important;
                background: #F8FAFC !important;
                border: 1px solid #E2E8F0 !important;
                border-radius: 4px !important;
                padding: 3px 8px !important;
                margin-top: 3px !important;
                font-size: 7.5px !important;
            }
            .print-metadata-strip .pmeta-item {
                display: inline-flex !important;
                align-items: center !important;
                gap: 3px !important;
            }
            .print-metadata-strip .pmeta-label {
                color: #64748B !important;
                font-weight: 600 !important;
            }
            .print-metadata-strip .pmeta-value {
                color: #0F172A !important;
                font-weight: 700 !important;
            }

            /* ============================================================
               GRID KHUSUS PRINT A4 LANDSCAPE (LEBAR ~260mm)
               ============================================================ */
            .grid-kpi-row-5,
            .grid-row-2,
            .grid-row-3,
            .grid-row-4 {
                width: 100% !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* ---- BARIS 1: 5 KPI Cards Sejajar ---- */
            .print-kpi-grid-5 {
                display: grid !important;
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
                gap: 5px !important;
                margin-bottom: 5px !important;
                width: 100% !important;
            }
            .kpi-card-compact {
                padding: 5px 8px !important;
                border: 1px solid #CBD5E1 !important;
                box-shadow: none !important;
                min-width: 0 !important;
            }
            .kpi-card-compact .text-\[24px\] {
                font-size: 15px !important;
                line-height: 1.1 !important;
            }

            /* ---- BARIS 2: Donut Chart (~38%) + Top UPT TMK (~62%) ---- */
            .print-row-2-grid {
                display: grid !important;
                grid-template-columns: minmax(0, 3.8fr) minmax(0, 6.2fr) !important;
                gap: 8px !important;
                margin-bottom: 5px !important;
                width: 100% !important;
                align-items: stretch !important;
            }
            .print-pie-canvas-wrap {
                width: 95px !important;
                height: 95px !important;
                min-width: 95px !important;
                max-width: 95px !important;
                min-height: 95px !important;
                max-height: 95px !important;
                margin: 2px auto !important;
            }
            .print-pie-canvas-wrap .text-2xl {
                font-size: 13px !important;
            }
            .print-top-upt-canvas-wrap {
                width: 100% !important;
                height: 125px !important;
                max-height: 125px !important;
                position: relative !important;
                overflow: hidden !important;
                margin: 2px 0 !important;
            }
            .print-top-upt-canvas-wrap .print-chart-snapshot {
                width: 100% !important;
                height: 125px !important;
                max-height: 125px !important;
                object-fit: contain !important;
            }

            /* ---- BARIS 3: Tren Bulanan (~60%) + Pelanggaran TMK (~40%) ---- */
            .print-trend-grid {
                display: grid !important;
                grid-template-columns: minmax(0, 6fr) minmax(0, 4fr) !important;
                gap: 8px !important;
                margin-bottom: 5px !important;
                width: 100% !important;
                align-items: stretch !important;
            }
            .print-trend-canvas-wrap {
                width: 100% !important;
                height: 125px !important;
                max-height: 125px !important;
                position: relative !important;
                overflow: hidden !important;
                margin: 2px 0 !important;
            }
            .print-trend-canvas-wrap .print-chart-snapshot {
                width: 100% !important;
                height: 125px !important;
                max-height: 125px !important;
                object-fit: contain !important;
            }
            .print-tmk-canvas-wrap {
                width: 100% !important;
                height: 125px !important;
                max-height: 125px !important;
                position: relative !important;
                overflow: hidden !important;
                margin: 2px 0 !important;
            }
            .print-tmk-canvas-wrap .print-chart-snapshot {
                width: 100% !important;
                height: 125px !important;
                max-height: 125px !important;
                object-fit: contain !important;
            }

            /* ============================================================
               HALAMAN 2: DISTRIBUSI HASIL PENGAWASAN (GRID 2x2 SEJAJAR)
               ============================================================ */
            .page-break-before-distribusi {
                break-before: page !important;
                page-break-before: always !important;
                margin-top: 0 !important;
                padding-top: 0 !important;
            }
            .print-distribusi-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 8px !important;
                width: 100% !important;
                margin-top: 4px !important;
            }

            /* Wadah kanvas grafik distribusi — KUNCI TINGGI AGAR TIDAK PERNAH OVERLAP DENGAN TABEL */
            .print-distribusi-canvas-wrap {
                width: 100% !important;
                height: 85px !important;
                max-height: 85px !important;
                min-height: 85px !important;
                position: relative !important;
                overflow: hidden !important;
                margin: 2px 0 4px 0 !important;
                display: block !important;
            }
            .print-distribusi-canvas-wrap .print-chart-snapshot {
                display: block !important;
                width: 100% !important;
                height: 85px !important;
                max-height: 85px !important;
                object-fit: contain !important;
                margin: 0 auto !important;
            }

            /* Tabel Matriks Data di dalam Kartu Distribusi saat Cetak */
            .print-distribusi-grid table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 7.5px !important;
                line-height: 1.15 !important;
                margin-top: 2px !important;
            }
            .print-distribusi-grid th {
                padding: 2px 4px !important;
                font-size: 7px !important;
                font-weight: 700 !important;
                background-color: #F8FAFC !important;
                border-bottom: 1px solid #CBD5E1 !important;
            }
            .print-distribusi-grid td {
                padding: 1.5px 4px !important;
                font-size: 7.5px !important;
                border-bottom: 1px solid #F1F5F9 !important;
            }
        }
    </style>
    @endpush

    {{-- KOP SURAT RESMI KHUSUS PRINT A4 LANDSCAPE --}}
    <div class="hidden print:block print-kop-wrapper">
        <div class="print-kop-inner">
            <div class="print-kop-brand">
                <img src="{{ asset('assets/BADAN_POM.png') }}" alt="Logo BPOM" class="print-kop-logo">
                <div class="space-y-0.5">
                    <h1 class="text-[13px] font-black text-slate-900 uppercase tracking-wide leading-tight">
                        BADAN PENGAWAS OBAT DAN MAKANAN
                    </h1>
                    <h2 class="text-[10px] font-bold text-slate-700 leading-snug">
                        Direktorat Pengawasan Obat Tradisional dan Suplemen Kesehatan
                    </h2>
                    <p class="text-[10.5px] font-extrabold text-[#2563EB] leading-snug pt-0.5">
                        Laporan Eksekutif Pengawasan Iklan OTSK — Tahun {{ $tahun }} ({{ $komoditiLabel }})
                    </p>
                </div>
            </div>
            <div class="text-right text-[8px] text-slate-500 font-semibold">
                <span>Filter UPT: {{ $uptNamaLengkap }}</span>
            </div>
        </div>
        {{-- Strip Metadata Audit Filter --}}
        <div class="print-metadata-strip">
            <span class="pmeta-item">
                <span class="pmeta-label">Tahun Pengawasan:</span>
                <span class="pmeta-value">{{ $tahun }}</span>
            </span>
            <span class="pmeta-item">
                <span class="pmeta-label">Komoditi:</span>
                <span class="pmeta-value">{{ $komoditiLabel }}</span>
            </span>
            <span class="pmeta-item">
                <span class="pmeta-label">Cakupan UPT:</span>
                <span class="pmeta-value">{{ $uptNamaLengkap }}</span>
            </span>
            <span class="pmeta-item">
                <span class="pmeta-label">Waktu Cetak:</span>
                <span class="pmeta-value" id="printTimestamp">—</span>
            </span>
        </div>
    </div>

    <div
        x-data="dashboardAnalytics({
            dataTersimpanUrl: {{ json_encode(route('data-tersimpan.index')) }},
            tahun: {{ $tahun }},
            komoditi: {{ json_encode($komoditi) }},
            upt: {{ json_encode($upt ?? request('upt')) }},
            kpiData: {{ json_encode($kpi) }},
            ikuData: {{ json_encode($iku) }},
            monthlyData: {{ json_encode($metrics['monthly_trend']) }},
            dimensionsData: {{ json_encode($metrics['dimensions']) }},
            topTmkData: {{ json_encode($metrics['top_tmk']) }},
            topUptTmkData: {{ json_encode($metrics['top_upt_tmk'] ?? []) }}
        })"
        class="space-y-5 dash-main-container"
        style="min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; overflow-x: hidden;"
    >
        <!-- Filter & Header Actions Toolbar -->
        <x-card class="dash-card overflow-visible! no-print" style="width: 100%; min-width: 0; box-sizing: border-box;">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
                    <!-- Tahun Pengawasan -->
                    <div class="space-y-1">
                        <label for="selectTahunPengawasan" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Tahun Pengawasan
                        </label>
                        <select
                            id="selectTahunPengawasan"
                            name="tahun"
                            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-900 font-medium focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 bg-white cursor-pointer"
                        >
                            @foreach ($metrics['available_years'] as $y)
                                <option value="{{ $y }}" @selected($tahun == $y)>{{ $y }}</option>
                            @endforeach
                            @if (!in_array($tahun, $metrics['available_years']))
                                <option value="{{ $tahun }}" selected>{{ $tahun }}</option>
                            @endif
                        </select>
                    </div>

                    <!-- Komoditi -->
                    <div class="space-y-1">
                        <label for="selectKomoditi" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Komoditi
                        </label>
                        <select
                            id="selectKomoditi"
                            name="komoditi"
                            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-900 font-medium focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 bg-white cursor-pointer"
                        >
                            <option value="SEMUA" @selected($komoditi === 'SEMUA')>Semua Komoditi</option>
                            <option value="OBA" @selected($komoditi === 'OBA' || $komoditi === 'OT')>Obat Bahan Alam (OBA)</option>
                            <option value="OK" @selected($komoditi === 'OK')>Obat Kuasi (OK)</option>
                            <option value="SK" @selected($komoditi === 'SK')>Suplemen Kesehatan (SK)</option>
                        </select>
                    </div>

                    <!-- Balai / Loka POM (UPT) -->
                    <div class="space-y-1">
                        <label for="selectUpt" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            Balai / Loka POM (UPT)
                        </label>
                        <select
                            id="selectUpt"
                            name="upt"
                            class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-900 font-medium focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 bg-white cursor-pointer"
                        >
                            <option value="">Semua UPT (Nasional)</option>
                            @if (is_iterable($metrics['upt_list'] ?? null))
                                @foreach ($metrics['upt_list'] as $upt)
                                    @php
                                        $k = is_object($upt) ? ($upt->kode ?? '') : (is_array($upt) ? ($upt['kode'] ?? '') : '');
                                        $n = is_object($upt) ? ($upt->nama ?? '') : (is_array($upt) ? ($upt['nama'] ?? '') : '');
                                    @endphp
                                    @if ($k !== '')
                                        <option value="{{ $k }}" @selected((string)$uptKode === (string)$k)>
                                            [{{ $k }}] {{ $n }}
                                        </option>
                                    @endif
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 shrink-0">
                    <x-button type="submit" variant="primary" size="sm">
                        <span class="dash-badge">Terapkan Filter</span>
                    </x-button>

                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'guideModal')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 text-xs font-semibold hover:bg-blue-100 transition-colors shadow-2xs cursor-pointer dash-badge"
                    >
                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Panduan Alur</span>
                    </button>

                    <button
                        type="button"
                        onclick="exportDashboardPdf()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-rose-200 bg-rose-50 text-rose-800 text-xs font-bold hover:bg-rose-100 transition-colors shadow-2xs cursor-pointer dash-badge"
                        title="Ekspor seluruh visualisasi data dashboard ke file PDF"
                    >
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor PDF</span>
                    </button>

                    <a
                        href="{{ route('rekap.export', ['tahun' => $filter['tahun'] ?? now()->year, 'bulan_awal' => 1, 'bulan_akhir' => 12]) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-bold hover:bg-emerald-100 transition-colors shadow-2xs dash-badge"
                        title="Ekspor rekapitulasi data dashboard ke file Excel (.xlsx)"
                    >
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor Excel</span>
                    </a>
                </div>
            </form>
        </x-card>

        <!-- BARIS 1: 5 KARTU KPI SEJAJAR (KOMPAK & KONSISTEN) -->
        <div class="grid-kpi-row-5 print-kpi-grid-5">
            <!-- 1. Card Total Pengawasan -->
            <div class="dash-card exec-card kpi-card-compact hover:border-slate-400 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Total Pengawasan
                    </span>
                </div>
                <div class="my-2.5">
                    <div class="text-[24px] font-extrabold text-slate-900 tracking-tight leading-tight">
                        {{ $formatRibuan($kpi['total_pengawasan']) }}
                        <span class="text-xs font-semibold text-slate-500">iklan</span>
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="font-medium text-slate-400">Komoditas:</span>
                    <span class="font-bold text-slate-800 dash-badge" style="white-space: nowrap;">
                        OBA: {{ $formatRibuan($iku['rincian']['oba']['total'] ?? $iku['oba']['murni_total'] ?? 0) }} • OK: {{ $formatRibuan($iku['ok']['total'] ?? 0) }} • SK: {{ $formatRibuan($iku['sk']['total'] ?? 0) }}
                    </span>
                </div>
            </div>

            <!-- 2. Card Memenuhi Ketentuan (MK) -->
            <div class="dash-card exec-card kpi-card-compact hover:border-emerald-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Memenuhi (MK)
                    </span>
                    <span class="dash-badge px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-[#16A34A] border border-emerald-200" style="white-space: nowrap;">
                        {{ $formatPersen($kpi['persen_mk']) }}
                    </span>
                </div>
                <div class="my-2.5">
                    <div class="text-[24px] font-extrabold text-[#16A34A] tracking-tight leading-tight">
                        {{ $formatRibuan($kpi['mk']) }}
                        <span class="text-xs font-semibold text-slate-500">iklan</span>
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="font-medium text-slate-400">Rincian MK:</span>
                    <span class="font-bold text-[#16A34A] dash-badge" style="white-space: nowrap;">
                        OBA: {{ $formatRibuan($iku['rincian']['oba']['mk'] ?? $iku['oba']['murni_mk'] ?? 0) }} • OK: {{ $formatRibuan($iku['ok']['mk'] ?? 0) }} • SK: {{ $formatRibuan($iku['sk']['mk'] ?? 0) }}
                    </span>
                </div>
            </div>

            <!-- 3. Card Tidak Memenuhi (TMK) -->
            <div class="dash-card exec-card kpi-card-compact hover:border-rose-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Tidak Memenuhi (TMK)
                    </span>
                    <span class="dash-badge px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-[#DC2626] border border-rose-200" style="white-space: nowrap;">
                        {{ $formatPersen($kpi['persen_tmk']) }}
                    </span>
                </div>
                <div class="my-2.5">
                    <div class="text-[24px] font-extrabold text-[#DC2626] tracking-tight leading-tight">
                        {{ $formatRibuan($kpi['tmk']) }}
                        <span class="text-xs font-semibold text-slate-500">iklan</span>
                    </div>
                </div>
                @php
                    $topTmkLabel = !empty($metrics['top_tmk']['labels']) ? $metrics['top_tmk']['labels'][0] : 'Nihil';
                    $topTmkVal = !empty($metrics['top_tmk']['values']) ? $metrics['top_tmk']['values'][0] : 0;
                @endphp
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                    <span class="font-medium text-slate-400">Temuan:</span>
                    <span class="font-bold text-[#DC2626] truncate ml-1 text-right dash-badge" title="{{ $topTmkLabel }}" style="white-space: nowrap;">
                        {{ $kpi['tmk'] > 0 ? $topTmkLabel . ' (' . $formatRibuan($topTmkVal) . ')' : 'Nihil (0 kasus)' }}
                    </span>
                </div>
            </div>

            <!-- 4. Card IKU OBA -->
            <div class="dash-card exec-card kpi-card-compact hover:border-blue-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        IKU OBA
                    </span>
                    @if ($iku['oba']['tercapai'])
                        <span class="dash-badge px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-[#059669] border border-emerald-200" style="white-space: nowrap;">
                            ✓ Tercapai
                        </span>
                    @endif
                </div>
                <div class="my-2.5">
                    <div class="text-[24px] font-extrabold text-[#2563EB] tracking-tight leading-tight">
                        {{ $formatPersen($iku['oba']['realisasi_persen_mk']) }}
                    </div>
                    <span class="text-[9.5px] font-bold uppercase text-slate-400 block tracking-wider mt-0.5">Realisasi % MK</span>
                    <!-- Progress Bar Indikator Capaian Target -->
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden mt-2">
                        <div class="bg-[#2563EB] h-1.5 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, (float)$iku['oba']['capaian_persen'])) }}%"></div>
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">Target: <strong class="text-slate-700 font-bold">{{ $formatPersen($iku['oba']['target']) }}</strong></span>
                    <span class="text-[#2563EB] font-bold dash-badge" style="white-space: nowrap;">Capaian: {{ $formatPersen($iku['oba']['capaian_persen']) }}</span>
                </div>
            </div>

            <!-- 5. Card IKU SK -->
            <div class="dash-card exec-card kpi-card-compact hover:border-emerald-300 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        IKU SK
                    </span>
                    @if ($iku['sk']['tercapai'])
                        <span class="dash-badge px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-[#059669] border border-emerald-200" style="white-space: nowrap;">
                            ✓ Tercapai
                        </span>
                    @endif
                </div>
                <div class="my-2.5">
                    <div class="text-[24px] font-extrabold text-[#059669] tracking-tight leading-tight">
                        {{ $formatPersen($iku['sk']['realisasi_persen_mk']) }}
                    </div>
                    <span class="text-[9.5px] font-bold uppercase text-slate-400 block tracking-wider mt-0.5">Realisasi % MK</span>
                    <!-- Progress Bar Indikator Capaian Target -->
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden mt-2">
                        <div class="bg-[#059669] h-1.5 rounded-full transition-all duration-500" style="width: {{ min(100, max(0, (float)$iku['sk']['capaian_persen'])) }}%"></div>
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                    <span class="text-slate-400 font-medium">Target: <strong class="text-slate-700 font-bold">{{ $formatPersen($iku['sk']['target']) }}</strong></span>
                    <span class="text-[#059669] font-bold dash-badge" style="white-space: nowrap;">Capaian: {{ $formatPersen($iku['sk']['capaian_persen']) }}</span>
                </div>
            </div>
        </div>

        <!-- BARIS 2: PROPORSI EVALUASI KEPATUHAN (minmax(0, 4fr)) & UPT DENGAN PELANGGARAN TMK TERTINGGI (minmax(0, 8fr)) -->
        <div class="grid-row-2 print-row-2-grid">
            <!-- Kolom Kiri: Donut Chart Proporsi Evaluasi Kepatuhan Interaktif -->
            <div class="dash-card exec-card p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            Proporsi Evaluasi Kepatuhan
                        </h3>
                    </div>
                    <span class="dash-badge text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                        {{ $tahun }}
                    </span>
                </div>

                <!-- Layout: Donut Chart Centered (Interaktif & Bersih) -->
                <div class="flex-1 flex flex-col items-center justify-center my-auto py-3">
                    <!-- Donut / Pie Canvas Container -->
                    <div class="print-pie-canvas-wrap my-1 cursor-pointer" title="Klik bagian lingkaran untuk melihat rincian data" style="width: 165px; height: 165px; min-width: 165px; max-width: 165px; min-height: 165px; max-height: 165px; position: relative; display: flex; align-items: center; justify-content: center;">
                        <canvas id="mkTmkPieChart" width="165" height="165"></canvas>
                        <!-- Center Overlay Angka Total -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                            <span class="text-2xl font-black text-slate-900 leading-none tracking-tight print:text-base">
                                {{ $formatRibuan($kpi['total_pengawasan']) }}
                            </span>
                            <span class="text-[9.5px] font-bold text-slate-400 uppercase tracking-wider mt-0.5 print:text-[7px]">Total Iklan</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: UPT DENGAN PELANGGARAN TMK TERTINGGI -->
            <div class="dash-card exec-card p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                @php
                    $topUptList = $metrics['top_upt_tmk']['items'] ?? [];
                    $firstUpt = !empty($topUptList) ? $topUptList[0] : null;
                @endphp
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 uppercase tracking-wider">
                            UPT DENGAN PELANGGARAN TMK TERTINGGI
                        </h3>
                    </div>
                </div>

                <!-- Konten Visualisasi Top UPT TMK -->
                <div class="flex-1 my-auto py-2">
                    <div class="relative print-top-upt-canvas-wrap" style="position: relative; width: 100%; max-width: 100%; height: 250px;">
                        <canvas id="topUptTmkChart" class="screen-chart-canvas"></canvas>
                        <img id="printTopUptTmkImg" class="print-chart-snapshot" alt="Grafik Top UPT TMK">
                    </div>
                </div>
            </div>
        </div>

        <!-- BARIS 3: TREN BULANAN COMBO CHART (minmax(0, 7fr)) & PELANGGARAN TMK PARETO (minmax(0, 5fr)) -->
        <div class="grid-row-3 print-trend-grid">
            <!-- Kolom Kiri: Tren Bulanan (% MK Gabungan, OBA, SK & Garis Target) -->
            <div class="dash-card exec-card p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            Tren Kepatuhan Iklan Bulanan (% MK)
                        </h3>
                    </div>
                    <span class="dash-badge text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                        Jan – Des {{ $tahun }}
                    </span>
                </div>

                <div class="mt-3 relative print-trend-canvas-wrap" style="position: relative; width: 100%; max-width: 100%; height: 260px;">
                    <canvas id="monthlyTrendChart" class="screen-chart-canvas"></canvas>
                    <img id="printMonthlyTrendImg" class="print-chart-snapshot" alt="Grafik Tren Bulanan">
                </div>

                <!-- Legenda 1 Baris Rapi Tanpa Meluber -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 gap-2 print:text-[8.5px]">
                    <div class="flex items-center gap-3 overflow-hidden text-[10.5px]">
                        <span class="inline-flex items-center gap-1 text-blue-700 font-bold dash-legend-item">
                            <span class="w-2.5 h-1 rounded bg-[#2563EB] inline-block"></span> OBA
                        </span>
                        <span class="inline-flex items-center gap-1 text-amber-700 font-bold dash-legend-item">
                            <span class="w-2.5 h-1 rounded bg-[#D97706] inline-block"></span> OK
                        </span>
                        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold dash-legend-item">
                            <span class="w-2.5 h-1 rounded bg-[#059669] inline-block"></span> SK
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-700 font-bold dash-legend-item">
                            <span class="w-2.5 h-1 rounded bg-[#475569] inline-block"></span> Gabungan
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-500 font-semibold dash-legend-item">
                            <span class="w-2.5 h-0.5 border-t border-dashed border-slate-400 inline-block"></span> Target
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-400 italic shrink-0 print:hidden">* Nilai kosong: belum ada data final</span>
                </div>
            </div>

            <!-- Kolom Kanan: Pelanggaran TMK -->
            <div class="dash-card exec-card p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                            Pelanggaran TMK
                        </h3>
                    </div>
                </div>

                <div class="mt-3 relative print-tmk-canvas-wrap" style="position: relative; width: 100%; max-width: 100%; height: 260px;">
                    <canvas id="topTmkChart" class="screen-chart-canvas"></canvas>
                    <img id="printTopTmkImg" class="print-chart-snapshot" alt="Grafik Pelanggaran TMK">
                </div>
            </div>
        </div>

        <!-- BARIS 4: 4 KARTU DISTRIBUSI PENGAWASAN (GRID 2x2 PADAT & LENGKAP DENGAN TABEL MATRIKS) -->
        <div class="space-y-4 page-break-before-distribusi">
            @php
                $distribusiCards = [
                    [
                        'key' => 'asal_produk',
                        'title' => 'Distribusi Berdasarkan Asal Produk',
                        'subtitle' => 'Perbandingan kepatuhan produk Lokal, Impor, dan Lisensi',
                        'canvasId' => 'dimAsalProdukChart',
                        'printImgId' => 'printDimAsalProdukImg',
                        'labels' => $metrics['dimensions']['asal_produk']['labels'] ?? ['Lokal', 'Impor', 'Lisensi'],
                        'total' => $metrics['dimensions']['asal_produk']['total'] ?? [],
                        'mk' => $metrics['dimensions']['asal_produk']['mk'] ?? [],
                        'tmk' => $metrics['dimensions']['asal_produk']['tmk'] ?? [],
                    ],
                    [
                        'key' => 'kategori_media',
                        'title' => 'Distribusi Berdasarkan Kategori Media',
                        'subtitle' => 'Perbandingan kepatuhan media Audio, Visual, dan Audiovisual',
                        'canvasId' => 'dimKategoriMediaChart',
                        'printImgId' => 'printDimKategoriMediaImg',
                        'labels' => $metrics['dimensions']['kategori_media']['labels'] ?? ['Audio', 'Visual', 'Audiovisual'],
                        'total' => $metrics['dimensions']['kategori_media']['total'] ?? [],
                        'mk' => $metrics['dimensions']['kategori_media']['mk'] ?? [],
                        'tmk' => $metrics['dimensions']['kategori_media']['tmk'] ?? [],
                    ],
                    [
                        'key' => 'kategori_iklan',
                        'title' => 'Distribusi Berdasarkan Kategori Iklan',
                        'subtitle' => 'Kepatuhan pada 8 kanal media periklanan',
                        'canvasId' => 'dimKategoriIklanChart',
                        'printImgId' => 'printDimKategoriIklanImg',
                        'labels' => $metrics['dimensions']['kategori_iklan']['labels'] ?? [],
                        'total' => $metrics['dimensions']['kategori_iklan']['total'] ?? [],
                        'mk' => $metrics['dimensions']['kategori_iklan']['mk'] ?? [],
                        'tmk' => $metrics['dimensions']['kategori_iklan']['tmk'] ?? [],
                    ],
                    [
                        'key' => 'pembuat_iklan',
                        'title' => 'Distribusi Berdasarkan Pembuat Iklan',
                        'subtitle' => 'Pelaku Usaha dan Perseorangan',
                        'canvasId' => 'dimPembuatIklanChart',
                        'printImgId' => 'printDimPembuatIklanImg',
                        'labels' => $metrics['dimensions']['pembuat_iklan']['labels'] ?? ['Pelaku Usaha', 'Perseorangan'],
                        'total' => $metrics['dimensions']['pembuat_iklan']['total'] ?? [],
                        'mk' => $metrics['dimensions']['pembuat_iklan']['mk'] ?? [],
                        'tmk' => $metrics['dimensions']['pembuat_iklan']['tmk'] ?? [],
                    ],
                ];
            @endphp

            <div class="grid-row-4 print-distribusi-grid">
                @foreach ($distribusiCards as $card)
                    <div class="dash-card exec-card p-5 flex flex-col justify-between hover:border-slate-300 transition-all">
                        <div>
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                        {{ $card['title'] }}
                                    </h4>
                                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $card['subtitle'] }}</p>
                                </div>
                            </div>

                            <!-- Canvas Grafik Stacked Bar -->
                            <div class="my-3 relative print-distribusi-canvas-wrap" style="position: relative; width: 100%; max-width: 100%; height: 200px;">
                                <canvas id="{{ $card['canvasId'] }}" class="screen-chart-canvas"></canvas>
                                <img id="{{ $card['printImgId'] }}" class="print-chart-snapshot" alt="{{ $card['title'] }}">
                            </div>
                        </div>

                        <!-- Accordion Tabel Matriks Data (Default Open) -->
                        <div x-data="{ openTable: true }" class="pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="openTable = !openTable"
                                class="w-full flex items-center justify-between text-[11px] font-bold text-slate-600 hover:text-slate-900 transition-colors py-1 cursor-pointer no-print"
                            >
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span>Tabel Matriks Data</span>
                                </span>
                                <span class="text-slate-400 text-[10px] flex items-center gap-1">
                                    <span x-text="openTable ? 'Sembunyikan' : 'Tampilkan'"></span>
                                    <svg class="w-3.5 h-3.5 transition-transform" :class="openTable ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>

                            <!-- Table Element (Always visible in print) -->
                            <div x-show="openTable" class="mt-2.5 overflow-x-auto print:block!">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-50/80 text-[10px] uppercase font-bold text-slate-500">
                                            <th scope="col" class="py-2 px-2.5">Kategori</th>
                                            <th scope="col" class="py-2 px-2 text-center">Total</th>
                                            <th scope="col" class="py-2 px-2 text-center text-slate-900 font-bold">MK (n &amp; %)</th>
                                            <th scope="col" class="py-2 px-2 text-center text-slate-900 font-bold">TMK (n &amp; %)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-[11px]">
                                        @foreach ($card['labels'] as $idx => $label)
                                            @php
                                                $tot = (int) ($card['total'][$idx] ?? 0);
                                                $mk = (int) ($card['mk'][$idx] ?? 0);
                                                $tmk = (int) ($card['tmk'][$idx] ?? 0);
                                                $persenMk = $tot > 0 ? round(($mk / $tot) * 100, 1) : 0;
                                                $persenTmk = $tot > 0 ? round(($tmk / $tot) * 100, 1) : 0;
                                            @endphp
                                            <tr class="hover:bg-slate-50/70 transition-colors">
                                                <td class="py-1.5 px-2.5 font-semibold text-slate-800 truncate max-w-[120px]" title="{{ $label }}">
                                                    {{ $label }}
                                                </td>
                                                <td class="py-1.5 px-2 text-center font-bold text-slate-800">
                                                    {{ $formatRibuan($tot) }}
                                                </td>
                                                <td class="py-1.5 px-2 text-center text-slate-900 font-bold">
                                                    {{ $formatRibuan($mk) }} <span class="text-[9.5px] text-slate-600 font-medium">({{ $persenMk }}%)</span>
                                                </td>
                                                <td class="py-1.5 px-2 text-center text-slate-900 font-bold">
                                                    {{ $formatRibuan($tmk) }} <span class="text-[9.5px] text-slate-600 font-medium">({{ $persenTmk }}%)</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Modal Panduan Alur (Tetap Dipertahankan) -->
        <x-modal name="guideModal" maxWidth="3xl">
            <div class="p-6 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div class="flex items-center gap-3">
                        @if (file_exists(public_path('assets/BADAN_POM.png')))
                            <img src="{{ asset('assets/BADAN_POM.png') }}" alt="Logo BPOM" class="h-10 w-auto object-contain">
                        @elseif (file_exists(public_path('assets/logo-bpom.jpeg')))
                            <img src="{{ asset('assets/logo-bpom.jpeg') }}" alt="Logo BPOM" class="h-10 w-auto object-contain">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-[#003B7A] text-white flex items-center justify-center font-bold text-sm">
                                BPOM
                            </div>
                        @endif
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Panduan Alur Sistem Pengolahan Data Iklan</h3>
                            <p class="text-xs text-slate-500">Direktorat Pengawasan Obat Tradisional dan Suplemen Kesehatan</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'guideModal')"
                        class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <div class="space-y-4 text-xs text-slate-700 leading-relaxed">
                    <p>
                        Dashboard ini menampilkan hasil visualisasi data pengawasan iklan obat tradisional dan suplemen kesehatan yang bersumber dari sistem SIPT. Hanya data yang telah berstatus <strong>Final</strong> yang diikutsertakan dalam penghitungan metrik kepatuhan (MK/TMK).
                    </p>
                    <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-blue-900">
                        <h4 class="font-bold text-xs">Ketentuan Status &amp; Nilai Target IKU:</h4>
                        <ul class="list-disc pl-5 mt-1.5 space-y-1 text-[11px]">
                            <li><strong>Obat Bahan Alam (OBA)</strong>: Meliputi data komoditi Obat Tradisional (OT) dan Obat Kuasi (OK). Target IKU: <strong>67%</strong>.</li>
                            <li><strong>Suplemen Kesehatan (SK)</strong>: Target IKU: <strong>83%</strong>.</li>
                            <li>Data dengan status menunggu evaluasi tidak dihitung ke dalam rasio MK/TMK namun tetap dicatat kuantitasnya.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </x-modal>
    </div>

    <!-- Chart.js and Alpine Component Script -->
    @push('scripts')
    <script>
        if (window.Chart) {
            Chart.defaults.font.family = "'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif";
            Chart.defaults.color = '#64748B';
            if (Chart.defaults.plugins?.tooltip) {
                Chart.defaults.plugins.tooltip.backgroundColor = '#0F172A';
                Chart.defaults.plugins.tooltip.titleColor = '#FFFFFF';
                Chart.defaults.plugins.tooltip.bodyColor = '#F8FAFC';
                Chart.defaults.plugins.tooltip.borderColor = '#334155';
                Chart.defaults.plugins.tooltip.borderWidth = 1;
                Chart.defaults.plugins.tooltip.padding = 10;
                Chart.defaults.plugins.tooltip.cornerRadius = 8;
                Chart.defaults.plugins.tooltip.boxPadding = 4;
            }
        }

        let monthlyTrendChartInstance = null;
        let topTmkChartInstance = null;
        let topUptTmkChartInstance = null;
        let dimAsalProdukChartInstance = null;
        let dimKategoriMediaChartInstance = null;
        let dimKategoriIklanChartInstance = null;
        let dimPembuatIklanChartInstance = null;
        let mkTmkPieChartInstance = null;

        function getActiveDashboardCharts() {
            return [
                mkTmkPieChartInstance,
                topUptTmkChartInstance,
                monthlyTrendChartInstance,
                topTmkChartInstance,
                dimAsalProdukChartInstance,
                dimKategoriMediaChartInstance,
                dimKategoriIklanChartInstance,
                dimPembuatIklanChartInstance
            ].filter(Boolean);
        }

        let isPreparingPrint = false;

        function prepareChartsForPrint() {
            if (isPreparingPrint) return;
            isPreparingPrint = true;

            const originalDpr = window.Chart?.defaults?.devicePixelRatio ?? window.devicePixelRatio ?? 1;
            if (window.Chart?.defaults) {
                window.Chart.defaults.devicePixelRatio = 2;
            }

            document.body.classList.add('preparing-print-snapshot');
            // Paksa reflow layout agar ukuran kontainer print snapshot aktif sebelum Chart.js resize
            void document.body.offsetHeight;

            getActiveDashboardCharts().forEach(chart => {
                try {
                    chart.resize();
                    chart.update('none');
                    const canvas = chart.canvas;
                    if (!canvas || !canvas.parentNode) return;

                    // Gunakan scale 2 (sama dengan DPR=2) untuk ketajaman optimal
                    const snapshotDataUrl = chart.toBase64Image('image/png', 1.0);
                    const wrap = canvas.closest('.print-trend-canvas-wrap, .print-tmk-canvas-wrap, .print-distribusi-canvas-wrap, .print-pie-canvas-wrap, .print-top-upt-canvas-wrap');
                    if (wrap) {
                        const img = wrap.querySelector('.print-chart-snapshot');
                        if (img) {
                            img.src = snapshotDataUrl;
                        }
                    }
                } catch (e) {
                    console.warn('Gagal menyiapkan snapshot chart print:', e);
                }
            });

            // Tuliskan waktu cetak real-time WIB ke metadata kop
            const tsElem = document.getElementById('printTimestamp');
            if (tsElem) {
                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                const tgl = `${pad(now.getDate())}/${pad(now.getMonth() + 1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())} WIB`;
                tsElem.textContent = tgl;
            }

            // Kembalikan DPR ke nilai semula setelah dialog cetak ditutup
            function cleanupAfterPrint() {
                window.removeEventListener('afterprint', cleanupAfterPrint);
                isPreparingPrint = false;
                document.body.classList.remove('preparing-print-snapshot');
                if (window.Chart?.defaults) {
                    window.Chart.defaults.devicePixelRatio = originalDpr;
                }
                getActiveDashboardCharts().forEach(c => {
                    try {
                        c.resize();
                        c.update('none');
                    } catch (e) {}
                });
            }

            window.addEventListener('afterprint', cleanupAfterPrint);
        }

        window.addEventListener('beforeprint', prepareChartsForPrint);

        function exportDashboardPdf() {
            prepareChartsForPrint();
            // Berikan jeda 250ms agar browser merender base64 image secara tajam sebelum dialog print aktif
            setTimeout(() => {
                window.print();
            }, 250);
        }

        window.exportDashboardPdf = exportDashboardPdf;

        function dashboardAnalytics(config) {
            return {
                showGuideModal: false,

                init() {
                    this.$nextTick(() => {
                        this.initMkTmkPieChart();
                        this.initTopUptTmkChart();
                        this.initMonthlyTrendChart();
                        this.initTopTmkChart();
                        this.initAsalProdukChart();
                        this.initKategoriMediaChart();
                        this.initKategoriIklanChart();
                        this.initPembuatIklanChart();
                    });
                },

                initTopUptTmkChart() {
                    const ctx = document.getElementById('topUptTmkChart');
                    if (!ctx) return;
                    if (topUptTmkChartInstance) {
                        topUptTmkChartInstance.destroy();
                    }

                    const uptData = config.topUptTmkData || {};
                    const labels = uptData.labels || [];
                    const values = uptData.values || [];
                    const percentages = uptData.percentages || [];
                    const totals = uptData.totals || [];

                    const bgColors = values.map((_, idx) => idx < 3 ? '#DC2626' : '#F87171');
                    const hoverBgColors = values.map((_, idx) => idx < 3 ? '#B91C1C' : '#EF4444');

                    topUptTmkChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Kasus TMK',
                                data: values,
                                backgroundColor: bgColors,
                                hoverBackgroundColor: hoverBgColors,
                                borderRadius: 4,
                                maxBarThickness: 20
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    right: 110,
                                    top: 4,
                                    bottom: 4
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        title: function (tooltipItems) {
                                            const idx = tooltipItems[0].dataIndex;
                                            return labels[idx] || '';
                                        },
                                        label: function (context) {
                                            const idx = context.dataIndex;
                                            const val = values[idx] || 0;
                                            const tot = totals[idx] || 0;
                                            const pct = percentages[idx] !== undefined ? Number(percentages[idx]).toFixed(1).replace('.', ',') : '0,0';
                                            return [
                                                ` Kasus TMK: ${val.toLocaleString('id-ID')} kasus`,
                                                ` Total Dievaluasi: ${tot.toLocaleString('id-ID')} iklan`,
                                                ` Rasio TMK: ${pct}%`
                                            ];
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    suggestedMax: 10,
                                    ticks: {
                                        font: { size: 10.5 },
                                        precision: 0,
                                        stepSize: 2,
                                        color: '#64748B'
                                    },
                                    grid: { color: '#F1F5F9' }
                                },
                                y: {
                                    ticks: {
                                        autoSkip: false,
                                        font: { size: 11, weight: '600' },
                                        color: '#1E293B'
                                    },
                                    grid: { display: false }
                                }
                            }
                        },
                        plugins: [{
                            id: 'uptBarEndLabels',
                            afterDatasetsDraw(chart) {
                                const { ctx } = chart;
                                const meta = chart.getDatasetMeta(0);
                                if (!meta || meta.hidden) return;
                                meta.data.forEach((element, index) => {
                                    const val = values[index];
                                    if (val === undefined || val === null || val <= 0) return;
                                    const pct = percentages[index] !== undefined ? Number(percentages[index]).toFixed(1).replace('.', ',') : '0,0';
                                    const text = `${val.toLocaleString('id-ID')} kasus (${pct}%)`;
                                    ctx.save();
                                    ctx.font = '600 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                                    ctx.fillStyle = index < 3 ? '#991B1B' : '#475569';
                                    ctx.textAlign = 'left';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(text, element.x + 8, element.y);
                                    ctx.restore();
                                });
                            }
                        }]
                    });
                },

                initMkTmkPieChart() {
                    const ctx = document.getElementById('mkTmkPieChart');
                    if (!ctx) return;
                    if (mkTmkPieChartInstance) {
                        mkTmkPieChartInstance.destroy();
                    }

                    const mk = Number(config.kpiData?.mk ?? 0);
                    const tmk = Number(config.kpiData?.tmk ?? 0);
                    const total = mk + tmk;

                    const hasData = total > 0;
                    // Zero-state handling: cincin abu-abu (#E2E8F0) jika total 0
                    const labels = hasData ? ['Memenuhi (MK)', 'Tidak Memenuhi (TMK)'] : ['Belum Ada Data'];
                    const dataValues = hasData ? [mk, tmk] : [1];
                    const bgColors = hasData ? ['#16A34A', '#DC2626'] : ['#E2E8F0'];
                    const hoverBgColors = hasData ? ['#15803D', '#B91C1C'] : ['#CBD5E1'];

                    mkTmkPieChartInstance = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: dataValues,
                                backgroundColor: bgColors,
                                hoverBackgroundColor: hoverBgColors,
                                borderWidth: 2,
                                borderColor: '#FFFFFF',
                                hoverOffset: hasData ? 8 : 0
                            }]
                        },
                        options: {
                            responsive: false,
                            maintainAspectRatio: false,
                            cutout: '74%',
                            onHover: (event, chartElement) => {
                                if (event.native && event.native.target) {
                                    event.native.target.style.cursor = (hasData && chartElement.length) ? 'pointer' : 'default';
                                }
                            },
                            onClick: (event, elements) => {
                                if (!hasData || !elements.length) return;
                                const elementIndex = elements[0].index;
                                // Index 0: MK (hasil_evaluasi = 1), Index 1: TMK (hasil_evaluasi = 2)
                                const hasilEvaluasi = elementIndex === 0 ? 1 : 2;

                                const targetUrl = new URL(config.dataTersimpanUrl, window.location.origin);
                                targetUrl.searchParams.set('tahun', config.tahun);
                                if (config.komoditi && config.komoditi !== 'SEMUA') {
                                    targetUrl.searchParams.set('komoditi', config.komoditi);
                                }
                                if (config.upt) {
                                    targetUrl.searchParams.set('upt', config.upt);
                                }
                                targetUrl.searchParams.set('hasil_evaluasi', hasilEvaluasi);

                                window.location.href = targetUrl.toString();
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: hasData,
                                    callbacks: {
                                        label: function (context) {
                                            const val = context.raw || 0;
                                            const pct = total > 0 ? ((val / total) * 100).toFixed(2).replace('.', ',') : '0,00';
                                            return [
                                                ` ${context.label}: ${val.toLocaleString('id-ID')} iklan (${pct}%)`,
                                                ` ➜ Klik untuk melihat rincian data`
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    });
                },

                initMonthlyTrendChart() {
                    const ctx = document.getElementById('monthlyTrendChart');
                    if (!ctx) return;
                    if (monthlyTrendChartInstance) {
                        monthlyTrendChartInstance.destroy();
                    }

                    const mData = config.monthlyData || {};
                    const labels = mData.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

                    monthlyTrendChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Realisasi OBA (% MK)',
                                    data: mData.oba || [],
                                    borderColor: '#2563EB',
                                    backgroundColor: '#FFFFFF',
                                    pointBorderColor: '#2563EB',
                                    pointBackgroundColor: '#FFFFFF',
                                    pointBorderWidth: 2,
                                    borderWidth: 2.5,
                                    pointRadius: 3.5,
                                    pointHoverRadius: 6,
                                    tension: 0.3,
                                    spanGaps: true
                                },
                                {
                                    label: 'Realisasi OK (% MK)',
                                    data: mData.ok || [],
                                    borderColor: '#D97706',
                                    backgroundColor: '#FFFFFF',
                                    pointBorderColor: '#D97706',
                                    pointBackgroundColor: '#FFFFFF',
                                    pointBorderWidth: 2,
                                    borderWidth: 2.5,
                                    pointRadius: 3.5,
                                    pointHoverRadius: 6,
                                    tension: 0.3,
                                    spanGaps: true
                                },
                                {
                                    label: 'Realisasi SK (% MK)',
                                    data: mData.sk || [],
                                    borderColor: '#059669',
                                    backgroundColor: '#FFFFFF',
                                    pointBorderColor: '#059669',
                                    pointBackgroundColor: '#FFFFFF',
                                    pointBorderWidth: 2,
                                    borderWidth: 2.5,
                                    pointRadius: 3.5,
                                    pointHoverRadius: 6,
                                    tension: 0.3,
                                    spanGaps: true
                                },
                                {
                                    label: 'Gabungan (% MK)',
                                    data: mData.gabungan || [],
                                    borderColor: '#475569',
                                    backgroundColor: '#FFFFFF',
                                    pointBorderColor: '#475569',
                                    pointBackgroundColor: '#FFFFFF',
                                    pointBorderWidth: 1.5,
                                    borderWidth: 2,
                                    borderDash: [4, 4],
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                    tension: 0.3,
                                    spanGaps: true
                                },
                                {
                                    label: 'Target OBA (67%)',
                                    data: mData.target_oba || [],
                                    borderColor: '#94A3B8',
                                    borderWidth: 1.5,
                                    borderDash: [6, 4],
                                    pointRadius: 0,
                                    fill: false
                                },
                                {
                                    label: 'Target SK (83%)',
                                    data: mData.target_sk || [],
                                    borderColor: '#CBD5E1',
                                    borderWidth: 1.5,
                                    borderDash: [3, 3],
                                    pointRadius: 0,
                                    fill: false
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            const val = context.raw;
                                            if (val === null || val === undefined) return ` ${context.dataset.label}: (Tidak ada data final)`;
                                            return ` ${context.dataset.label}: ${Number(val).toFixed(2).replace('.', ',')}%`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    min: 0,
                                    max: 100,
                                    ticks: {
                                        stepSize: 20,
                                        callback: function (val) {
                                            return val + '%';
                                        },
                                        font: { size: 10.5 },
                                        color: '#64748B'
                                    },
                                    grid: {
                                        color: '#F1F5F9'
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: { size: 10.5, weight: '500' },
                                        color: '#475569'
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                },

                initTopTmkChart() {
                    const ctx = document.getElementById('topTmkChart');
                    if (!ctx) return;
                    if (topTmkChartInstance) {
                        topTmkChartInstance.destroy();
                    }

                    const tmk = config.topTmkData || {};
                    const labels = tmk.labels || [];
                    const values = tmk.values || [];
                    const percentages = tmk.percentages || [];

                    const bgColors = values.map((_, idx) => idx === 0 ? '#DC2626' : (idx < 3 ? '#EF4444' : '#F87171'));
                    const hoverBgColors = values.map((_, idx) => idx === 0 ? '#B91C1C' : (idx < 3 ? '#DC2626' : '#EF4444'));

                    topTmkChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Kasus TMK',
                                data: values,
                                backgroundColor: bgColors,
                                hoverBackgroundColor: hoverBgColors,
                                borderRadius: 4,
                                maxBarThickness: 20
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    right: 95,
                                    top: 4,
                                    bottom: 4
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            const idx = context.dataIndex;
                                            const val = values[idx] || 0;
                                            const pct = percentages[idx] ? Number(percentages[idx]).toFixed(2).replace('.', ',') : '0,00';
                                            return ` ${val.toLocaleString('id-ID')} kasus (${pct}% dari total TMK)`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    suggestedMax: 10,
                                    ticks: {
                                        font: { size: 10.5 },
                                        precision: 0,
                                        stepSize: 2,
                                        color: '#64748B'
                                    },
                                    grid: { color: '#F1F5F9' }
                                },
                                y: {
                                    ticks: {
                                        font: { size: 11, weight: '600' },
                                        color: '#0F172A',
                                        autoSkip: false
                                    },
                                    grid: { display: false }
                                }
                            }
                        },
                        plugins: [{
                            id: 'tmkBarEndLabels',
                            afterDatasetsDraw(chart) {
                                const { ctx } = chart;
                                const meta = chart.getDatasetMeta(0);
                                if (!meta || meta.hidden) return;
                                meta.data.forEach((element, index) => {
                                    const val = values[index];
                                    if (val === undefined || val === null || val <= 0) return;
                                    const pct = percentages[index] !== undefined ? Number(percentages[index]).toFixed(1).replace('.', ',') : '0,0';
                                    const text = `${val.toLocaleString('id-ID')} (${pct}%)`;
                                    ctx.save();
                                    ctx.font = '600 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
                                    ctx.fillStyle = index === 0 ? '#991B1B' : '#475569';
                                    ctx.textAlign = 'left';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(text, element.x + 8, element.y);
                                    ctx.restore();
                                });
                            }
                        }]
                    });
                },

                initAsalProdukChart() {
                    this.initDimensionStackedBar(
                        'dimAsalProdukChart',
                        dimAsalProdukChartInstance,
                        inst => { dimAsalProdukChartInstance = inst; },
                        config.dimensionsData?.asal_produk
                    );
                },

                initKategoriMediaChart() {
                    this.initDimensionStackedBar(
                        'dimKategoriMediaChart',
                        dimKategoriMediaChartInstance,
                        inst => { dimKategoriMediaChartInstance = inst; },
                        config.dimensionsData?.kategori_media
                    );
                },

                initKategoriIklanChart() {
                    this.initDimensionStackedBar(
                        'dimKategoriIklanChart',
                        dimKategoriIklanChartInstance,
                        inst => { dimKategoriIklanChartInstance = inst; },
                        config.dimensionsData?.kategori_iklan
                    );
                },

                initPembuatIklanChart() {
                    this.initDimensionStackedBar(
                        'dimPembuatIklanChart',
                        dimPembuatIklanChartInstance,
                        inst => { dimPembuatIklanChartInstance = inst; },
                        config.dimensionsData?.pembuat_iklan
                    );
                },

                initDimensionStackedBar(canvasId, currentInstance, setInstance, dimData) {
                    const ctx = document.getElementById(canvasId);
                    if (!ctx) return;
                    if (currentInstance) {
                        currentInstance.destroy();
                    }

                    const labels = dimData?.labels || [];
                    const total = dimData?.total || [];
                    const mk = dimData?.mk || [];
                    const tmk = dimData?.tmk || [];

                    // Hitung persentase stacked 100%
                    const mkPct = [];
                    const tmkPct = [];
                    for (let i = 0; i < labels.length; i++) {
                        const tot = total[i] || 0;
                        mkPct.push(tot > 0 ? (mk[i] / tot) * 100 : 0);
                        tmkPct.push(tot > 0 ? (tmk[i] / tot) * 100 : 0);
                    }

                    const inst = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'MK (%)',
                                    data: mkPct,
                                    backgroundColor: '#16A34A',
                                    hoverBackgroundColor: '#15803D',
                                    borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4 },
                                    borderColor: '#FFFFFF',
                                    borderWidth: 1.5,
                                    maxBarThickness: 34
                                },
                                {
                                    label: 'TMK (%)',
                                    data: tmkPct,
                                    backgroundColor: '#DC2626',
                                    hoverBackgroundColor: '#B91C1C',
                                    borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                                    borderColor: '#FFFFFF',
                                    borderWidth: 1.5,
                                    maxBarThickness: 34
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            const idx = context.dataIndex;
                                            const isMk = context.datasetIndex === 0;
                                            const val = isMk ? (mk[idx] || 0) : (tmk[idx] || 0);
                                            const pct = context.raw ? context.raw.toFixed(1).replace('.', ',') : '0,0';
                                            const tot = total[idx] || 0;
                                            return ` ${context.dataset.label}: ${pct}% (${val.toLocaleString('id-ID')} dari ${tot.toLocaleString('id-ID')} iklan)`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                    ticks: {
                                        font: { size: 10.5, weight: '600' },
                                        color: '#334155',
                                        autoSkip: false
                                    },
                                    grid: { display: false }
                                },
                                y: {
                                    stacked: true,
                                    min: 0,
                                    max: 100,
                                    ticks: {
                                        stepSize: 25,
                                        callback: function (val) {
                                            return val + '%';
                                        },
                                        font: { size: 10.5 },
                                        color: '#64748B'
                                    },
                                    grid: { color: '#F1F5F9' }
                                }
                            }
                        }
                    });

                    setInstance(inst);
                }
            };
        }
    </script>
    @endpush
</x-layouts.app>
