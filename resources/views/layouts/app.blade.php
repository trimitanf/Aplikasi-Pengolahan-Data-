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
    @stack('styles')
</head>
<body class="h-full bg-(--bg) font-sans antialiased text-(--text)">
    <div class="min-h-full flex flex-col md:flex-row">
        <!-- Sidebar Navigation -->
        <aside
            class="w-full md:w-64 shrink-0 text-white flex flex-col shadow-xl z-30"
            style="background-image: var(--grad-brand);"
        >
            <!-- Logo & Brand Header -->
            <div class="p-6 border-b border-white/15 flex items-center gap-3">
                @if (file_exists(public_path('images/logo-bpom.png')))
                    <img src="{{ asset('images/logo-bpom.png') }}" alt="Logo BPOM" class="h-10 w-auto bg-white/10 p-1.5 rounded-lg shrink-0">
                @elseif (file_exists(public_path('images/logo-bpom.svg')))
                    <img src="{{ asset('images/logo-bpom.svg') }}" alt="Logo BPOM" class="h-10 w-auto bg-white/10 p-1.5 rounded-lg shrink-0">
                @else
                    <div class="h-10 w-10 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center font-bold text-white text-base tracking-wider shrink-0 shadow-inner">
                        BPOM
                    </div>
                @endif
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-white leading-tight">Pengawasan OTSK</h1>
                    <p class="text-[11px] text-blue-200/80 font-medium">Pengolahan Data Iklan</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1.5">
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

                <a
                    href="{{ route('dataset.index') }}"
                    @class([
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                        'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('dataset*'),
                        'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('dataset*'),
                    ])
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span>Dataset</span>
                </a>

                <a
                    href="{{ route('data-tersimpan.index') }}"
                    @class([
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                        'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('data-tersimpan*'),
                        'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('data-tersimpan*'),
                    ])
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                    <span>Data Tersimpan</span>
                </a>

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

                <a
                    href="{{ route('pengaturan.index') }}"
                    @class([
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150',
                        'bg-white/20 text-white shadow-xs font-semibold' => request()->routeIs('pengaturan*'),
                        'text-blue-100/90 hover:bg-white/10 hover:text-white' => !request()->routeIs('pengaturan*'),
                    ])
                >
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Pengaturan</span>
                </a>
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
                                {{ auth()->user()->role ?? 'evaluator' }}
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
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Topbar Header -->
            <header class="bg-(--surface) border-b border-(--border) sticky top-0 z-20 px-6 py-4 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-(--muted) mb-1">
                            <a href="{{ route('dashboard') }}" class="hover:text-(--brand-blue)">Beranda</a>
                            @if (isset($breadcrumb))
                                <span>/</span>
                                <span class="text-gray-700 font-medium">{{ $breadcrumb }}</span>
                            @endif
                        </div>
                        <h2 class="text-xl font-bold text-(--text) tracking-tight">
                            {{ $header ?? 'Dashboard' }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-badge variant="primary" size="sm">
                            Role: {{ ucfirst(auth()->user()->role ?? 'evaluator') }}
                        </x-badge>
                        <span class="text-xs text-(--muted) border-l border-gray-200 pl-3">
                            {{ now()->translatedFormat('l, d F Y') }}
                        </span>
                    </div>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-6 md:p-8 space-y-6">
                <!-- Flash Status Notification -->
                @if (session('status'))
                    <x-alert type="success">
                        {{ session('status') }}
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

                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
