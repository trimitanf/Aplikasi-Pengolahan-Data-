<x-layouts.app title="Dataset Penandaan">
    <x-slot:header>
        Kelola Dataset Penandaan
    </x-slot:header>
    <x-slot:breadcrumb>
        Pengawasan Penandaan / Dataset
    </x-slot:breadcrumb>

    <div class="space-y-6">
        <x-card title="Riwayat Dataset Penandaan">
            <x-slot:headerAction>
                <button
                    type="button"
                    disabled
                    class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600/60 text-white text-xs font-semibold rounded-lg opacity-75 cursor-not-allowed"
                    title="Impor SIPT Penandaan akan diaktifkan setelah penyesuaian skema kolom"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Impor SIPT Penandaan</span>
                </button>
            </x-slot:headerAction>

            <div class="py-12 px-4 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-1">Framework Dataset Penandaan Siap</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto leading-relaxed">
                    Struktur halaman dataset penandaan telah diisolasi secara mandiri. Form unggah dan tabel data akan disesuaikan dengan struktur kolom berkas SIPT Penandaan saat spesifikasi kolom telah ditentukan.
                </p>
                <div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-teal-50 border border-teal-200 text-teal-700 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                    <span>Modul Terpisah &bull; Tidak Mengganggu Data Iklan</span>
                </div>
            </div>
        </x-card>
    </div>
</x-layouts.app>
