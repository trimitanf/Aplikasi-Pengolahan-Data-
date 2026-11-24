<x-layouts.app title="Pengaturan Sistem">
    <x-slot:header>
        Pengaturan Sistem & Master Data
    </x-slot:header>
    <x-slot:breadcrumb>
        Pengaturan
    </x-slot:breadcrumb>

    <!-- Navigation Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6">
            <a
                href="{{ route('pengaturan.index', ['tab' => 'target_iku']) }}"
                @class([
                    'pb-4 text-sm font-semibold border-b-2 flex items-center gap-2 transition-colors',
                    'border-blue-600 text-blue-600' => $tabAktif === 'target_iku',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $tabAktif !== 'target_iku',
                ])
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Target IKU</span>
            </a>

            <a
                href="{{ route('pengaturan.index', ['tab' => 'kamus_kode']) }}"
                @class([
                    'pb-4 text-sm font-semibold border-b-2 flex items-center gap-2 transition-colors',
                    'border-blue-600 text-blue-600' => $tabAktif === 'kamus_kode',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $tabAktif !== 'kamus_kode',
                ])
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span>Kamus Kode Standar (Read-only)</span>
            </a>

            <a
                href="{{ route('pengaturan.index', ['tab' => 'pemetaan']) }}"
                @class([
                    'pb-4 text-sm font-semibold border-b-2 flex items-center gap-2 transition-colors',
                    'border-blue-600 text-blue-600' => $tabAktif === 'pemetaan',
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $tabAktif !== 'pemetaan',
                ])
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <span>Aturan Konversi SIPT</span>
            </a>
        </nav>
    </div>

    <!-- TAB 1: TARGET IKU -->
    @if ($tabAktif === 'target_iku')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Update Target IKU -->
            <div class="lg:col-span-1">
                <x-card title="Ubah / Tambah Target IKU" subtitle="Tetapkan target persentase capaian Memenuhi Ketentuan (MK)">
                    <form method="POST" action="{{ route('pengaturan.target-iku.update') }}" class="space-y-4">
                        @csrf

                        <x-input
                            label="Tahun Periode"
                            name="tahun"
                            type="number"
                            min="2020"
                            max="2100"
                            :value="old('tahun', 2026)"
                            required
                            help="Contoh: 2026"
                        />

                        <div class="space-y-1.5">
                            <label for="kelompok" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                                Kelompok Komoditi <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="kelompok"
                                name="kelompok"
                                class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none"
                                required
                            >
                                <option value="OBA" @selected(old('kelompok') === 'OBA')>Obat Bahan Alam (OBA) - OT & OK</option>
                                <option value="SK" @selected(old('kelompok') === 'SK')>Suplemen Kesehatan (SK)</option>
                            </select>
                            @error('kelompok')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-input
                            label="Nilai Target (%)"
                            name="target"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            :value="old('target')"
                            placeholder="Contoh: 67.00"
                            required
                            help="Masukkan angka persentase target MK (0 - 100)"
                        />

                        <div class="pt-2">
                            <x-button type="submit" variant="primary" class="w-full">
                                Simpan Target IKU
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </div>

            <!-- Table of Configured Targets -->
            <div class="lg:col-span-2">
                <x-card title="Daftar Target IKU Tersimpan" subtitle="Digunakan otomatis pada perhitungan capaian IKU di lembar Rekap">
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">Tahun</th>
                                    <th class="px-4 py-3 text-left">Kelompok</th>
                                    <th class="px-4 py-3 text-right">Target MK</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3 text-right">Diperbarui</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($targetIkuList as $item)
                                    <tr class="hover:bg-gray-50/70 transition-colors">
                                        <td class="px-4 py-3.5 font-bold text-gray-900">{{ $item->tahun }}</td>
                                        <td class="px-4 py-3.5">
                                            @if ($item->kelompok === 'OBA')
                                                <x-badge variant="primary">Obat Bahan Alam (OBA)</x-badge>
                                            @else
                                                <x-badge variant="success">Suplemen Kesehatan (SK)</x-badge>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-right font-extrabold text-(--brand-navy)">
                                            {{ number_format($item->target, 2, ',', '.') }}%
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            <x-badge variant="neutral" size="sm">Aktif</x-badge>
                                        </td>
                                        <td class="px-4 py-3.5 text-right text-xs text-gray-400">
                                            {{ $item->updated_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                            Belum ada target IKU yang disimpan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    @endif

    <!-- TAB 2: KAMUS KODE (READ-ONLY) -->
    @if ($tabAktif === 'kamus_kode')
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-(--border) shadow-2xs">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Kamus Kode Standar (Backend Reference)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Berpedoman pada sheet Master Data / Coding Standar BPOM. Data bersifat read-only.</p>
                </div>

                <!-- Group Selector -->
                <div class="flex items-center gap-2">
                    <label for="pilihGrup" class="text-xs font-semibold text-gray-600">Pilih Grup Master:</label>
                    <select
                        id="pilihGrup"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-800 font-medium focus:border-blue-600 focus:outline-none"
                        onchange="location.href = '{{ route('pengaturan.index', ['tab' => 'kamus_kode']) }}&grup=' + this.value"
                    >
                        @foreach ($daftarGrup as $grup)
                            <option value="{{ $grup }}" @selected($grupAktif === $grup)>
                                {{ strtoupper(str_replace('_', ' ', $grup)) }}
                            </option>
                        @endforeach
                        <option value="upt" @selected($grupAktif === 'upt')>DAFTAR UPT BPOM</option>
                    </select>
                </div>
            </div>

            @if ($grupAktif === 'upt')
                <!-- Table for Master UPTs -->
                <x-card title="Daftar Balai Besar / Balai / Loka POM (UPT) ({{ $uptList->count() }} UPT)">
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-center w-20">Kode Angka</th>
                                    <th class="px-4 py-3 text-left">Nama Resmi Unit Pelaksana Teknis (UPT)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($uptList as $upt)
                                    <tr class="hover:bg-blue-50/40 transition-colors">
                                        <td class="px-4 py-2.5 text-center font-bold text-blue-700 bg-blue-50/20">
                                            {{ $upt->kode }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-800 font-medium">
                                            {{ $upt->nama }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @else
                <!-- Table for MasterKode Group -->
                <x-card :title="'Daftar Kode Master: ' . strtoupper(str_replace('_', ' ', $grupAktif))">
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-center w-24">Kode Angka</th>
                                    <th class="px-4 py-3 text-left">Deskripsi Standar</th>
                                    @if ($grupAktif === 'kesesuaian_evaluasi')
                                        <th class="px-4 py-3 text-left">Keterangan</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($masterKodes as $item)
                                    <tr class="hover:bg-blue-50/40 transition-colors">
                                        <td class="px-4 py-3 text-center font-bold text-blue-700 bg-blue-50/20">
                                            {{ $item->kode }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-800 font-medium">
                                            {{ $item->nama }}
                                        </td>
                                        @if ($grupAktif === 'kesesuaian_evaluasi')
                                            <td class="px-4 py-3 text-xs text-emerald-700 font-medium">
                                                Otomatis dari kolom KESESUAIAN HASIL EVALUASI SIPT
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-400">
                                            Tidak ada data untuk grup ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif
        </div>
    @endif

    <!-- TAB 3: ATURAN KONVERSI (PEMETAAN NILAI) -->
    @if ($tabAktif === 'pemetaan')
        <div class="space-y-6">
            <x-card title="Aturan Pemetaan Nilai Mentah SIPT ke Kode Angka" subtitle="Aturan pencocokan otomatis rule-based saat proses standardisasi dijalankan">
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left">Grup Master</th>
                                <th class="px-4 py-3 text-left">Kolom Sumber SIPT</th>
                                <th class="px-4 py-3 text-left">Teks Mentah SIPT (Ternormalisasi)</th>
                                <th class="px-4 py-3 text-center">Kode Hasil</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($pemetaanList as $peta)
                                <tr class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-gray-700 text-xs">
                                        {{ strtoupper(str_replace('_', ' ', $peta->grup)) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 font-mono">
                                        {{ $peta->kolom_sumber }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $peta->nilai_sipt }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-blue-700 bg-blue-50/30">
                                        {{ $peta->kode }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <x-badge :variant="$peta->is_active ? 'success' : 'neutral'" size="sm">
                                            {{ $peta->is_active ? 'Aktif' : 'Non-aktif' }}
                                        </x-badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        Belum ada aturan pemetaan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    @endif
</x-layouts.app>
