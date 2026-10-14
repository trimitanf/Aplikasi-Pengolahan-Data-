<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Sistem Pengolahan Data Iklan OTSK BPOM</title>

    <!-- Favicon -->
    @if (file_exists(public_path('images/logo-bpom.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/logo-bpom.png') }}">
    @elseif (file_exists(public_path('images/logo-bpom.svg')))
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-bpom.svg') }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-(--bg) font-sans antialiased text-(--text)">
    <div class="min-h-full flex flex-col md:flex-row">
        <!-- Left Side: Brand Visual & Info -->
        <div
            class="hidden md:flex md:w-1/2 lg:w-5/12 p-12 flex-col justify-between text-white relative overflow-hidden shadow-2xl"
            style="background-image: var(--grad-brand);"
        >
            <!-- Decorative Accent circles -->
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-emerald-400/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-3.5 mb-10">
                    @if (file_exists(public_path('images/logo-bpom.png')))
                        <img src="{{ asset('images/logo-bpom.png') }}" alt="Logo BPOM" class="h-12 w-auto bg-white/10 p-2 rounded-xl">
                    @elseif (file_exists(public_path('images/logo-bpom.svg')))
                        <img src="{{ asset('images/logo-bpom.svg') }}" alt="Logo BPOM" class="h-12 w-auto bg-white/10 p-2 rounded-xl">
                    @else
                        <div class="h-12 w-12 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center font-bold text-white text-lg tracking-wider shadow-inner">
                            BPOM
                        </div>
                    @endif
                    <div>
                        <h2 class="text-base font-bold tracking-tight text-white leading-tight">BADAN POM RI</h2>
                        <p class="text-xs text-blue-200">Direktorat Pengawasan OTSK</p>
                    </div>
                </div>

                <div class="space-y-4 max-w-md">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/15 text-blue-100 border border-white/20">
                        Tim Pengolahan Iklan
                    </span>
                    <h1 class="text-3xl font-extrabold tracking-tight text-white leading-snug">
                        Sistem Informasi Pengolahan Data Iklan
                    </h1>
                    <p class="text-sm text-blue-100/90 leading-relaxed">
                        Platform otomatisasi konversi data mentah SIPT, standardisasi kode numerik, evaluasi pengawasan iklan, serta pelaporan capaian IKU Obat Bahan Alam dan Suplemen Kesehatan.
                    </p>
                </div>
            </div>

            <!-- Left Footer Information -->
            <div class="relative z-10 pt-8 border-t border-white/15 text-xs text-blue-200/80 flex items-center justify-between">
                <span>&copy; {{ date('Y') }} Badan Pengawas Obat dan Makanan</span>
                <span>Role: Evaluator</span>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex-1 flex items-center justify-center p-6 sm:p-12 lg:p-16">
            <div class="w-full max-w-md space-y-8">
                <!-- Mobile Logo Header -->
                <div class="md:hidden text-center space-y-2">
                    <div class="inline-flex h-12 w-12 rounded-2xl bg-(--brand-navy) text-white items-center justify-center font-bold text-lg mb-2 shadow-md">
                        BPOM
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Sistem Pengolahan Data Iklan</h2>
                    <p class="text-xs text-gray-500">Direktorat Pengawasan OTSK</p>
                </div>

                <!-- Card Container -->
                <div class="bg-white p-8 rounded-2xl border border-(--border) shadow-md space-y-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight">Masuk ke Sistem</h3>
                        <p class="text-xs text-gray-500 mt-1">Silakan masukkan username dan password evaluator Anda</p>
                    </div>

                    @if (session('status'))
                        <x-alert type="info">
                            {{ session('status') }}
                        </x-alert>
                    @endif

                    @if ($errors->has('username') && !old('username'))
                        <x-alert type="error">
                            {{ $errors->first('username') }}
                        </x-alert>
                    @endif

                    <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                        @csrf

                        <!-- Username Input -->
                        <x-input
                            label="Username"
                            name="username"
                            type="text"
                            placeholder="Contoh: evaluator"
                            required
                            autofocus
                        />

                        <!-- Password Input -->
                        <x-input
                            label="Password"
                            name="password"
                            type="password"
                            placeholder="••••••••"
                            required
                        />

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                                <span class="text-xs text-gray-600 font-medium">Ingat sesi saya</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <x-button
                                type="submit"
                                variant="primary"
                                size="lg"
                                class="w-full font-semibold shadow-md"
                            >
                                <span>Masuk ke Akun</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </x-button>
                        </div>
                    </form>

                    <div class="pt-2 text-center text-xs text-gray-400 border-t border-gray-100">
                        Akses dibatasi khusus Tim Pengolahan Iklan OTSK BPOM
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
