<x-layouts.app title="Master Data">
    <x-slot:header>
        Master Data
    </x-slot:header>
    <x-slot:breadcrumb>
        Master Data
    </x-slot:breadcrumb>

    <div
        x-data="{
            editItem: {
                id: null,
                grup: '',
                kode: 0,
                nama: ''
            },
            deleteItem: {
                id: null,
                kode: 0,
                nama: '',
                grup: ''
            },
            openEditModal(item) {
                this.editItem = {
                    id: item.id,
                    grup: item.grup || '{{ $grupAktif }}',
                    kode: item.kode,
                    nama: item.nama
                };
                $dispatch('open-modal', 'modal-edit-kode');
            },
            openDeleteModal(item) {
                this.deleteItem = {
                    id: item.id,
                    kode: item.kode,
                    nama: item.nama,
                    grup: item.grup || '{{ $grupAktif }}'
                };
                $dispatch('open-modal', 'modal-hapus-kode');
            }
        }"
        class="space-y-6"
    >
        <!-- Top Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Kode Standar</span>
                    <span class="text-xl font-bold text-gray-900">{{ number_format($stats['total_kode']) }} Kode</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Grup Master</span>
                    <span class="text-xl font-bold text-indigo-700">{{ number_format($stats['total_grup']) }} Grup</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Master Referensi UPT</span>
                    <span class="text-xl font-bold text-emerald-700">{{ number_format($stats['total_upt']) }} Balai/Loka</span>
                </div>
            </div>
        </div>

        @if ($errors->has('error'))
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700 flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $errors->first('error') }}</span>
            </div>
        @endif

        <!-- Grup Navigation Bar -->
        <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-xs">
            <div class="flex items-center justify-between gap-3 mb-2 px-1">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pilih Grup Master Data:</span>
                <span class="text-xs text-blue-600 font-semibold">Grup Aktif: {{ strtoupper(str_replace('_', ' ', $grupAktif)) }}</span>
            </div>
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-thin">
                @foreach ($daftarGrup as $grp)
                    <a
                        href="{{ route('kamus-kode.index', ['grup' => $grp]) }}"
                        @class([
                            'px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5',
                            'bg-blue-600 text-white shadow-xs' => $grupAktif === $grp,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200' => $grupAktif !== $grp,
                        ])
                    >
                        <span>{{ strtoupper(str_replace('_', ' ', $grp)) }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Filter Bar & Tambah Action -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('kamus-kode.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
                <input type="hidden" name="grup" value="{{ $grupAktif }}">

                <div class="relative min-w-60 flex-1 sm:flex-initial">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="cari"
                        value="{{ $cari }}"
                        placeholder="Cari nama atau kode angka..."
                        class="block w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none"
                    >
                </div>

                <button
                    type="submit"
                    class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition cursor-pointer"
                >
                    Cari
                </button>

                @if ($cari)
                    <a
                        href="{{ route('kamus-kode.index', ['grup' => $grupAktif]) }}"
                        class="text-xs text-red-600 hover:text-red-700 font-medium underline inline-flex items-center gap-1"
                    >
                        Reset Pencarian
                    </a>
                @endif
            </form>

            <button
                type="button"
                @click="$dispatch('open-modal', 'modal-tambah-kode')"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg text-white shadow-xs hover:opacity-95 transition cursor-pointer shrink-0"
                style="background-image: var(--grad-accent);"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ $grupAktif === 'upt' ? 'Tambah UPT Baru' : 'Tambah Kode Standar' }}</span>
            </button>
        </div>

        <!-- Tabel Data Master Kode -->
        <x-card
            title="Daftar {{ $grupAktif === 'upt' ? 'Master Data UPT (Balai Besar / Balai / Loka POM)' : 'Kode Standar: ' . strtoupper(str_replace('_', ' ', $grupAktif)) }}"
            subtitle="{{ $grupAktif === 'upt' ? 'Daftar Unit Pelaksana Teknis (UPT) yang fleksibel dan dapat ditambah atau disesuaikan sewaktu-waktu' : 'Nilai kode resmi yang digunakan sistem dalam standardisasi, visualisasi, dan laporan rekap' }}"
        >
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-center w-24">Kode</th>
                            <th class="px-4 py-3 text-left">
                                {{ $grupAktif === 'upt' ? 'Nama Resmi Unit Pelaksana Teknis (Balai Besar / Balai / Loka POM)' : 'Nama Standar / Deskripsi Resmi' }}
                            </th>
                            <th class="px-4 py-3 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($items as $item)
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                <td class="px-4 py-3 text-center font-mono font-bold text-blue-700 bg-blue-50/30">
                                    {{ $item->kode }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $item->nama }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal({{ Js::from($item) }})"
                                            class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition cursor-pointer"
                                            title="{{ $grupAktif === 'upt' ? 'Ubah Data UPT' : 'Ubah Kode Standar' }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteModal({{ Js::from($item) }})"
                                            class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md transition cursor-pointer"
                                            title="{{ $grupAktif === 'upt' ? 'Hapus UPT' : 'Hapus Kode Standar' }}"
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
                                <td colspan="3" class="px-4 py-8 text-center text-gray-400">
                                    Tidak ada data kode standar yang cocok pada grup ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="mt-4">
                    {{ $items->links() }}
                </div>
            @endif
        </x-card>

        <!-- Modal Tambah Kode Standar / UPT Baru -->
        <x-modal name="modal-tambah-kode" :title="$grupAktif === 'upt' ? 'Tambah UPT Baru ke Master Data' : 'Tambah Kode Standar Baru'" maxWidth="lg">
            <form method="POST" action="{{ route('kamus-kode.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="create_grup" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Grup Master Data <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="grup"
                        id="create_grup"
                        value="{{ old('grup', $grupAktif) }}"
                        required
                        list="grup_suggestions"
                        placeholder="Contoh: upt, jenis_produk, nama_media, klaim..."
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    >
                    <datalist id="grup_suggestions">
                        @foreach ($daftarGrup as $grp)
                            <option value="{{ $grp }}">{{ strtoupper(str_replace('_', ' ', $grp)) }}</option>
                        @endforeach
                    </datalist>
                    <p class="text-[11px] text-gray-500 mt-1">Gunakan <code>upt</code> untuk menambah Balai Besar / Balai / Loka POM baru.</p>
                </div>

                <div>
                    <label for="create_kode" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Kode Angka Standar <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="kode"
                        id="create_kode"
                        value="{{ old('kode', $nextKode ?? '') }}"
                        required
                        min="1"
                        step="1"
                        placeholder="Contoh: {{ $nextKode ?? 84 }}"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    >
                    <p class="text-[11px] text-gray-500 mt-1">Nilai integer unik dalam grup ini (otomatis disarankan urutan berikutnya: <strong>#{{ $nextKode ?? 1 }}</strong>).</p>
                </div>

                <div>
                    <label for="create_nama" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        {{ $grupAktif === 'upt' ? 'Nama Resmi UPT (Balai Besar / Balai / Loka POM)' : 'Nama Standar / Deskripsi Resmi' }} <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="nama"
                        id="create_nama"
                        rows="2"
                        required
                        placeholder="{{ $grupAktif === 'upt' ? 'Contoh: Loka POM di Kabupaten ...' : 'Contoh: OBAT TRADISIONAL (OT)' }}"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    >{{ old('nama') }}</textarea>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-tambah-kode')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg text-white shadow-xs hover:opacity-95 transition cursor-pointer"
                        style="background-image: var(--grad-accent);"
                    >
                        {{ $grupAktif === 'upt' ? 'Simpan UPT Baru' : 'Simpan Kode Standar' }}
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Edit Kode Standar / UPT -->
        <x-modal name="modal-edit-kode" :title="$grupAktif === 'upt' ? 'Ubah Data Master UPT' : 'Ubah Kode Standar'" maxWidth="md">
            <form
                method="POST"
                :action="editItem.grup === 'upt' ? '{{ url('kamus-kode/upt') }}/' + editItem.id : '{{ url('kamus-kode') }}/' + editItem.id"
                class="space-y-4"
            >
                @csrf
                @method('PUT')

                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Grup Master:</span>
                        <span class="font-bold text-gray-800" x-text="editItem.grup ? editItem.grup.toUpperCase() : '-'"></span>
                    </div>
                    <div class="flex justify-between" x-show="editItem.grup !== 'upt'">
                        <span class="text-gray-500 font-medium">Kode Angka:</span>
                        <span class="font-mono font-bold text-blue-700" x-text="editItem.kode"></span>
                    </div>
                </div>

                <div x-show="editItem.grup === 'upt'">
                    <label for="edit_kode" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Kode Angka UPT <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="kode"
                        id="edit_kode"
                        min="1"
                        step="1"
                        x-model="editItem.kode"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 font-mono font-bold focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="edit_nama" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        <span x-text="editItem.grup === 'upt' ? 'Nama Resmi UPT (Balai Besar / Balai / Loka POM)' : 'Nama Standar / Deskripsi Resmi'"></span> <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="nama"
                        id="edit_nama"
                        rows="3"
                        required
                        x-model="editItem.nama"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    ></textarea>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-edit-kode')"
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

        <!-- Modal Hapus Kode Standar / UPT -->
        <x-modal name="modal-hapus-kode" :title="$grupAktif === 'upt' ? 'Konfirmasi Hapus UPT' : 'Konfirmasi Hapus Kode Standar'" maxWidth="sm">
            <form
                method="POST"
                :action="deleteItem.grup === 'upt' ? '{{ url('kamus-kode/upt') }}/' + deleteItem.id : '{{ url('kamus-kode') }}/' + deleteItem.id"
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
                    <h4 class="text-sm font-bold text-gray-900" x-text="deleteItem.grup === 'upt' ? 'Hapus UPT ini dari Master Data?' : 'Hapus kode standar ini dari Master Data?'"></h4>
                    <p class="text-xs text-gray-500 mt-2">
                        Anda akan menghapus kode #<span class="font-bold text-gray-700" x-text="deleteItem.kode"></span>:
                        "<span class="font-semibold text-gray-800" x-text="deleteItem.nama"></span>"
                        pada grup <span class="font-mono font-semibold text-blue-600" x-text="deleteItem.grup"></span>.
                    </p>
                    <p class="text-[11px] text-amber-600 mt-2 bg-amber-50 p-2 rounded border border-amber-200">
                        Peringatan: Jika kode ini sedang digunakan pada data iklan atau aturan konversi SIPT aktif, penghapusan akan dicegah demi integritas data.
                    </p>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-hapus-kode')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 shadow-xs transition cursor-pointer"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
