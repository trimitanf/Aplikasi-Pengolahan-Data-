<x-layouts.app title="Data Terstandarisasi">
    <x-slot:header>
        Data Terstandarisasi Iklan
    </x-slot:header>
    <x-slot:breadcrumb>
        <span>Data Terstandarisasi</span>
    </x-slot:breadcrumb>

    <div class="max-w-2xl mx-auto my-12 text-center">
        <div class="bg-white rounded-2xl border border-(--border) p-8 shadow-xs">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            
            <h3 class="text-lg font-bold text-gray-900">Belum Ada Data Terstandarisasi</h3>
            <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
                Silakan unggah dokumen ekspor bulanan SIPT pada menu <strong>Dataset</strong> terlebih dahulu. Sistem akan otomatis memvalidasi dan menyiapkan tabel kode angka standar di menu ini.
            </p>

            <div class="mt-6">
                <a
                    href="{{ route('dataset.index') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white shadow-xs hover:shadow-md transition-all"
                    style="background-image: var(--grad-brand);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span>Buka Menu Dataset & Unggah Berkas</span>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
