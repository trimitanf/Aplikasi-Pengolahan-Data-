<x-layouts.app title="Dashboard">
    <x-slot:header>
        Dashboard Pengolahan Iklan
    </x-slot:header>
    <x-slot:breadcrumb>
        Dashboard
    </x-slot:breadcrumb>

    <!-- Welcome Banner with Accent Gradient -->
    <div
        class="rounded-2xl p-6 md:p-8 text-white relative overflow-hidden shadow-sm"
        style="background-image: var(--grad-brand);"
    >
        <div class="relative z-10 max-w-2xl space-y-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-blue-50 border border-white/20">
                Tahap 1: Fondasi Sistem & Desain
            </span>
            <h2 class="text-2xl font-bold tracking-tight text-white leading-snug">
                Selamat Datang, {{ auth()->user()->name }}
            </h2>
            <p class="text-sm text-blue-100/90 leading-relaxed">
                Sistem Pengolahan Data Iklan Direktorat Pengawasan OTSK BPOM siap digunakan. Database master kode, 83 UPT, aturan konversi, dan target IKU telah terkonfigurasi.
            </p>
        </div>
        <div class="absolute right-0 bottom-0 opacity-10 translate-x-8 translate-y-8 pointer-events-none">
            <svg class="w-72 h-72" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
            </svg>
        </div>
    </div>

    <!-- Quick Target IKU Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- OBA Card -->
        <x-card variant="soft">
            <div class="flex items-center justify-between pb-4 border-b border-(--border)">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-700">Target IKU Tahun 2026</span>
                    <h3 class="text-lg font-bold text-gray-900 mt-0.5">Obat Bahan Alam (OBA)</h3>
                </div>
                <x-badge variant="primary">Komoditi OT & OK</x-badge>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <div>
                    <span class="text-3xl font-extrabold text-(--brand-navy)">
                        {{ number_format($targetIku['OBA']->target ?? 67, 2, ',', '.') }}%
                    </span>
                    <span class="text-xs text-gray-500 ml-1.5 font-medium">Target MK</span>
                </div>
                <a
                    href="{{ route('pengaturan.index', ['tab' => 'target_iku']) }}"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1"
                >
                    <span>Ubah di Pengaturan</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </x-card>

        <!-- SK Card -->
        <x-card variant="soft">
            <div class="flex items-center justify-between pb-4 border-b border-(--border)">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Target IKU Tahun 2026</span>
                    <h3 class="text-lg font-bold text-gray-900 mt-0.5">Suplemen Kesehatan (SK)</h3>
                </div>
                <x-badge variant="success">Komoditi SK</x-badge>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <div>
                    <span class="text-3xl font-extrabold text-(--brand-navy)">
                        {{ number_format($targetIku['SK']->target ?? 83, 2, ',', '.') }}%
                    </span>
                    <span class="text-xs text-gray-500 ml-1.5 font-medium">Target MK</span>
                </div>
                <a
                    href="{{ route('pengaturan.index', ['tab' => 'target_iku']) }}"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline inline-flex items-center gap-1"
                >
                    <span>Ubah di Pengaturan</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </x-card>
    </div>

    <!-- Stepper Workflow Overview -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900">Alur Pengolahan Data Iklan</h3>
            <span class="text-xs text-gray-500">6 Langkah Otomasi & Evaluasi</span>
        </div>
        <x-stepper :activeStep="1" />
    </div>

    <!-- System Status & Next Steps -->
    <x-card title="Status Modul Sistem">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-emerald-800">Tahap 1</span>
                        <x-badge variant="success" size="sm">Selesai</x-badge>
                    </div>
                    <h4 class="text-sm font-semibold text-gray-900">Fondasi & Desain</h4>
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                        Desain BPOM, auth evaluator, 83 UPT, master kode, aturan pemetaan, dan pengaturan target IKU.
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500">Tahap 2</span>
                        <x-badge variant="neutral" size="sm">Berikutnya</x-badge>
                    </div>
                    <h4 class="text-sm font-semibold text-gray-800">Dataset & Validasi</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                        Unggah file export SIPT (.xls HTML/xlsx), parsing 26 kolom, dan validasi data mentah.
                    </p>
                </div>
            </div>

            <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500">Tahap 3 - 5</span>
                        <x-badge variant="neutral" size="sm">Mendatang</x-badge>
                    </div>
                    <h4 class="text-sm font-semibold text-gray-800">Standardisasi & Rekap</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                        Tabel angka kode, input manual 3 kolom merah, kalkulasi rekap otomatis, dan dashboard visual.
                    </p>
                </div>
            </div>
        </div>
    </x-card>
</x-layouts.app>
