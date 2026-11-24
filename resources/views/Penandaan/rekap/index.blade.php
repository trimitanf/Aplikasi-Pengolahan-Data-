<x-layouts.app title="Rekap Evaluasi Penandaan">
    <x-slot:header>
        Rekap Evaluasi Penandaan
    </x-slot:header>
    <x-slot:breadcrumb>
        Pengawasan Penandaan / Rekap
    </x-slot:breadcrumb>

    <div class="space-y-6">
        <x-card title="Rekapitulasi Capaian Penandaan">
            <div class="py-12 px-4 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Framework Rekapitulasi Penandaan Siap</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                    Halaman ini disiapkan untuk pelaporan eksekutif, rekapitulasi progres per UPT/komoditi, dan ekspor data pengawasan penandaan.
                </p>
                <div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-teal-50 border border-teal-200 text-teal-700 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                    <span>Modul Mandiri</span>
                </div>
            </div>
        </x-card>
    </div>
</x-layouts.app>
