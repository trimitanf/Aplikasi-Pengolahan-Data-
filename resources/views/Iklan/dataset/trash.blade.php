<x-layouts.app title="Kotak Sampah Dataset">
    <div class="space-y-6">
        <!-- Top Breadcrumb & Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <a href="{{ route('dataset.index') }}" class="hover:text-blue-600 transition-colors">Dataset</a>
                    <span class="text-gray-400">/</span>
                    <span class="font-semibold text-gray-700">Kotak Sampah</span>
                </nav>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 border border-red-200 flex items-center justify-center shrink-0 shadow-2xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 tracking-tight">Kotak Sampah Dataset</h2>
                        <p class="text-xs text-gray-500">Kelola dan pulihkan berkas dataset yang telah dipindahkan ke sampah, termasuk dataset berstatus Final.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <a
                    href="{{ route('dataset.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-2xs"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Daftar Dataset</span>
                </a>

                @if ($stats['total_sampah'] > 0)
                    <!-- Pulihkan Semua -->
                    <form method="POST" action="{{ route('dataset.trash.restore-all') }}" onsubmit="return confirm('Apakah Anda yakin ingin memulihkan SEMUA berkas dari Kotak Sampah?')">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 transition shadow-2xs cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Pulihkan Semua</span>
                        </button>
                    </form>

                    <!-- Kosongkan Sampah -->
                    <form method="POST" action="{{ route('dataset.trash.empty') }}" onsubmit="return confirm('PERINGATAN KERAS: Seluruh berkas di Kotak Sampah dan semua baris datanya akan DIHAPUS PERMANEN dari server. Tindakan ini TIDAK DAPAT DIBATALKAN. Yakin kosongkan sampah?')">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-600 text-xs font-semibold text-white hover:bg-red-700 transition shadow-2xs cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Kosongkan Sampah</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>


        <!-- Notice Alert -->
        <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/80 text-amber-900 text-xs flex items-start gap-3 shadow-xs">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="space-y-1">
                <strong class="font-semibold text-amber-950">Catatan Pengawasan Kotak Sampah:</strong>
                <p class="text-amber-800 leading-relaxed">
                    Berkas dataset yang berada di Kotak Sampah (termasuk yang berstatus <strong>Final</strong>) <em>tidak akan dihitung</em> dalam akumulasi IKU Dashboard, Data Tersimpan, dan Rekapitulasi Tahunan. Anda dapat memulihkannya kapan saja untuk mengembalikan status dan data seperti semula.
                </p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-card variant="soft">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Berkas di Sampah</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">
                            {{ number_format($stats['total_sampah'], 0, ',', '.') }}
                        </div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Total berkas menunggu tindakan</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-red-100/80 text-red-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                </div>
            </x-card>

            <x-card variant="soft">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Baris Data Non-Aktif</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">
                            {{ number_format($stats['total_baris'], 0, ',', '.') }}
                        </div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Baris data terisolasi di sampah</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-100/80 text-amber-700 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </x-card>

            <x-card variant="soft">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Berkas Status Final</div>
                        <div class="text-2xl font-black text-purple-900 mt-1">
                            {{ number_format($stats['total_final'], 0, ',', '.') }}
                        </div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Dataset final yang diarsipkan</div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-100/80 text-purple-700 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Filter Bar -->
        <x-card>
            <form method="GET" action="{{ route('dataset.trash') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Komoditi</label>
                    <select
                        name="komoditi"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none bg-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">Semua Komoditi</option>
                        <option value="OBA" @selected(($filters['komoditi'] ?? null) === 'OBA' || ($filters['komoditi'] ?? null) === 'OT')>Obat Bahan Alam (OBA)</option>
                        <option value="OK" @selected(($filters['komoditi'] ?? null) === 'OK')>Obat Kuasi (OK)</option>
                        <option value="SK" @selected(($filters['komoditi'] ?? null) === 'SK')>Suplemen Kesehatan (SK)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Tahun</label>
                    <select
                        name="tahun"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none bg-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">Semua Tahun</option>
                        @for ($y = now()->year + 1; $y >= 2024; $y--)
                            <option value="{{ $y }}" @selected($filters['tahun'] == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Status Terakhir</label>
                    <select
                        name="status"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none bg-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">Semua Status</option>
                        <option value="Diproses" @selected($filters['status'] === 'Diproses')>Diproses</option>
                        <option value="Sudah Diproses" @selected($filters['status'] === 'Sudah Diproses')>Sudah Diproses</option>
                    </select>
                </div>

                <div>
                    <a
                        href="{{ route('dataset.trash') }}"
                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset Filter</span>
                    </a>
                </div>
            </form>
        </x-card>

        <!-- Trashed Datasets Table Card -->
        <x-card title="Daftar Berkas di Kotak Sampah" subtitle="Berkas berikut dapat dipulihkan kapan saja ke daftar aktif atau dihapus permanen">
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5 text-left">Komoditi</th>
                            <th class="px-4 py-3.5 text-left">Periode</th>
                            <th class="px-4 py-3.5 text-left">Dokumen File SIPT</th>
                            <th class="px-4 py-3.5 text-right">Baris Data</th>
                            <th class="px-4 py-3.5 text-center">Status Terakhir</th>
                            <th class="px-4 py-3.5 text-left">Dihapus Pada</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($datasets as $item)
                            <tr class="hover:bg-red-50/20 transition-colors">
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if ($item->komoditi === 'SK')
                                        <div class="space-y-0.5">
                                            <x-badge variant="success">SK</x-badge>
                                            <span class="block text-[10px] text-gray-500 font-medium">Suplemen Kesehatan</span>
                                        </div>
                                    @elseif ($item->komoditi === 'OK')
                                        <div class="space-y-0.5">
                                            <x-badge variant="warning">OK</x-badge>
                                            <span class="block text-[10px] text-gray-500 font-medium">Obat Kuasi</span>
                                        </div>
                                    @else
                                        <div class="space-y-0.5">
                                            <x-badge variant="primary">OBA</x-badge>
                                            <span class="block text-[10px] text-gray-500 font-medium">Obat Bahan Alam</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-gray-900 text-xs sm:text-sm">
                                        {{ $item->nama_bulan }} {{ $item->periode_tahun }}
                                    </div>
                                    <span class="text-[10px] text-gray-400 block mt-0.5">
                                        Diunggah {{ $item->uploaded_at ? $item->uploaded_at->translatedFormat('d M Y, H:i') : ($item->created_at ? $item->created_at->translatedFormat('d M Y, H:i') : '-') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 max-w-xs">
                                            <span class="font-semibold text-xs text-gray-800 truncate block line-through decoration-red-400">
                                                {{ $item->nama_file_asli }}
                                            </span>
                                            <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-gray-100 text-gray-600 uppercase">
                                                {{ pathinfo($item->nama_file_asli, PATHINFO_EXTENSION) ?: 'XLS' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-right font-semibold text-gray-800 text-xs">
                                    {{ number_format($item->jumlah_baris, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    @if ($item->isFinal())
                                        <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                            Sudah Diproses
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold bg-red-100 text-red-700 border border-red-200">
                                            Diproses
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-500">
                                    <div class="font-medium text-gray-800">
                                        {{ $item->deleted_at ? $item->deleted_at->translatedFormat('d M Y') : '-' }}
                                    </div>
                                    <span class="text-[10px] text-gray-400">
                                        {{ $item->deleted_at ? $item->deleted_at->format('H:i') . ' WIB' : '' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <!-- Form Pulihkan -->
                                        <form method="POST" action="{{ route('dataset.restore', $item->id) }}" class="inline-block">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 hover:border-emerald-400 transition-colors shadow-2xs cursor-pointer"
                                                title="Pulihkan dataset ini ke daftar aktif"
                                            >
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Pulihkan</span>
                                            </button>
                                        </form>

                                        <!-- Form Hapus Permanen -->
                                        <form
                                            method="POST"
                                            action="{{ route('dataset.force-delete', $item->id) }}"
                                            class="inline-block"
                                            onsubmit="return confirm('PERINGATAN KERAS: Berkas \'{{ $item->nama_file_asli }}\' dan seluruh data terkait akan DIHAPUS PERMANEN dari server. Tindakan ini tidak dapat dibatalkan. Lanjutkan?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-red-200 bg-red-50 text-xs font-semibold text-red-700 hover:bg-red-100 hover:border-red-300 transition-colors shadow-2xs cursor-pointer"
                                                title="Hapus permanen dari database dan disk"
                                            >
                                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                <span>Hapus Permanen</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center mb-3">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-900">Kotak Sampah Bersih</h4>
                                        <p class="text-xs text-gray-500 mt-1 max-w-sm">
                                            Tidak ada berkas dataset yang berada di dalam tempat sampah saat ini. Seluruh berkas aktif tersimpan aman.
                                        </p>
                                        <div class="mt-4">
                                            <a
                                                href="{{ route('dataset.index') }}"
                                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold text-white shadow-xs hover:opacity-95 transition"
                                                style="background-image: var(--grad-accent);"
                                            >
                                                <span>Kembali ke Daftar Dataset</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($datasets->hasPages())
                <div class="mt-4">
                    {{ $datasets->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
