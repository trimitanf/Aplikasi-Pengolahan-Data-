<x-layouts.app title="Detail Dataset - {{ $dataset->nama_file_asli }}">
    <x-slot:header>
        Detail Dataset: {{ $dataset->nama_komoditi }} {{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }}
    </x-slot:header>
    <x-slot:breadcrumb>
        <a href="{{ route('dataset.index') }}" class="hover:underline">Dataset</a> / Detail
    </x-slot:breadcrumb>

    <!-- Stepper Progression -->
    <x-stepper :activeStep="$activeStep" :datasetId="$dataset->id" />

    <!-- Live Polling Wrapper if Processing -->
    <div
        x-data="{
            status: '{{ $dataset->status }}',
            isPolling: {{ in_array($dataset->status, ['Diunggah', 'Diproses']) ? 'true' : 'false' }},
            pollInterval: null,
            init() {
                if (this.isPolling) {
                    this.pollInterval = setInterval(() => {
                        fetch('{{ route('dataset.status', $dataset) }}')
                            .then(res => res.json())
                            .then(data => {
                                if (data.status !== 'Diunggah' && data.status !== 'Diproses') {
                                    clearInterval(this.pollInterval);
                                    window.location.reload();
                                }
                            })
                            .catch(err => console.error(err));
                    }, 2000);
                }
            }
        }"
        class="space-y-6"
    >
        <!-- Notice if Processing -->
        <div x-show="isPolling" class="p-4 rounded-xl border border-blue-200 bg-blue-50 text-blue-900 flex items-center gap-3 shadow-xs">
            <svg class="animate-spin h-5 w-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div class="text-xs font-medium">
                Sistem sedang memproses pembacaan dan validasi baris data file SIPT. Halaman akan diperbarui otomatis...
            </div>
        </div>

        <!-- Summary & Action Header Card -->
        <x-card variant="soft">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <!-- Info Left -->
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <x-badge variant="primary">{{ $dataset->komoditi }}</x-badge>
                        <h3 class="text-xl font-bold text-gray-900">{{ $dataset->nama_file_asli }}</h3>
                        @php
                            $badgeVariant = match($dataset->status) {
                                'Valid' => 'success',
                                'Final' => 'primary',
                                'Terstandardisasi' => 'primary',
                                'Ditolak', 'Gagal' => 'danger',
                                'Diproses' => 'warning',
                                default => 'neutral',
                            };
                        @endphp
                        <x-badge :variant="$badgeVariant" size="md">
                            Status: {{ $dataset->status }}
                        </x-badge>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs text-gray-600 pt-1">
                        <div>
                            <span class="block text-gray-400 font-medium uppercase text-[10px]">Periode Target:</span>
                            <span class="font-bold text-gray-900 text-sm">{{ $dataset->nama_bulan }} {{ $dataset->periode_tahun }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 font-medium uppercase text-[10px]">Rentang Tanggal SIPT:</span>
                            <span class="font-semibold text-gray-800">
                                {{ $dataset->periode_mulai ? $dataset->periode_mulai->format('d/m/Y') : '-' }} s/d {{ $dataset->periode_akhir ? $dataset->periode_akhir->format('d/m/Y') : '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-gray-400 font-medium uppercase text-[10px]">Total Baris:</span>
                            <span class="font-bold text-gray-900 text-sm">{{ number_format($dataset->jumlah_baris, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-400 font-medium uppercase text-[10px]">Menunggu Evaluasi:</span>
                            <span class="font-bold text-amber-700 text-sm">{{ number_format($dataset->jumlah_menunggu_evaluasi, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons Right -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    @if ($dataset->status === 'Valid' || $dataset->status === 'Terstandardisasi' || $dataset->status === 'Final')
                        <a
                            href="{{ route('dataset.data-mentah', $dataset) }}"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold text-blue-700 bg-white border border-blue-300 hover:bg-blue-50 shadow-2xs transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Data Mentah (26 Kolom)</span>
                        </a>

                        <a
                            href="{{ route('dataset.standardisasi', $dataset) }}"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold text-white shadow-xs hover:opacity-95 transition-opacity"
                            style="background-image: var(--grad-accent);"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <span>Standardisasi & Input Manual &rarr;</span>
                        </a>
                    @endif

                    @if ($dataset->canBeDeleted())
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'modal-hapus-dataset')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-red-600 bg-white border border-red-200 hover:bg-red-50 cursor-pointer shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus Dataset</span>
                        </button>
                    @endif
                </div>
            </div>
        </x-card>

        <!-- Validation Issues Panel -->
        <x-card title="Hasil Pemeriksaan Validasi" subtitle="Daftar anomali dan ketidaksesuaian yang terdeteksi saat validasi">
            <!-- Filter Tabs Issue -->
            <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                <a
                    href="{{ route('dataset.show', $dataset) }}"
                    @class([
                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors',
                        'bg-blue-600 text-white' => empty($tingkatAktif),
                        'bg-gray-100 text-gray-600 hover:bg-gray-200' => !empty($tingkatAktif),
                    ])
                >
                    Semua Masalah ({{ $issues->total() }})
                </a>

                <a
                    href="{{ route('dataset.show', ['dataset' => $dataset, 'tingkat' => 'error']) }}"
                    @class([
                        'px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors',
                        'bg-red-600 text-white' => $tingkatAktif === 'error',
                        'bg-red-50 text-red-700 hover:bg-red-100' => $tingkatAktif !== 'error',
                    ])
                >
                    <span>Error Kritis</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tingkatAktif === 'error' ? 'bg-white/30 text-white' : 'bg-red-200 text-red-800' }}">
                        {{ $errorCount }}
                    </span>
                </a>

                <a
                    href="{{ route('dataset.show', ['dataset' => $dataset, 'tingkat' => 'warning']) }}"
                    @class([
                        'px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors',
                        'bg-amber-500 text-white' => $tingkatAktif === 'warning',
                        'bg-amber-50 text-amber-700 hover:bg-amber-100' => $tingkatAktif !== 'warning',
                    ])
                >
                    <span>Peringatan (Warning)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tingkatAktif === 'warning' ? 'bg-white/30 text-white' : 'bg-amber-200 text-amber-800' }}">
                        {{ $warningCount }}
                    </span>
                </a>
            </div>

            <!-- Issues Table -->
            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-center w-20">Baris</th>
                            <th class="px-4 py-3 text-left w-36">Kolom</th>
                            <th class="px-4 py-3 text-center w-28">Tingkat</th>
                            <th class="px-4 py-3 text-left">Deskripsi Masalah / Rekomendasi Solutif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($issues as $issue)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-4 py-3 text-center font-bold text-gray-700">
                                    {{ $issue->nomor_baris ?? 'Umum' }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-xs text-gray-800 font-mono">
                                    {{ $issue->kolom ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($issue->tingkat === 'error')
                                        <x-badge variant="danger" size="sm">ERROR</x-badge>
                                    @else
                                        <x-badge variant="warning" size="sm">WARNING</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-800 text-xs leading-relaxed">
                                    {{ $issue->pesan }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-emerald-600 font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Tidak ada masalah validasi pada filter ini. Data siap untuk diproses lebih lanjut!</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($issues->hasPages())
                <div class="mt-4">
                    {{ $issues->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Modal Konfirmasi Hapus Dataset -->
    <x-modal name="modal-hapus-dataset" title="Konfirmasi Penghapusan Dataset" maxWidth="md">
        <div class="space-y-4">
            <div class="p-3 bg-red-50 text-red-800 rounded-xl border border-red-200 text-xs leading-relaxed">
                <strong>Peringatan:</strong> Seluruh data mentah dan catatan validasi untuk file <strong>{{ $dataset->nama_file_asli }}</strong> akan dihapus permanen dari server. Tindakan ini tidak dapat dibatalkan.
            </div>
            <p class="text-sm text-gray-600">
                Apakah Anda yakin ingin melanjutkan penghapusan?
            </p>
        </div>

        <x-slot:footer>
            <x-button type="button" variant="secondary" size="sm" @click="$dispatch('close-modal', 'modal-hapus-dataset')">
                Batal
            </x-button>
            <form method="POST" action="{{ route('dataset.destroy', $dataset) }}">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger" size="sm">
                    Ya, Hapus Permanen
                </x-button>
            </form>
        </x-slot:footer>
    </x-modal>
</x-layouts.app>
