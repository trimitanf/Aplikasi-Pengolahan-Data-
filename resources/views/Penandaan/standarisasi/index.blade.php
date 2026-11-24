<x-layouts.app title="Standarisasi Penandaan">
    <x-slot:header>
        Standarisasi Data Penandaan
    </x-slot:header>
    <x-slot:breadcrumb>
        Pengawasan Penandaan / Standarisasi
    </x-slot:breadcrumb>

    <div class="space-y-6">
        <x-card title="Data Hasil Standarisasi Penandaan">
            <div class="py-12 px-4 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Framework Standarisasi Penandaan Siap</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                    Halaman ini disiapkan untuk proses validasi, pemetaan aturan evaluasi, dan standardisasi label pengawasan penandaan.
                </p>
                <div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-teal-50 border border-teal-200 text-teal-700 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                    <span>Modul Mandiri</span>
                </div>
            </div>
        </x-card>
    </div>
</x-layouts.app>
