<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Sistem Pengolahan Data Iklan' }} - BPOM OTSK</title>

    <!-- Favicon -->
    @if (file_exists(public_path('images/logo-bpom.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/logo-bpom.png') }}">
    @elseif (file_exists(public_path('images/logo-bpom.svg')))
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-bpom.svg') }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes pingDot {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }
        .animate-ping-dot {
            animation: pingDot 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @media print {
            html, body, .h-screen, .h-full, .min-h-full, .flex-1, main {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
            }
            aside, header {
                display: none !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-(--bg) font-sans antialiased text-(--text) overflow-hidden">
    <div x-data="{ mobileMenuOpen: false }" @keydown.escape.window="mobileMenuOpen = false" class="h-screen w-full flex flex-col md:flex-row overflow-hidden relative">
        <!-- Mobile Sidebar Backdrop Overlay -->
        <div
            x-show="mobileMenuOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false"
            class="fixed inset-0 z-30 bg-slate-900/60 backdrop-blur-xs md:hidden no-print"
        ></div>

        <!-- Sidebar Navigation (Off-canvas drawer on mobile, static full-height on desktop) -->
        <aside
            :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 text-white flex flex-col shadow-xl h-screen md:static md:sticky md:top-0 overflow-y-auto transition-transform duration-200 ease-in-out"
            style="background-image: var(--grad-brand);"
        >
            <!-- Logo & Brand Header -->
            <div class="p-6 border-b border-white/15 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3.5 min-w-0">
                    @if (file_exists(public_path('assets/BADAN_POM.png')))
                        <img src="{{ asset('assets/BADAN_POM.png') }}" alt="Logo BPOM" class="h-12 w-auto shrink-0 object-contain drop-shadow-md">
                    @elseif (file_exists(public_path('images/logo-bpom.png')))
                        <img src="{{ asset('images/logo-bpom.png') }}" alt="Logo BPOM" class="h-12 w-auto shrink-0 object-contain drop-shadow-md">
                    @elseif (file_exists(public_path('assets/logo-bpom.jpg')))
                        <img src="{{ asset('assets/logo-bpom.jpg') }}" alt="Logo BPOM" class="h-12 w-auto shrink-0 object-contain">
                    @elseif (file_exists(public_path('assets/logo-bpom.jpeg')))
                        <img src="{{ asset('assets/logo-bpom.jpeg') }}" alt="Logo BPOM" class="h-12 w-auto shrink-0 object-contain">
                    @else
                        <div class="h-11 w-11 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center font-bold text-white text-base tracking-wider shrink-0 shadow-inner">
                            BPOM
                        </div>
                    @endif
                    <div class="truncate">
                        <h1 class="text-sm font-bold tracking-tight text-white leading-tight">Pengawasan OTSK</h1>
                        @if (request()->routeIs('penandaan*') || (auth()->check() && auth()->user()->isEvaluatorPenandaan()))
                            <p class="text-[11px] text-blue-200/90 font-medium">Pengawasan Penandaan</p>
                        @else
                            <p class="text-[11px] text-blue-200/90 font-medium">Pengolahan Data Iklan</p>
                        @endif
                    </div>
                </div>
                <!-- Close Drawer Button (Mobile Only) -->
                <button
                    type="button"
                    @click="mobileMenuOpen = false"
                    class="md:hidden p-1.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                    aria-label="Tutup Menu Navigasi"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                @php
                    $user = auth()->user();
                    $isAdmin = $user && $user->isAdmin();
                    $canAccessIklan = $user && $user->canAccessIklan();
                    $canAccessPenandaan = $user && $user->canAccessPenandaan();
                    $isTrashActive = request()->routeIs('dataset.trash*');
                    $isDatasetActive = request()->routeIs('dataset*') && !request()->routeIs('dataset.standardisasi*') && !$isTrashActive;
                    $trashCount = $trashCount ?? 0;
                @endphp

                {{-- SEKSI 1: PENGAWASAN IKLAN --}}
                @if ($canAccessIklan)
                    <!-- Dashboard -->
                    <a
                        href="{{ route('dashboard') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('dashboard*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('dashboard*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- Dataset Iklan (Dropdown untuk Admin, Direct Link untuk Evaluator) -->
                    @if ($isAdmin)
                        <div x-data="{ openDataset: {{ ($isDatasetActive || $isTrashActive) ? 'true' : 'false' }} }" class="space-y-1">
                            <button
                                type="button"
                                @click="openDataset = !openDataset"
                                @class([
                                    'w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 cursor-pointer',
                                    'bg-white/20 text-white shadow-xs font-semibold' => $isDatasetActive || $isTrashActive,
                                    'text-blue-100/90 hover:bg-white/10 hover:text-white' => !($isDatasetActive || $isTrashActive),
                                ])
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>Dataset</span>
                                </div>
                                <svg class="w-4 h-4 transition-transform duration-200" :class="openDataset ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="openDataset" class="pl-9 pr-2 space-y-1 py-1">
                                <a
                                    href="{{ route('dataset.index') }}"
                                    @class([
                                        'flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                        'bg-white/20 text-white font-semibold' => $isDatasetActive,
                                        'text-blue-200 hover:text-white hover:bg-white/10' => !$isDatasetActive,
                                    ])
                                >
                                    <span>Daftar Dataset</span>
                                </a>
                                <a
                                    href="{{ route('dataset.trash') }}"
                                    @class([
                                        'flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                        'bg-white/20 text-white font-semibold' => $isTrashActive,
                                        'text-blue-200 hover:text-white hover:bg-white/10' => !$isTrashActive,
                                    ])
                                >
                                    <span>Kotak Sampah</span>
                                    @if ($trashCount > 0)
                                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-red-500 text-white">
                                            {{ $trashCount }}
                                        </span>
                                    @endif
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Direct Link untuk Evaluator: Langsung ke Daftar Dataset -->
                        <a
                            href="{{ route('dataset.index') }}"
                            @class([
                                'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                                'bg-white/20 text-white shadow-xs font-semibold' => $isDatasetActive,
                                'text-blue-100/90 hover:bg-white/10 hover:text-white' => !$isDatasetActive,
                            ])
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Dataset</span>
                        </a>
                    @endif

                    <!-- Standarisasi Dataset (per-dataset) -->
                    <a
                        href="{{ route('standarisasi.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('standarisasi*') || request()->routeIs('dataset.standardisasi*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !(request()->routeIs('standarisasi*') || request()->routeIs('dataset.standardisasi*')),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span>Standarisasi Dataset</span>
                    </a>

                    <!-- Data Terstandarisasi (Master Terpadu Semua UPT) -->
                    <a
                        href="{{ route('data-tersimpan.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('data-tersimpan*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('data-tersimpan*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span>Data Terstandarisasi</span>
                    </a>

                    <!-- Rekap Iklan -->
                    <a
                        href="{{ route('rekap.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('rekap*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('rekap*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Rekap</span>
                    </a>
                @endif

                {{-- SEKSI 2: PENGAWASAN PENANDAAN (Disembunyikan untuk Admin) --}}
                @if ($canAccessPenandaan && !$isAdmin)
                    <!-- Dashboard Penandaan -->
                    <a
                        href="{{ route('penandaan.dashboard') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('penandaan.dashboard*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('penandaan.dashboard*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    <!-- Dataset Penandaan -->
                    <a
                        href="{{ route('penandaan.dataset.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('penandaan.dataset*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('penandaan.dataset*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>Dataset</span>
                    </a>

                    <!-- Standarisasi Penandaan -->
                    <a
                        href="{{ route('penandaan.standarisasi.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('penandaan.standarisasi*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('penandaan.standarisasi*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span>Data Terstandarisasi</span>
                    </a>

                    <!-- Rekap Penandaan -->
                    <a
                        href="{{ route('penandaan.rekap.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('penandaan.rekap*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('penandaan.rekap*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Rekap</span>
                    </a>
                @endif

                {{-- SEKSI 3: MENU ADMINISTRATOR --}}
                @if ($isAdmin)
                    <!-- Separator Menu Admin -->
                    <div class="pt-4 pb-1">
                        <span class="px-3.5 text-[10px] font-bold uppercase tracking-wider text-blue-200/70">
                            Menu Administrator
                        </span>
                    </div>

                    <!-- Report Evaluator -->
                    <a
                        href="{{ route('report-evaluator.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('report-evaluator*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('report-evaluator*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>Report Evaluator</span>
                    </a>

                    <!-- 6. Master Data -->
                    <a
                        href="{{ route('kamus-kode.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('kamus-kode*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('kamus-kode*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Master Data</span>
                    </a>

                    <!-- 7. Target IKU -->
                    <a
                        href="{{ route('target-iku.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('target-iku*') || request()->routeIs('pengaturan*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('target-iku*') && !request()->routeIs('pengaturan*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Target IKU</span>
                    </a>

                    <!-- 9. Kelola Pengguna -->
                    <a
                        href="{{ route('pengguna.index') }}"
                        @class([
                            'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                            'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('pengguna*'),
                            'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('pengguna*'),
                        ])
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Kelola Pengguna</span>
                    </a>
                @endif
            </nav>

            <!-- Bottom User Profile & Role Info -->
            <div class="p-4 border-t border-white/15 bg-black/10">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-full bg-white/20 border border-white/30 flex items-center justify-center font-bold text-white text-xs shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'E', 0, 1)) }}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'Evaluator' }}</p>
                            <span class="inline-block text-[10px] text-blue-200 uppercase tracking-wider font-medium">
                                {{ str_replace('_', ' ', auth()->user()->role ?? 'evaluator') }}
                            </span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            title="Keluar dari Sistem"
                            class="p-2 rounded-lg text-blue-200 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden" style="min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; overflow-x: hidden;">
            <!-- Topbar Header -->
            <header class="bg-(--surface) border-b border-(--border) shrink-0 sticky top-0 z-20 px-4 sm:px-6 py-2.5 sm:py-3 shadow-2xs">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Hamburger Button (Mobile Only) -->
                        <button
                            type="button"
                            @click="mobileMenuOpen = true"
                            class="md:hidden p-1.5 rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-2xs cursor-pointer shrink-0"
                            aria-label="Buka Menu Navigasi"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <nav class="flex items-center gap-2 text-xs text-(--muted) truncate">
                            <a href="{{ route('dashboard') }}" class="hover:text-(--brand-blue) font-medium">Beranda</a>
                            @if (isset($breadcrumb))
                                <span>/</span>
                                <span class="text-gray-700 font-medium truncate">{{ $breadcrumb }}</span>
                            @endif
                        </nav>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 bg-gray-50/90 text-gray-800 shadow-2xs">
                            <span class="relative flex h-2.5 w-2.5 shrink-0">
                                <span class="animate-ping-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" style="background-color: #34d399;"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500" style="background-color: #10b981;"></span>
                            </span>
                            <span id="live-waktu-header" class="text-xs font-bold text-gray-800 tracking-tight whitespace-nowrap">
                                {{ now()->translatedFormat('d F Y') }}, {{ now()->format('H:i') }} WIB
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Body: Only this container scrolls -->
            <main class="flex-1 p-6 md:p-8 space-y-6 overflow-y-auto min-h-0" style="min-width: 0; width: 100%; max-width: 100%; box-sizing: border-box; overflow-x: hidden;">
                <!-- Page Title (Diposisikan di konten halaman, bukan di header) -->
                @if (isset($header))
                    <div class="no-print">
                        <h1 class="text-xl sm:text-2xl font-bold text-(--text) tracking-tight">
                            {{ $header }}
                        </h1>
                    </div>
                @endif
                <!-- Flash Status Notification -->
                @if (session('status'))
                    <x-alert type="success">
                        {{ session('status') }}
                    </x-alert>
                @endif

                @if (session('warning'))
                    <x-alert type="warning">
                        {{ session('warning') }}
                    </x-alert>
                @endif

                @if (session('info'))
                    <x-alert type="info">
                        {{ session('info') }}
                    </x-alert>
                @endif

                @if (session('error'))
                    <x-alert type="error">
                        {{ session('error') }}
                    </x-alert>
                @endif

                @if (isset($errors) && $errors->any())
                    <x-alert type="error">
                        <div class="font-bold text-xs mb-1">Terdapat kesalahan pengisian formulir:</div>
                        <ul class="list-disc list-inside text-xs space-y-0.5">
                            @foreach ($errors->all() as $errorMsg)
                                <li>{{ $errorMsg }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function updateLiveWaktuHeader() {
                const el = document.getElementById('live-waktu-header');
                if (!el) return;
                const now = new Date();
                const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                const d = now.getDate();
                const m = months[now.getMonth()];
                const y = now.getFullYear();
                const h = String(now.getHours()).padStart(2, '0');
                const min = String(now.getMinutes()).padStart(2, '0');
                el.textContent = `${d} ${m} ${y}, ${h}:${min} WIB`;
            }
            setInterval(updateLiveWaktuHeader, 10000);
        });
    </script>
    @stack('scripts')
</body>
</html>
