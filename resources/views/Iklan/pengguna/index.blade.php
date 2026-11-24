<x-layouts.app title="Kelola Pengguna">
    <x-slot:header>
        Kelola Pengguna Sistem
    </x-slot:header>
    <x-slot:breadcrumb>
        Kelola Pengguna
    </x-slot:breadcrumb>

    <div
        x-data="{
            editUser: {
                id: null,
                name: '',
                username: '',
                role: 'evaluator',
                is_active: true
            },
            userToDelete: {
                id: null,
                name: ''
            },
            openEditModal(user) {
                this.editUser = {
                    id: user.id,
                    name: user.name,
                    username: user.username,
                    role: user.role,
                    is_active: Boolean(user.is_active)
                };
                if (this.$refs.editUserForm) {
                    this.$refs.editUserForm.action = '/pengguna/' + user.id;
                }
                $dispatch('open-modal', 'modal-edit-pengguna');
            },
            openDeleteModal(user) {
                this.userToDelete = {
                    id: user.id,
                    name: user.name
                };
                if (this.$refs.deleteUserForm) {
                    this.$refs.deleteUserForm.action = '/pengguna/' + user.id;
                }
                $dispatch('open-modal', 'modal-hapus-pengguna');
            }
        }"
        x-init="
            @if (isset($errors) && $errors->any() && old('_modal') === 'edit')
                editUser = {
                    id: '{{ old('user_id') }}',
                    name: {{ Js::from(old('name', '')) }},
                    username: {{ Js::from(old('username', '')) }},
                    role: '{{ old('role', 'evaluator') }}',
                    is_active: {{ old('is_active') ? 'true' : 'false' }}
                };
                $nextTick(() => {
                    if ($refs.editUserForm && editUser.id) {
                        $refs.editUserForm.action = '/pengguna/' + editUser.id;
                    }
                    $dispatch('open-modal', 'modal-edit-pengguna');
                });
            @elseif (isset($errors) && $errors->any() && old('_modal') === 'tambah')
                $nextTick(() => {
                    $dispatch('open-modal', 'modal-tambah-pengguna');
                });
            @endif
        "
        class="space-y-6"
    >
        <!-- Top Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Pengguna</span>
                    <span class="text-xl font-bold text-gray-900">{{ number_format($stats['total_user']) }} Akun</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Administrator</span>
                    <span class="text-xl font-bold text-purple-700">{{ number_format($stats['total_admin']) }} Orang</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Evaluator</span>
                    <span class="text-xl font-bold text-sky-700">{{ number_format($stats['total_evaluator']) }} Orang</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Akun Aktif</span>
                    <span class="text-xl font-bold text-emerald-600">{{ number_format($stats['total_aktif']) }} Akun</span>
                </div>
            </div>
        </div>

        <!-- Filter Bar & Tambah Action -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('pengguna.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
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
                        placeholder="Cari nama atau username..."
                        class="block w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-gray-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none"
                    >
                </div>

                <!-- Role Filter -->
                <div class="w-full sm:w-auto">
                    <select
                        name="role"
                        onchange="this.form.submit()"
                        class="w-full sm:w-auto px-3 py-2 text-xs rounded-lg border border-gray-300 bg-white text-gray-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 outline-none cursor-pointer"
                    >
                        <option value="">Semua Role</option>
                        <option value="admin" @selected($filterRole === 'admin')>Administrator</option>
                        <option value="evaluator" @selected($filterRole === 'evaluator')>Evaluator Iklan</option>
                        <option value="evaluator_penandaan" @selected($filterRole === 'evaluator_penandaan')>Evaluator Penandaan</option>
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

                @if ($cari || $filterRole || $filterStatus)
                    <a
                        href="{{ route('pengguna.index') }}"
                        class="text-xs text-red-600 hover:text-red-700 font-medium underline inline-flex items-center gap-1"
                    >
                        Reset Filter
                    </a>
                @endif
            </form>

            <button
                type="button"
                @click="$dispatch('open-modal', 'modal-tambah-pengguna')"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold text-white shadow-xs hover:opacity-95 transition cursor-pointer shrink-0"
                style="background-image: var(--grad-accent);"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span>Tambah Pengguna Baru</span>
            </button>
        </div>

        <!-- Table of Users -->
        <x-card title="Daftar Pengguna Sistem" subtitle="Kelola hak akses dan akun evaluator serta administrator">
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5 text-center w-12">No</th>
                            <th class="px-4 py-3.5 text-left">Pengguna</th>
                            <th class="px-4 py-3.5 text-left">Username</th>
                            <th class="px-4 py-3.5 text-center">Role</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-center">Terdaftar Sejak</th>
                            <th class="px-4 py-3.5 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($users as $u)
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="px-4 py-3 text-center text-xs text-gray-500 font-medium">
                                    {{ $users->firstItem() + $loop->index }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-linear-to-br from-blue-500 to-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 text-sm flex items-center gap-2">
                                                <span>{{ $u->name }}</span>
                                                @if ($u->id === auth()->id())
                                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                        Anda
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-400">ID: #{{ $u->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs font-mono font-medium text-gray-700">
                                    {{ $u->username }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($u->role === 'admin')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-200">
                                            <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Administrator
                                        </span>
                                    @elseif ($u->role === 'evaluator_penandaan')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-800 border border-teal-200">
                                            <svg class="w-3 h-3 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                            Evaluator Penandaan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-200">
                                            <svg class="w-3 h-3 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Evaluator Iklan
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <x-badge :variant="$u->is_active ? 'success' : 'neutral'" size="sm">
                                        {{ $u->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 text-center text-xs text-gray-500">
                                    {{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <!-- Edit Button -->
                                        <button
                                            type="button"
                                            @click="openEditModal({{ Js::from($u) }})"
                                            title="Ubah data pengguna"
                                            class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-200 transition cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Delete Button -->
                                        @if ($u->id !== auth()->id())
                                            <button
                                                type="button"
                                                @click="openDeleteModal({{ Js::from($u) }})"
                                                title="Hapus akun pengguna"
                                                class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 transition cursor-pointer"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @else
                                            <span class="p-1.5 text-gray-300" title="Tidak dapat menghapus akun Anda sendiri">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <p class="text-sm font-semibold text-gray-600">Tidak ada pengguna yang cocok</p>
                                        <p class="text-xs text-gray-400 mt-1">Ubah kata kunci pencarian atau reset filter untuk menampilkan data</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            @endif
        </x-card>

        <!-- Modal Tambah Pengguna Baru -->
        <x-modal name="modal-tambah-pengguna" title="Tambah Pengguna Baru (Unit Kerja Tim Iklan dan Penandaan)" maxWidth="lg">
            <form method="POST" action="{{ route('pengguna.store') }}" class="space-y-4" autocomplete="off">
                @csrf
                <input type="hidden" name="_modal" value="tambah">

                @if (isset($errors) && $errors->any() && old('_modal') === 'tambah')
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Gagal menambahkan pengguna baru:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 pl-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label for="create_name" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        NAMA LENGKAP <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="create_name"
                        required
                        placeholder="Contoh: Mita / Amira"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    >
                    <p class="text-[11px] text-gray-500 mt-1">Digunakan langsung pada kolom input <strong>NAMA</strong> saat login.</p>
                </div>

                <div>
                    <label for="create_username" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        ID / Username Singkat <span class="text-xs font-normal text-gray-400 lowercase">(Opsional, otomatis dari Nama jika dikosongkan)</span>
                    </label>
                    <input
                        type="text"
                        name="username"
                        id="create_username"
                        placeholder="Contoh: mita / amira"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none font-mono"
                    >
                </div>

                <div>
                    <label for="create_password" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        NIP (Nomor Induk Pegawai) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="password"
                        id="create_password"
                        required
                        minlength="6"
                        placeholder="Masukkan NIP (minimal 6 karakter/digit)"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none font-mono"
                    >
                    <p class="text-[11px] text-gray-500 mt-1">Digunakan pada kolom input <strong>NIP</strong> saat login ke sistem.</p>
                </div>

                <div>
                    <label for="create_role" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Role / Hak Akses <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="role"
                        id="create_role"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none bg-white"
                    >
                        <option value="evaluator" selected>Evaluator Iklan (Menu: Pengawasan Iklan)</option>
                        <option value="evaluator_penandaan">Evaluator Penandaan (Menu: Pengawasan Penandaan)</option>
                        <option value="admin">Administrator (Akses Penuh: Iklan, Penandaan, Pengaturan Sistem)</option>
                    </select>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_active"
                        id="create_is_active"
                        value="1"
                        checked
                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer"
                    >
                    <label for="create_is_active" class="text-xs font-medium text-gray-700 cursor-pointer select-none">
                        Status Akun Aktif (Dapat langsung digunakan untuk login)
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-tambah-pengguna')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg text-white shadow-xs hover:opacity-95 transition cursor-pointer"
                        style="background-image: var(--grad-accent);"
                    >
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Edit Pengguna -->
        <x-modal name="modal-edit-pengguna" title="Ubah Data Pengguna (Nama & NIP)" maxWidth="lg">
            <form
                x-ref="editUserForm"
                method="POST"
                :action="'/pengguna/' + editUser.id"
                action="{{ route('pengguna.update.fallback') }}"
                class="space-y-4"
                autocomplete="off"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="user_id" :value="editUser.id">
                <input type="hidden" name="_modal" value="edit">

                @if (isset($errors) && $errors->any() && old('_modal') === 'edit')
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Gagal memperbarui data pengguna:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 pl-1">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-200 flex items-center justify-between text-xs">
                    <span class="text-gray-500 font-medium">ID Pengguna Sistem:</span>
                    <span class="font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200" x-text="'#' + editUser.id"></span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        NAMA LENGKAP <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        x-model="editUser.name"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        ID / Username Login <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="username"
                        x-model="editUser.username"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none font-mono"
                    >
                    <p class="text-[11px] text-gray-500 mt-1">Digunakan sebagai ID saat masuk sistem (boleh huruf, angka, titik, strip, garis bawah).</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        NIP Baru <span class="text-xs font-normal text-gray-400 lowercase">(Kosongkan jika NIP tidak diubah)</span>
                    </label>
                    <input
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        placeholder="Isi hanya jika ingin memperbarui NIP login (min. 6 karakter)"
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none font-mono"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 tracking-wide uppercase mb-1">
                        Role / Hak Akses <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="role"
                        x-model="editUser.role"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none bg-white"
                    >
                        <option value="evaluator">Evaluator Iklan (Menu: Pengawasan Iklan)</option>
                        <option value="evaluator_penandaan">Evaluator Penandaan (Menu: Pengawasan Penandaan)</option>
                        <option value="admin">Administrator (Akses Penuh: Iklan, Penandaan, Pengaturan Sistem)</option>
                    </select>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="is_active"
                        id="edit_is_active"
                        value="1"
                        x-model="editUser.is_active"
                        class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer"
                    >
                    <label for="edit_is_active" class="text-xs font-medium text-gray-700 cursor-pointer select-none">
                        Status Akun Aktif
                    </label>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-edit-pengguna')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg text-white shadow-xs hover:opacity-95 transition cursor-pointer"
                        style="background-image: var(--grad-accent);"
                    >
                        Perbarui Data
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Modal Konfirmasi Hapus Pengguna -->
        <x-modal name="modal-hapus-pengguna" title="Konfirmasi Hapus Pengguna" maxWidth="md">
            <form
                x-ref="deleteUserForm"
                method="POST"
                :action="'/pengguna/' + userToDelete.id"
                action="{{ route('pengguna.destroy.fallback') }}"
                class="space-y-4"
            >
                @csrf
                @method('DELETE')
                <input type="hidden" name="user_id" :value="userToDelete.id">

                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Apakah Anda yakin ingin menghapus akun ini?</h4>
                        <p class="text-xs text-gray-600 mt-1">
                            Akun pengguna <span class="font-bold text-gray-800" x-text="userToDelete.name"></span> akan dihapus permanen dari sistem dan tidak dapat dipulihkan.
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$dispatch('close-modal', 'modal-hapus-pengguna')"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white shadow-xs transition cursor-pointer"
                    >
                        Ya, Hapus Pengguna
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.app>
