<x-layouts.app title="Dataset Pengawasan Iklan">
    <x-slot:header>
        Dataset Pengawasan Iklan SIPT
    </x-slot:header>
    <x-slot:breadcrumb>
        Dataset
    </x-slot:breadcrumb>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Unggah File SIPT -->
        <div class="lg:col-span-1">
            <x-card title="Unggah Dataset SIPT" subtitle="Unggah file export bulanan SIPT (.xls HTML / .xlsx)">
                <form method="POST" action="{{ route('dataset.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <!-- Komoditi -->
                    <div class="space-y-1.5">
                        <label for="komoditi" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                            Komoditi <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="komoditi"
                            name="komoditi"
                            class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                            required
                        >
                            <option value="">-- Pilih Komoditi --</option>
                            <option value="OT" @selected(old('komoditi') === 'OT')>Obat Tradisional (OT)</option>
                            <option value="SK" @selected(old('komoditi') === 'SK')>Suplemen Kesehatan (SK)</option>
                            <option value="OK" @selected(old('komoditi') === 'OK')>Obat Kuasi (OK)</option>
                        </select>
                        @error('komoditi')
                            <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Periode Bulan & Tahun -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label for="periode_bulan" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                                Bulan <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="periode_bulan"
                                name="periode_bulan"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                                required
                            >
                                @php
                                    $bulanList = [
                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                                    ];
                                @endphp
                                @foreach ($bulanList as $num => $nama)
                                    <option value="{{ $num }}" @selected(old('periode_bulan', now()->month) == $num)>
                                        {{ $nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('periode_bulan')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-input
                            label="Tahun"
                            name="periode_tahun"
                            type="number"
                            min="2020"
                            max="2100"
                            :value="old('periode_tahun', now()->year)"
                            required
                        />
                    </div>

                    <!-- File Input -->
                    <div class="space-y-1.5">
                        <label for="file" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                            File Ekspor SIPT <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-500 transition-colors bg-gray-50/50">
                            <div class="space-y-2 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="text-xs text-gray-600">
                                    <label for="file" class="relative cursor-pointer rounded-md font-semibold text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        <span>Pilih file dari komputer</span>
                                        <input id="file" name="file" type="file" class="sr-only" required accept=".xls,.xlsx,.html,.htm">
                                    </label>
                                </div>
                                <p class="text-[11px] text-gray-400">Format .xls (HTML) atau .xlsx (Maks 25 MB)</p>
                            </div>
                        </div>
                        @error('file')
                            <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <x-button type="submit" variant="primary" class="w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span>Unggah & Proses Validasi</span>
                        </x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- Riwayat Dataset -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Filter Bar -->
            <x-card>
                <form method="GET" action="{{ route('dataset.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <select
                            name="komoditi"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none"
                            onchange="this.form.submit()"
                        >
                            <option value="">Semua Komoditi</option>
                            <option value="OT" @selected($filters['komoditi'] === 'OT')>Obat Tradisional (OT)</option>
                            <option value="SK" @selected($filters['komoditi'] === 'SK')>Suplemen Kesehatan (SK)</option>
                            <option value="OK" @selected($filters['komoditi'] === 'OK')>Obat Kuasi (OK)</option>
                        </select>
                    </div>

                    <div>
                        <select
                            name="tahun"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none"
                            onchange="this.form.submit()"
                        >
                            <option value="">Semua Tahun</option>
                            @for ($y = now()->year + 1; $y >= 2024; $y--)
                                <option value="{{ $y }}" @selected($filters['tahun'] == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <select
                            name="status"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none"
                            onchange="this.form.submit()"
                        >
                            <option value="">Semua Status</option>
                            <option value="Diunggah" @selected($filters['status'] === 'Diunggah')>Diunggah</option>
                            <option value="Diproses" @selected($filters['status'] === 'Diproses')>Diproses</option>
                            <option value="Valid" @selected($filters['status'] === 'Valid')>Valid</option>
                            <option value="Ditolak" @selected($filters['status'] === 'Ditolak')>Ditolak</option>
                            <option value="Terstandardisasi" @selected($filters['status'] === 'Terstandardisasi')>Terstandardisasi</option>
                            <option value="Final" @selected($filters['status'] === 'Final')>Final</option>
                        </select>
                    </div>

                    <div>
                        <a
                            href="{{ route('dataset.index') }}"
                            class="w-full inline-flex items-center justify-center px-3 py-2 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Reset Filter
                        </a>
                    </div>
                </form>
            </x-card>

            <!-- Table of Datasets -->
            <x-card title="Riwayat Dataset Terdaftar">
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Komoditi</th>
                                <th class="px-4 py-3 text-left">Periode</th>
                                <th class="px-4 py-3 text-left">File Asli</th>
                                <th class="px-4 py-3 text-right">Baris</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($datasets as $item)
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-4 py-3.5">
                                        @if ($item->komoditi === 'OT')
                                            <x-badge variant="primary">OT</x-badge>
                                        @elseif ($item->komoditi === 'SK')
                                            <x-badge variant="success">SK</x-badge>
                                        @else
                                            <x-badge variant="warning">OK</x-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 font-semibold text-gray-900">
                                        {{ $item->nama_bulan }} {{ $item->periode_tahun }}
                                    </td>
                                    <td class="px-4 py-3.5 text-xs text-gray-600 font-mono truncate max-w-45" title="{{ $item->nama_file_asli }}">
                                        {{ $item->nama_file_asli }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-medium text-gray-800">
                                        {{ number_format($item->jumlah_baris, 0, ',', '.') }}
                                        @if ($item->jumlah_menunggu_evaluasi > 0)
                                            <span class="block text-[10px] text-amber-600 font-normal">
                                                ({{ $item->jumlah_menunggu_evaluasi }} menunggu)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @php
                                            $badgeVariant = match($item->status) {
                                                'Valid' => 'success',
                                                'Final' => 'primary',
                                                'Terstandardisasi' => 'primary',
                                                'Ditolak', 'Gagal' => 'danger',
                                                'Diproses' => 'warning',
                                                default => 'neutral',
                                            };
                                        @endphp
                                        <x-badge :variant="$badgeVariant" size="sm">
                                            {{ $item->status }}
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3.5 text-right space-x-2">
                                        <a
                                            href="{{ route('dataset.show', $item) }}"
                                            class="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-800"
                                        >
                                            Detail &rarr;
                                        </a>

                                        @if ($item->canBeDeleted())
                                            <form method="POST" action="{{ route('dataset.destroy', $item) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus dataset ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="text-xs text-red-500 hover:text-red-700 font-medium cursor-pointer"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                        <x-empty-state
                                            title="Belum Ada Dataset"
                                            description="Silakan unggah file export SIPT pertama Anda menggunakan formulir di sebelah kiri."
                                        />
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
    </div>
</x-layouts.app>
