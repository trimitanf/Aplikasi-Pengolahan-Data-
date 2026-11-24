<x-layouts.app title="Dashboard Pengawasan Penandaan OTSK">
    <x-slot:header>
        Dashboard Pengawasan Penandaan OTSK
    </x-slot:header>
    <x-slot:breadcrumb>
        Pengawasan Penandaan / Dashboard
    </x-slot:breadcrumb>

    <div class="space-y-6">
        <!-- Status Banner Modul Framework -->
        <div class="bg-white border-l-4 border-teal-500 rounded-xl p-5 shadow-xs border border-gray-200/80">
            <div class="flex items-start gap-4">
                <div class="p-2.5 bg-teal-50 text-teal-600 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 class="text-base font-bold text-gray-900">Framework Modul Pengawasan Penandaan Aktif</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Modul ini diisolasi khusus untuk <strong>Tim Pengawasan Penandaan</strong> dan <strong>Administrator</strong>.
                        Struktur routing, middleware, dan view scaffolding telah siap diintegrasikan dengan skema kolom/dataset SIPT Penandaan tanpa mempengaruhi modul Pengawasan Iklan.
                    </p>
                </div>
            </div>
        </div>

        <!-- KPI Placeholder Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl border border-gray-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Dataset</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">0</p>
                    </div>
                    <div class="w-11 h-11 bg-teal-50 text-teal-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-3">Berkas bulanan SIPT Penandaan</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-gray-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Baris Data</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">0</p>
                    </div>
                    <div class="w-11 h-11 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-3">Objek pengawasan penandaan</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-gray-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Terstandarisasi</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">0</p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-3">Hasil pemetaan & evaluasi</p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-gray-200/80 shadow-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Evaluator Penandaan</p>
                        <p class="text-2xl font-bold text-purple-600 mt-1">
                            {{ \App\Models\User::where('role', 'evaluator_penandaan')->where('is_active', true)->count() }}
                        </p>
                    </div>
                    <div class="w-11 h-11 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-3">Akun evaluator aktif</p>
            </div>
        </div>

        <!-- Navigasi Cepat Modul Penandaan -->
        <x-card title="Fitur Modul Pengawasan Penandaan">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-2">
                <a href="{{ route('penandaan.dataset.index') }}" class="group block p-5 rounded-xl border border-gray-200 hover:border-teal-400 hover:bg-teal-50/30 transition-all duration-150">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 group-hover:text-teal-700">1. Kelola Dataset Penandaan</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">Penyimpanan dan pengelolaan berkas mentah impor SIPT penandaan.</p>
                </a>

                <a href="{{ route('penandaan.standarisasi.index') }}" class="group block p-5 rounded-xl border border-gray-200 hover:border-teal-400 hover:bg-teal-50/30 transition-all duration-150">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 group-hover:text-teal-700">2. Standarisasi Data</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">Konversi, pemetaan label, dan validasi standar data pengawasan penandaan.</p>
                </a>

                <a href="{{ route('penandaan.rekap.index') }}" class="group block p-5 rounded-xl border border-gray-200 hover:border-teal-400 hover:bg-teal-50/30 transition-all duration-150">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900 group-hover:text-teal-700">3. Rekap & Pelaporan</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">Rekapitulasi berkala capaian, tindak lanjut UPT, dan ekspor data.</p>
                </a>
            </div>
        </x-card>
    </div>
</x-layouts.app>
