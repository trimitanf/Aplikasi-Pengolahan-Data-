<x-layouts.app title="Target IKU">
    <x-slot:header>
        Pengaturan Target IKU
    </x-slot:header>
    <x-slot:breadcrumb>
        Target IKU
    </x-slot:breadcrumb>

    <div
        x-data="{
            editItem: {
                id: null,
                tahun: 2026,
                kelompok: 'OBA',
                target: 0
            },
            deleteItem: {
                id: null,
                tahun: 2026,
                kelompok: 'OBA',
                target: 0
            },
            openEditModal(item) {
                this.editItem = {
                    id: item.id,
                    tahun: item.tahun,
                    kelompok: item.kelompok,
                    target: item.target
                };
                $dispatch('open-modal', 'modal-edit-target');
            },
            openDeleteModal(item) {
                this.deleteItem = {
                    id: item.id,
                    tahun: item.tahun,
                    kelompok: item.kelompok,
                    target: item.target
                };
                $dispatch('open-modal', 'modal-hapus-target');
            }
        }"
        class="space-y-6"
    >
        <!-- Top Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Tahun Berjalan</span>
                    <span class="text-xl font-bold text-gray-900">{{ $stats['tahun_berjalan'] }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Target OBA ({{ $stats['tahun_berjalan'] }})</span>
                    <span class="text-xl font-bold text-emerald-600">{{ number_format($stats['target_oba'], 2) }}%</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Target SK ({{ $stats['tahun_berjalan'] }})</span>
                    <span class="text-xl font-bold text-purple-600">{{ number_format($stats['target_sk'], 2) }}%</span>
                </div>
            </div>
        </div>

        <!-- Action Header & Filter Toolbar -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-gray-200 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-3 border-b border-gray-100">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Daftar Target Indikator Kinerja Utama (IKU)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Target capaian persentase Memenuhi Ketentuan (MK) per tahun untuk komoditi Obat Bahan Alam (OBA) dan Suplemen Kesehatan (SK).
                    </p>
                </div>
                <button
                    type="button"
                    @click="$dispatch('open-modal', 'modal-tambah-target')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold text-white shadow-xs hover:opacity-95 transition cursor-pointer shrink-0"
                    style="background-image: var(--grad-accent);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tetapkan Target IKU</span>
                </button>
            </div>

            <!-- Filter Controls: Filter Tahun & Komoditi -->
            <form method="GET" action="{{ route('target-iku.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <label for="filter_tahun" class="text-xs font-bold text-gray-600">Tahun:</label>
                        <select
                            id="filter_tahun"
                            name="tahun"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none bg-white"
                            onchange="this.form.submit()"
                        >
                            <option value="">Semua Tahun</option>
                            @foreach ($availableYears as $y)
                                <option value="{{ $y }}" @selected((string) request('tahun') === (string) $y)>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <label for="filter_kelompok" class="text-xs font-bold text-gray-600">Komoditi:</label>
                        <select
                            id="filter_kelompok"
                            name="kelompok"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none bg-white"
                            onchange="this.form.submit()"
                        >
                            <option value="">Semua Komoditi</option>
                            <option value="OBA" @selected(request('kelompok') === 'OBA')>Obat Bahan Alam (OBA)</option>
                            <option value="SK" @selected(request('kelompok') === 'SK')>Suplemen Kesehatan (SK)</option>
                        </select>
                    </div>

                    @if (request()->hasAny(['tahun', 'kelompok']))
                        <a
                            href="{{ route('target-iku.index') }}"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-gray-50 text-gray-600 hover:text-gray-900 text-xs font-semibold hover:bg-gray-100 transition"
                            title="Reset Filter"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>Reset Filter</span>
                        </a>
                    @endif
                </div>

                <div class="text-xs text-gray-500">
                    Menampilkan <strong class="text-gray-900">{{ $targetIkuList->count() }}</strong> data target
                </div>
            </form>
        </div>

        <!-- Tabel Target IKU -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50/80 text-[11px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="py-3 px-4">Tahun</th>
                            <th scope="col" class="py-3 px-4">Kelompok Komoditi</th>
                            <th scope="col" class="py-3 px-4">Target Capaian MK</th>
                            <th scope="col" class="py-3 px-4">Terakhir Diperbarui</th>
                            <th scope="col" class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($targetIkuList as $item)
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900">
                                    {{ $item->tahun }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if ($item->kelompok === 'OBA')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Obat Bahan Alam (OBA)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                            Suplemen Kesehatan (SK)
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-bold text-base text-gray-900">
                                    {{ number_format($item->target, 2) }}%
                                </td>
                                <td class="py-3.5 px-4 text-xs text-gray-500">
                                    {{ $item->updated_at ? $item->updated_at->format('d M Y H:i') : '-' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            @click="openEditModal({{ Js::from($item) }})"
                                            class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition cursor-pointer"
                                            title="Ubah Target"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal({{ Js::from($item) }})"
                                            class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition cursor-pointer"
                                            title="Hapus Target"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <p class="text-sm font-semibold text-gray-700">
                                            @if (request()->hasAny(['periode', 'tahun', 'kelompok']))
                                                Tidak ada target IKU yang sesuai dengan filter yang dipilih.
                                            @else
                                                Belum ada target IKU yang ditetapkan.
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            @if (request()->hasAny(['periode', 'tahun', 'kelompok']))
                                                <a href="{{ route('target-iku.index') }}" class="text-blue-600 hover:underline font-semibold">Klik di sini untuk mereset filter</a> atau tetapkan target baru.
                                            @else
                                                Klik tombol 'Tetapkan Target IKU' untuk menambahkan target capaian baru.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah / Tetapkan Target IKU -->
        <x-modal name="modal-tambah-target" title="Tetapkan Target IKU Baru" maxWidth="md">
            <form method="POST" action="{{ route('target-iku.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="create_tahun" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Tahun Periode <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="tahun"
                        id="create_tahun"
                        min="2020"
                        max="2100"
                        required
                        value="{{ old('tahun', date('Y')) }}"
                        placeholder="Contoh: {{ date('Y') }}"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    >
                    <p class="text-[11px] text-gray-500 mt-1">Jika target pada tahun dan komoditi tersebut sudah ada, nilainya akan diperbarui.</p>
                </div>

                <div>
                    <label for="create_kelompok" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Kelompok Komoditi <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="kelompok"
                        id="create_kelompok"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none bg-white"
                    >
                        <option value="OBA" @selected(old('kelompok') === 'OBA')>Obat Bahan Alam (OBA) - OT & OK</option>
                        <option value="SK" @selected(old('kelompok') === 'SK')>Suplemen Kesehatan (SK)</option>
                    </select>
                </div>

                <div>
                    <label for="create_target" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Nilai Target (%) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="target"
                            id="create_target"
                            step="0.01"
                            min="0"
                            max="100"
                            required
                            value="{{ old('target', '67.00') }}"
                            placeholder="67.00"
                            class="block w-full rounded-lg border border-gray-300 pl-3.5 pr-8 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500 font-semibold text-sm">
                            %
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Masukkan target capaian Memenuhi Ketentuan (rentang 0 - 100%).</p>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-tambah-target')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg text-white shadow-xs hover:opacity-95 transition cursor-pointer"
                        style="background-image: var(--grad-accent);"
                    >
                        Simpan Target IKU
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Edit Target IKU -->
        <x-modal name="modal-edit-target" title="Ubah Nilai Target IKU" maxWidth="md">
            <form
                method="POST"
                :action="'{{ url('target-iku') }}/' + editItem.id"
                class="space-y-4"
            >
                @csrf
                @method('PUT')

                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Tahun Periode:</span>
                        <span class="font-bold text-gray-800" x-text="editItem.tahun"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Kelompok Komoditi:</span>
                        <span class="font-bold text-blue-700" x-text="editItem.kelompok === 'OBA' ? 'Obat Bahan Alam (OBA)' : 'Suplemen Kesehatan (SK)'"></span>
                    </div>
                </div>

                <div>
                    <label for="edit_target" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Nilai Target (%) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            name="target"
                            id="edit_target"
                            step="0.01"
                            min="0"
                            max="100"
                            required
                            x-model="editItem.target"
                            class="block w-full rounded-lg border border-gray-300 pl-3.5 pr-8 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                        >
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500 font-semibold text-sm">
                            %
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Masukkan target capaian persentase terbaru (0 - 100%).</p>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-edit-target')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg text-white shadow-xs hover:opacity-95 transition cursor-pointer"
                        style="background-image: var(--grad-accent);"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Hapus Target IKU -->
        <x-modal name="modal-hapus-target" title="Konfirmasi Hapus Target IKU" maxWidth="sm">
            <form
                method="POST"
                :action="'{{ url('target-iku') }}/' + deleteItem.id"
                class="space-y-4"
            >
                @csrf
                @method('DELETE')

                <div class="text-center sm:text-left">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 mb-3 text-red-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900">Hapus target IKU ini?</h4>
                    <p class="text-xs text-gray-500 mt-2">
                        Target <span class="font-bold text-gray-700" x-text="deleteItem.kelompok"></span>
                        Tahun <span class="font-bold text-gray-700" x-text="deleteItem.tahun"></span>
                        (<span class="font-semibold text-blue-600" x-text="deleteItem.target + '%'"></span>) akan dihapus secara permanen.
                    </p>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-hapus-target')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 shadow-xs transition cursor-pointer"
                    >
                        Ya, Hapus Target
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
