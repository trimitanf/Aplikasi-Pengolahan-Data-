<x-layouts.app title="Aturan Konversi SIPT">
    <x-slot:header>
        Aturan Konversi SIPT
        <span class="text-xs font-normal text-gray-500 block mt-0.5">Kelola Aturan Pemetaan SIPT ke Kode Standar</span>
    </x-slot:header>
    <x-slot:breadcrumb>
        Aturan Konversi SIPT
    </x-slot:breadcrumb>

    <div
        x-data="{
            masterDict: {{ Js::from($masterDict) }},
            selectedGrup: '{{ old('grup', '') }}',
            selectedKode: '{{ old('kode', '') }}',
            editRule: {
                id: null,
                grup: '',
                kolom_sumber: '',
                nilai_sipt: '',
                kode: 0,
                is_active: true
            },
            ruleToDelete: {
                id: null,
                nilai_sipt: ''
            },
            openEditModal(rule) {
                this.editRule = {
                    id: rule.id,
                    grup: rule.grup,
                    kolom_sumber: rule.kolom_sumber,
                    nilai_sipt: rule.nilai_sipt,
                    kode: rule.kode,
                    is_active: Boolean(rule.is_active)
                };
                $dispatch('open-modal', 'modal-edit-aturan');
            },
            openDeleteModal(rule) {
                this.ruleToDelete = {
                    id: rule.id,
                    nilai_sipt: rule.nilai_sipt
                };
                $dispatch('open-modal', 'modal-hapus-aturan');
            }
        }"
        class="space-y-6"
    >
        <!-- Top Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Aturan Konversi</span>
                    <span class="text-xl font-bold text-gray-900">{{ number_format($stats['total_aturan']) }} Aturan</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Aturan Aktif</span>
                    <span class="text-xl font-bold text-emerald-600">{{ number_format($stats['total_aktif']) }} Aturan</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Grup Master Dicakup</span>
                    <span class="text-xl font-bold text-purple-700">{{ number_format($stats['total_grup']) }} Grup</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar & Tambah Action -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('aturan-konversi.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
                <!-- Search Keyword -->
                <div class="relative min-w-55 flex-1 sm:flex-initial">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="cari"
                        value="{{ $cari }}"
                        placeholder="Cari teks SIPT / kolom sumber..."
                        class="block w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none"
                    >
                </div>

                <!-- Grup Filter -->
                <div class="w-full sm:w-auto">
                    <select
                        name="grup"
                        onchange="this.form.submit()"
                        class="w-full sm:w-auto px-3 py-2 text-xs rounded-lg border border-gray-300 bg-white text-gray-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none cursor-pointer"
                    >
                        <option value="">Semua Grup Master</option>
                        @foreach ($availableGrups as $g)
                            <option value="{{ $g }}" @selected($filterGrup === $g)>
                                {{ strtoupper(str_replace('_', ' ', $g)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-full sm:w-auto">
                    <select
                        name="status"
                        onchange="this.form.submit()"
                        class="w-full sm:w-auto px-3 py-2 text-xs rounded-lg border border-gray-300 bg-white text-gray-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        <option value="aktif" @selected($filterStatus === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($filterStatus === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition cursor-pointer"
                >
                    Terapkan
                </button>

                @if ($cari || $filterGrup || $filterStatus)
                    <a
                        href="{{ route('aturan-konversi.index') }}"
                        class="text-xs text-red-600 hover:text-red-700 font-medium underline inline-flex items-center gap-1"
                    >
                        Reset Filter
                    </a>
                @endif
            </form>

            <button
                type="button"
                @click="$dispatch('open-modal', 'modal-tambah-aturan')"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg text-white shadow-xs hover:opacity-95 transition cursor-pointer shrink-0"
                style="background-image: var(--grad-accent);"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Aturan Konversi</span>
            </button>
        </div>

        <!-- Tabel Aturan Konversi -->
        <x-card title="Daftar Aturan Konversi Nilai Mentah SIPT ke Kode Standar" subtitle="Pencocokan teks mentah SIPT secara otomatis rule-based saat proses standardisasi dataset dijalankan">
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left w-36">Grup Master</th>
                            <th class="px-4 py-3 text-left w-48">Kolom Sumber SIPT</th>
                            <th class="px-4 py-3 text-left">Teks Mentah SIPT (Ternormalisasi)</th>
                            <th class="px-4 py-3 text-left">Kode Standar (Master Data)</th>
                            <th class="px-4 py-3 text-center w-28">Status</th>
                            <th class="px-4 py-3 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($rules as $rule)
                            @php
                                $masterName = $masterDict[$rule->grup][$rule->kode] ?? null;
                            @endphp
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-800">
                                        {{ strtoupper(str_replace('_', ' ', $rule->grup)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-700">
                                    {{ $rule->kolom_sumber }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-900">
                                    {{ $rule->nilai_sipt }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="font-mono font-bold text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                                            {{ $rule->kode }}
                                        </span>
                                        @if ($masterName)
                                            <span class="text-xs text-gray-700">{{ $masterName }}</span>
                                        @else
                                            <span class="text-xs text-gray-400 italic">(Kode custom / belum di master)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($rule->is_active)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal({{ Js::from($rule) }})"
                                            class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition cursor-pointer"
                                            title="Ubah aturan konversi"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteModal({{ Js::from($rule) }})"
                                            class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md transition cursor-pointer"
                                            title="Hapus aturan konversi"
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
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="max-w-xs mx-auto text-center">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3 text-gray-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-600">Tidak ada aturan konversi yang cocok</p>
                                        <p class="text-xs text-gray-400 mt-1">Ubah kata kunci pencarian atau reset filter untuk menampilkan aturan</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rules->hasPages())
                <div class="mt-4">
                    {{ $rules->links() }}
                </div>
            @endif
        </x-card>

        <!-- Modal Tambah Aturan Konversi Baru -->
        <x-modal name="modal-tambah-aturan" title="Tambah Aturan Konversi SIPT Baru" maxWidth="lg">
            <form method="POST" action="{{ route('aturan-konversi.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="create_grup" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Grup Master <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="grup"
                        id="create_grup"
                        required
                        x-model="selectedGrup"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none bg-white"
                    >
                        <option value="" disabled selected>-- Pilih Grup Master --</option>
                        @foreach ($availableGrups as $g)
                            <option value="{{ $g }}">
                                {{ strtoupper(str_replace('_', ' ', $g)) }} ({{ $g }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="create_kolom_sumber" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Kolom Sumber SIPT <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="kolom_sumber"
                        id="create_kolom_sumber"
                        required
                        placeholder="Contoh: KOMODITI / MEDIA / HASIL PENGAWASAN"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none uppercase font-mono"
                    >
                    <p class="text-[11px] text-gray-500 mt-1">Nama header kolom pada file dataset tarikan SIPT.</p>
                </div>

                <div>
                    <label for="create_nilai_sipt" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Teks Mentah SIPT <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="nilai_sipt"
                        id="create_nilai_sipt"
                        required
                        placeholder="Contoh: OBAT TRADISIONAL / SHOPEE INDONESIA / MEMENUHI KETENTUAN"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none uppercase"
                    >
                    <p class="text-[11px] text-gray-500 mt-1">Teks mentah sebagaimana tertulis pada file export SIPT.</p>
                </div>

                <!-- Dropdown Kode Standar dari Master Data Kamus Kode Standar -->
                <div>
                    <label for="create_kode_select" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Pilih Kode Standar (Master Data) <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="kode"
                        id="create_kode_select"
                        required
                        x-model="selectedKode"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none bg-white"
                    >
                        <option value="" disabled selected>-- Pilih Kode dari Kamus Kode Standar --</option>
                        <template x-for="(label, k) in (masterDict[selectedGrup] || {})" :key="k">
                            <option :value="k" x-text="k + ' - ' + label"></option>
                        </template>
                    </select>
                    <p class="text-[11px] text-gray-500 mt-1" x-show="!selectedGrup">
                        Pilih Grup Master di atas terlebih dahulu untuk memuat daftar kode standar.
                    </p>
                    <p class="text-[11px] text-emerald-600 mt-1" x-show="selectedGrup">
                        Opsi diambil langsung dari Master Data Kamus Kode Standar.
                    </p>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_active"
                        id="create_rule_is_active"
                        value="1"
                        checked
                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer"
                    >
                    <label for="create_rule_is_active" class="text-xs font-medium text-gray-700 cursor-pointer select-none">
                        Aturan Aktif (Otomatis digunakan saat standardisasi dataset dijalankan)
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-tambah-aturan')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg text-white shadow-xs hover:opacity-95 transition cursor-pointer"
                        style="background-image: var(--grad-accent);"
                    >
                        Simpan Aturan Konversi
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Edit Aturan Konversi -->
        <x-modal name="modal-edit-aturan" title="Ubah Aturan Konversi SIPT" maxWidth="md">
            <form
                method="POST"
                :action="'{{ url('aturan-konversi') }}/' + editRule.id"
                class="space-y-4"
            >
                @csrf
                @method('PUT')

                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Grup Master:</span>
                        <span class="font-bold text-gray-800" x-text="editRule.grup ? editRule.grup.toUpperCase() : '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Kolom Sumber:</span>
                        <span class="font-mono font-bold text-blue-700" x-text="editRule.kolom_sumber"></span>
                    </div>
                    <div class="pt-1 border-t border-gray-200">
                        <span class="text-gray-500 block mb-0.5 font-medium">Teks Mentah SIPT:</span>
                        <span class="font-bold text-gray-900 block" x-text="editRule.nilai_sipt"></span>
                    </div>
                </div>

                <!-- Dropdown Kode Standar dari Master Data -->
                <div>
                    <label for="edit_kode_select" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Pilih Kode Standar (Master Data) <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="kode"
                        id="edit_kode_select"
                        required
                        x-model="editRule.kode"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none bg-white"
                    >
                        <template x-for="(label, k) in (masterDict[editRule.grup] || {})" :key="k">
                            <option :value="k" :selected="editRule.kode == k" x-text="k + ' - ' + label"></option>
                        </template>
                    </select>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_active"
                        id="edit_rule_is_active"
                        value="1"
                        x-model="editRule.is_active"
                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer"
                    >
                    <label for="edit_rule_is_active" class="text-xs font-medium text-gray-700 cursor-pointer select-none">
                        Aturan Aktif
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-edit-aturan')"
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

        <!-- Modal Hapus Aturan Konversi -->
        <x-modal name="modal-hapus-aturan" title="Konfirmasi Hapus Aturan Konversi" maxWidth="sm">
            <form
                method="POST"
                :action="'{{ url('aturan-konversi') }}/' + ruleToDelete.id"
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
                    <h4 class="text-sm font-bold text-gray-900">Hapus aturan konversi ini?</h4>
                    <p class="text-xs text-gray-500 mt-2">
                        Aturan konversi untuk teks "<span class="font-semibold text-gray-800" x-text="ruleToDelete.nilai_sipt"></span>" akan dihapus secara permanen. Standardisasi selanjutnya tidak akan memetakan teks ini secara otomatis lagi.
                    </p>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-hapus-aturan')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 shadow-xs transition cursor-pointer"
                    >
                        Ya, Hapus Aturan
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
