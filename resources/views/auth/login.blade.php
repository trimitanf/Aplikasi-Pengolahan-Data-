<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Sistem Pengolahan Data Iklan OTSK BPOM</title>

    <!-- Favicon -->
    @if (file_exists(public_path('assets/logo-bpom.jpg')))
        <link rel="icon" type="image/jpeg" href="{{ asset('assets/logo-bpom.jpg') }}">
    @elseif (file_exists(public_path('assets/BADAN_POM.png')))
        <link rel="icon" type="image/png" href="{{ asset('assets/BADAN_POM.png') }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans antialiased text-slate-800 bg-gradient-to-br from-[#f0f5fa] via-[#f7faf8] to-[#edf6f2] flex items-center justify-center p-4 sm:p-6 lg:p-10 relative overflow-x-hidden selection:bg-[#003B7A] selection:text-white" x-data="{ showHelp: false }">

    <!-- Soft Professional Ambient Gradient Blobs (BPOM Blue & Green Palette) -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <!-- BPOM Deep Navy/Blue Glow (Top-Left) -->
        <div class="absolute -top-32 -left-32 w-[540px] h-[540px] bg-gradient-to-br from-[#003B7A]/15 via-blue-200/25 to-transparent rounded-full blur-3xl"></div>
        <!-- BPOM Emerald Green Glow (Bottom-Right) -->
        <div class="absolute -bottom-32 -right-32 w-[560px] h-[560px] bg-gradient-to-tl from-[#008D43]/15 via-emerald-100/30 to-transparent rounded-full blur-3xl"></div>
        <!-- Soft Ambient Center Aura -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[850px] h-[600px] bg-gradient-to-r from-sky-100/25 via-teal-50/20 to-emerald-100/25 rounded-full blur-3xl"></div>
    </div>

    <!-- Centered Floating Rounded Card -->
    <div class="relative z-10 w-full max-w-4xl bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,59,122,0.08)] border border-slate-100/90 overflow-hidden flex flex-col md:flex-row">

        <!-- Left Column: Branding, Statement, and Trust Pills -->
        <div class="w-full md:w-1/2 p-8 sm:p-10 lg:p-12 flex flex-col justify-between relative overflow-hidden bg-gradient-to-b from-white via-[#f9fbfb] to-[#edf6f2]/40">
            <!-- Subtle organic ambient curve with BPOM tones -->
            <div class="absolute top-0 right-0 w-72 h-72 bg-gradient-to-br from-[#003B7A]/5 via-[#008D43]/10 to-transparent rounded-full blur-2xl pointer-events-none -mr-20 -mt-20"></div>

            <div class="relative z-10">
                <!-- Top Brand Logo (Frameless, Larger, Clear) & Institution Name -->
                <div class="flex items-center gap-4">
                    @if (file_exists(public_path('assets/logo-bpom.jpg')))
                        <img src="{{ asset('assets/logo-bpom.jpg') }}" alt="Logo BPOM" class="h-20 sm:h-22 w-auto object-contain shrink-0">
                    @elseif (file_exists(public_path('assets/logo-bpom.jpeg')))
                        <img src="{{ asset('assets/logo-bpom.jpeg') }}" alt="Logo BPOM" class="h-20 sm:h-22 w-auto object-contain shrink-0">
                    @elseif (file_exists(public_path('assets/BADAN_POM.png')))
                        <img src="{{ asset('assets/BADAN_POM.png') }}" alt="Logo BPOM" class="h-20 sm:h-22 w-auto object-contain shrink-0">
                    @else
                        <div class="h-16 w-16 rounded-2xl bg-[#003B7A] text-white flex items-center justify-center font-bold text-lg tracking-wider shadow-sm">
                            BPOM
                        </div>
                    @endif
                    <div>
                        <div class="text-2xl font-black tracking-tight text-[#003B7A] leading-tight">BADAN POM</div>
                        <p class="text-xs font-bold text-[#008D43] uppercase tracking-wider mt-0.5">Direktorat Pengawasan OTSK</p>
                        <p class="text-[11px] font-medium text-slate-400 tracking-wide mt-0.5">Republik Indonesia</p>
                    </div>
                </div>

                <!-- Headline -->
                <div class="mt-10 sm:mt-12">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-[1.18]">
                        Pengolahan Data Iklan<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#003B7A] via-[#0C589C] to-[#008D43]">Skala Nasional.</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mt-4 max-w-sm">
                        Sistem Informasi Pengolahan Data Iklan real-time dengan standardisasi kode numerik dan kompilasi rekap capaian IKU Obat Bahan Alam dan Suplemen Kesehatan.
                    </p>
                </div>

                <!-- Feature Badges (Pills) -->
                <div class="mt-8 space-y-2.5">
                    <!-- BPOM Emerald Green Badge -->
                    <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-emerald-50/80 border border-emerald-200/80 text-[#006630] text-xs font-semibold shadow-2xs">
                        <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center text-[#008D43] shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <span>Standar Kode Numerik BPOM Terenkripsi</span>
                    </div>

                    <div class="block"></div>

                    <!-- BPOM Blue Badge -->
                    <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-blue-50/80 border border-blue-200/80 text-[#003B7A] text-xs font-semibold shadow-2xs">
                        <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-[#003B7A] shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span>Real-time Rekapitulasi IKU Sync</span>
                    </div>
                </div>
            </div>

            <!-- Left Footer Note -->
            <div class="relative z-10 mt-10 pt-4 text-[11px] text-slate-400">
                &copy; {{ date('Y') }} Badan Pengawas Obat dan Makanan. All rights reserved.
            </div>
        </div>

        <!-- Right Column: Login Form -->
        <div class="w-full md:w-1/2 p-8 sm:p-10 lg:p-12 flex flex-col justify-center bg-white border-t md:border-t-0 md:border-l border-slate-100">
            <div>
                <!-- Heading -->
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Selamat Datang</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Masuk menggunakan kredensial NIP atau Email dinas Anda.</p>

                <!-- Session Alert -->
                @if (session('status'))
                    <div class="mt-4 p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900 flex items-start gap-2">
                        <svg class="w-4 h-4 text-[#003B7A] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <!-- Validation Error Alert -->
                @if ($errors->has('username'))
                    <div class="mt-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-start gap-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ $errors->first('username') }}</span>
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('login.post') }}" class="mt-6 space-y-4">
                    @csrf

                    <!-- NIP / Email / Username -->
                    <div class="space-y-1.5">
                        <label for="username" class="block text-xs font-semibold text-slate-700">
                            NIP / Email / Username <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                name="username"
                                id="username"
                                value="{{ old('username') }}"
                                required
                                autofocus
                                placeholder="10000001 atau evaluator"
                                class="block w-full pl-10 pr-3.5 py-2.5 text-sm text-slate-900 rounded-xl border border-slate-200 bg-white placeholder:text-slate-400 focus:border-[#003B7A] focus:ring-2 focus:ring-[#003B7A]/15 focus:outline-none transition {{ $errors->has('username') ? 'border-rose-400 ring-2 ring-rose-500/10' : '' }}"
                            >
                        </div>
                    </div>

                    <!-- Kata Sandi / Password with Show/Hide Toggle -->
                    <div class="space-y-1.5" x-data="{ show: false }">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-xs font-semibold text-slate-700">
                                Kata Sandi / Password <span class="text-rose-500">*</span>
                            </label>
                            <button
                                type="button"
                                @click="showHelp = true"
                                class="text-xs font-semibold text-[#003B7A] hover:text-[#008D43] transition cursor-pointer"
                            >
                                Lupa sandi?
                            </button>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                :type="show ? 'text' : 'password'"
                                name="password"
                                id="password"
                                required
                                placeholder="••••••••"
                                class="block w-full pl-10 pr-10 py-2.5 text-sm text-slate-900 rounded-xl border border-slate-200 bg-white placeholder:text-slate-400 focus:border-[#003B7A] focus:ring-2 focus:ring-[#003B7A]/15 focus:outline-none transition"
                            >
                            <button
                                type="button"
                                @click="show = !show"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer"
                                tabindex="-1"
                                :title="show ? 'Sembunyikan sandi' : 'Tampilkan sandi'"
                            >
                                <!-- Eye icon (when hidden) -->
                                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <!-- Eye-off icon (when visible) -->
                                <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center pt-0.5">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="remember"
                                class="w-4 h-4 rounded text-[#003B7A] border-slate-300 focus:ring-[#003B7A] focus:ring-offset-0"
                            >
                            <span class="text-xs text-slate-600">Ingat sesi saya pada perangkat ini</span>
                        </label>
                    </div>

                    <!-- Submit Button with BPOM Blue to Green Soft Professional Gradient -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            class="w-full py-3 px-4 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-[#003B7A] via-[#0A4F99] to-[#007E3D] hover:from-[#002f61] hover:via-[#08417e] hover:to-[#006933] shadow-md shadow-[#003B7A]/20 active:scale-[0.99] transition flex items-center justify-center gap-2.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span>Masuk ke Sistem</span>
                        </button>
                    </div>
                </form>

                <div class="mt-6 pt-4 text-center text-xs text-slate-400 border-t border-slate-100">
                    Akses dibatasi khusus Evaluator Pengawasan Iklan BPOM
                </div>
            </div>
        </div>
    </div>

    <!-- Help / Forgot Password Modal -->
    <div
        x-show="showHelp"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
        @keydown.escape.window="showHelp = false"
    >
        <div
            @click.away="showHelp = false"
            class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 text-center space-y-4"
        >
            <div class="w-12 h-12 rounded-full bg-blue-50 text-[#003B7A] flex items-center justify-center mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">Bantuan Akses & Sandi</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
                Untuk pemulihan kata sandi atau kendala akun dinas evaluator, silakan hubungi Administrator Sistem TI Direktorat Pengawasan OTSK Badan POM RI.
            </p>
            <button
                type="button"
                @click="showHelp = false"
                class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
            >
                Mengerti & Tutup
            </button>
        </div>
    </div>
</body>
</html>
