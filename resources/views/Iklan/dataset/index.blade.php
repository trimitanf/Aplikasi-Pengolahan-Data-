<x-layouts.app title="Dataset Pengawasan Iklan">
    <x-slot:header>
        Dataset Pengawasan Iklan SIPT
    </x-slot:header>
    <x-slot:breadcrumb>
        Dataset
    </x-slot:breadcrumb>


    @php
        $isAdmin = auth()->check() && auth()->user()->isAdmin();
    @endphp

    <!-- Top Stats Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div>
                <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                    {{ $isAdmin ? 'Total Berkas Bulanan (Unik)' : 'Total Dataset SIPT' }}
                </span>
                <span class="text-xl font-bold text-gray-900">{{ number_format($stats['total_dataset'] ?? $datasets->total()) }} Berkas</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M8 4v16" />
                </svg>
            </div>
            <div>
                <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Baris Data Mentah</span>
                <span class="text-xl font-bold text-gray-900">{{ number_format($stats['total_baris'] ?? 0, 0, ',', '.') }} Baris</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                @if ($isAdmin)
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Progres Evaluasi Gabungan</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-xl font-bold text-gray-900">
                            {{ number_format($stats['total_baris_terevaluasi'] ?? 0, 0, ',', '.') }} / {{ number_format($stats['total_wajib_evaluasi'] ?? ($stats['total_baris'] ?? 0), 0, ',', '.') }} Baris
                        </span>
                        <span class="text-[11px] font-semibold text-indigo-600">
                            ({{ number_format($stats['total_valid'] ?? 0) }} Berkas Valid)
                        </span>
                    </div>
                @else
                    <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">Dataset Siap / Valid</span>
                    <span class="text-xl font-bold text-gray-900">{{ number_format($stats['total_valid'] ?? 0) }} Berkas</span>
                @endif
            </div>
        </div>
    </div>

        <!-- Container Tampilan Utama -->
        <div @class([
            'grid grid-cols-1 lg:grid-cols-3 gap-6 items-start' => $isAdmin,
            'space-y-4' => !$isAdmin,
        ])>
            @if ($isAdmin)
                <!-- Form Unggah File SIPT Master (Hanya untuk Admin / PIC Pengawas) -->
                <div class="lg:col-span-1">
                    <x-card title="Unggah Dataset SIPT Master" subtitle="Unggah berkas SIPT bulanan (Cukup 1 kali oleh Admin/PIC)">
                    <form
                        method="POST"
                        action="{{ route('dataset.store') }}"
                        enctype="multipart/form-data"
                        class="space-y-4"
                        x-data="{
                            isDragging: false,
                            file: null,
                            fileName: '',
                            fileSize: '',
                            fileExt: '',
                            errorMessage: '',
                            isSubmitting: false,

                            handleFileSelect(event) {
                                const selected = event.target.files[0];
                                this.processFile(selected);
                            },

                            handleDrop(event) {
                                this.isDragging = false;
                                if (event.dataTransfer.files && event.dataTransfer.files.length > 0) {
                                    const dropped = event.dataTransfer.files[0];
                                    const fileInput = document.getElementById('file');
                                    const dt = new DataTransfer();
                                    dt.items.add(dropped);
                                    fileInput.files = dt.files;
                                    this.processFile(dropped);
                                }
                            },

                            processFile(f) {
                                this.errorMessage = '';
                                if (!f) {
                                    this.clearFile();
                                    return;
                                }

                                const ext = f.name.split('.').pop().toLowerCase();
                                const allowed = ['xls', 'xlsx', 'html', 'htm'];
                                if (!allowed.includes(ext)) {
                                    this.errorMessage = 'Format file tidak didukung. Harap pilih file export SIPT (.xls, .xlsx, atau HTML).';
                                    this.clearFile();
                                    return;
                                }

                                if (f.size > 25 * 1024 * 1024) {
                                    this.errorMessage = 'Ukuran file melebihi batas 25 MB (' + (f.size / (1024 * 1024)).toFixed(1) + ' MB).';
                                    this.clearFile();
                                    return;
                                }

                                this.file = f;
                                this.fileName = f.name;
                                this.fileExt = ext.toUpperCase();
                                this.fileSize = f.size > (1024 * 1024)
                                    ? (f.size / (1024 * 1024)).toFixed(2) + ' MB'
                                    : (f.size / 1024).toFixed(1) + ' KB';
                            },

                            clearFile() {
                                this.file = null;
                                this.fileName = '';
                                this.fileSize = '';
                                this.fileExt = '';
                                const input = document.getElementById('file');
                                if (input) input.value = '';
                            }
                        }"
                        @submit="if(fileName) isSubmitting = true"
                    >
                        @csrf

                        <!-- Komoditi -->
                        <div class="space-y-1.5">
                            <label for="komoditi" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                                Komoditi <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="komoditi"
                                name="komoditi"
                                class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none bg-white"
                                required
                            >
                                <option value="">-- Pilih Komoditi --</option>
                                <option value="OBA" @selected(old('komoditi') === 'OBA' || old('komoditi') === 'OT')>Obat Bahan Alam (OBA)</option>
                                <option value="OK" @selected(old('komoditi') === 'OK')>Obat Kuasi (OK)</option>
                                <option value="SK" @selected(old('komoditi') === 'SK')>Suplemen Kesehatan (SK)</option>
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
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 focus:outline-none bg-white"
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

                        <!-- Interactive File Ekspor SIPT Dropzone & Preview -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label for="file" class="block text-xs font-semibold text-gray-700 tracking-wide uppercase">
                                    File Ekspor SIPT <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] text-gray-400 font-medium">Maks. 25 MB</span>
                            </div>

                            <!-- Hidden Native File Input -->
                            <input
                                id="file"
                                name="file"
                                type="file"
                                class="sr-only"
                                required
                                accept=".xls,.xlsx,.html,.htm"
                                @change="handleFileSelect($event)"
                            />

                            <!-- Dropzone Area (Displayed when no file chosen yet) -->
                            <div
                                x-show="!fileName"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleDrop($event)"
                                @click="document.getElementById('file').click()"
                                :class="isDragging ? 'border-blue-500 bg-blue-50/70 ring-4 ring-blue-500/10' : 'border-gray-300 hover:border-blue-400 bg-gray-50/60 hover:bg-blue-50/20'"
                                class="cursor-pointer border-2 border-dashed rounded-xl p-5 text-center transition-all duration-200"
                            >
                                <div class="space-y-2">
                                    <div class="w-12 h-12 mx-auto rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                    </div>
                                    <div class="text-xs text-gray-700">
                                        <span class="font-bold text-blue-600 hover:underline">Pilih file dari komputer</span> atau tarik & lepas ke sini
                                    </div>
                                    <p class="text-[11px] text-gray-400">Format .xls (HTML SIPT) atau .xlsx</p>
                                </div>
                            </div>

                            <!-- File Selected Preview Card -->
                            <div
                                x-show="fileName"
                                x-cloak
                                class="border border-emerald-300 bg-emerald-50/60 rounded-xl p-3.5 shadow-xs transition-all duration-200"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                            <!-- Spreadsheet Icon -->
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="font-bold text-gray-900 text-xs truncate max-w-44" x-text="fileName" :title="fileName"></span>
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 uppercase" x-text="fileExt"></span>
                                            </div>
                                            <div class="text-[11px] text-gray-500 mt-0.5" x-text="fileSize"></div>
                                            <div class="inline-flex items-center gap-1 mt-1 text-[11px] font-semibold text-emerald-700">
                                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Dokumen berhasil dipilih & siap diunggah</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 shrink-0">
                                        <button
                                            type="button"
                                            @click="document.getElementById('file').click()"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                            title="Ganti File"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="clearFile()"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                                            title="Hapus / Batalkan"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Error Message if Validation Fails -->
                            <div x-show="errorMessage" x-cloak class="p-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="errorMessage"></span>
                            </div>

                            @error('file')
                                <p class="text-xs font-medium text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button with Loading State -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold text-white shadow-sm hover:opacity-95 transition-all disabled:opacity-75 disabled:cursor-not-allowed cursor-pointer"
                                style="background-image: var(--grad-accent);"
                            >
                                <template x-if="!isSubmitting">
                                    <div class="inline-flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        <span>Unggah & Proses Validasi</span>
                                    </div>
                                </template>
                                <template x-if="isSubmitting">
                                    <div class="inline-flex items-center gap-2">
                                        <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Mengunggah & Memvalidasi...</span>
                                    </div>
                                </template>
                            </button>
                        </div>

                        <!-- Guidance Info Box -->
                        <div class="pt-1">
                            <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-100 flex items-start gap-2.5 text-xs text-blue-900">
                                <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-[11px] leading-relaxed">
                                    <strong>Dokumen Ekspor SIPT:</strong>
                                    Format yang didukung adalah hasil ekspor resmi dari sistem SIPT BPOM (26 kolom data mentah). Sistem akan membaca dan memvalidasi keabsahan data secara otomatis.
                                </div>
                            </div>
                        </div>
                    </form>
                </x-card>
            </div>
            @endif

            <div @class([
                'lg:col-span-2 space-y-4' => $isAdmin,
                'space-y-4' => !$isAdmin,
            ])>
            <!-- Table of Datasets dengan Filter Sejajar di Header -->
            <x-card title="Daftar Berkas Master Dataset SIPT">
                <x-slot:headerAction>
                    <form method="GET" action="{{ route('dataset.index') }}" class="flex flex-wrap items-center gap-2">
                        <!-- Komoditi -->
                        <select
                            name="komoditi"
                            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-800 shadow-2xs focus:border-blue-600 focus:outline-none cursor-pointer shrink-0"
                            onchange="this.form.submit()"
                            title="Filter Komoditi"
                        >
                            <option value="">Semua Komoditi</option>
                            <option value="OBA" @selected(($filters['komoditi'] ?? null) === 'OBA' || ($filters['komoditi'] ?? null) === 'OT')>Obat Bahan Alam (OBA)</option>
                            <option value="OK" @selected(($filters['komoditi'] ?? null) === 'OK')>Obat Kuasi (OK)</option>
                            <option value="SK" @selected(($filters['komoditi'] ?? null) === 'SK')>Suplemen Kesehatan (SK)</option>
                        </select>

                        @if (!$isAdmin)
                            <!-- UPT -->
                            <select
                                name="upt"
                                class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-800 shadow-2xs focus:border-blue-600 focus:outline-none cursor-pointer shrink-0 max-w-44"
                                onchange="this.form.submit()"
                                title="Filter UPT"
                            >
                                <option value="">Semua UPT</option>
                                @foreach ($uptList as $uptItem)
                                    <option value="{{ $uptItem->nama }}" @selected(($filters['upt'] ?? null) === $uptItem->nama || ($filters['upt'] ?? null) === (string) $uptItem->kode)>
                                        {{ $uptItem->nama }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <!-- Tahun -->
                        <select
                            name="tahun"
                            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-800 shadow-2xs focus:border-blue-600 focus:outline-none cursor-pointer shrink-0"
                            onchange="this.form.submit()"
                            title="Filter Tahun"
                        >
                            <option value="">Semua Tahun</option>
                            @for ($y = now()->year + 1; $y >= 2024; $y--)
                                <option value="{{ $y }}" @selected($filters['tahun'] == $y)>{{ $y }}</option>
                            @endfor
                        </select>

                        <!-- Status -->
                        <select
                            name="status"
                            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-800 shadow-2xs focus:border-blue-600 focus:outline-none cursor-pointer shrink-0"
                            onchange="this.form.submit()"
                            title="Filter Status"
                        >
                            <option value="">Semua Status</option>
                            <option value="Diproses" @selected($filters['status'] === 'Diproses')>Diproses</option>
                            <option value="Sudah Diproses" @selected($filters['status'] === 'Sudah Diproses')>Sudah Diproses</option>
                        </select>

                        <!-- Reset Filter (Satu Baris Sejajar) -->
                        <a
                            href="{{ route('dataset.index') }}"
                            class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-2xs whitespace-nowrap cursor-pointer shrink-0"
                            title="Reset semua filter ke kondisi awal"
                        >
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset</span>
                        </a>

                        <!-- Ekspor Excel Daftar Dataset -->
                        <a
                            href="{{ route('dataset.export-list', request()->query()) }}"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-300 bg-emerald-50 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition-colors shadow-2xs whitespace-nowrap cursor-pointer shrink-0"
                            title="Ekspor seluruh daftar dataset ke file Excel (.xlsx)"
                        >
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Ekspor Excel</span>
                        </a>

                        @if ($isAdmin && ($stats['total_sampah'] ?? 0) > 0)
                            <a
                                href="{{ route('dataset.trash') }}"
                                class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-red-200 bg-red-50 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors shadow-2xs whitespace-nowrap cursor-pointer shrink-0"
                                title="Buka Kotak Sampah Dataset"
                            >
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Kotak Sampah ({{ $stats['total_sampah'] }})</span>
                            </a>
                        @endif
                    </form>
                </x-slot:headerAction>
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-slate-50 text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                            <tr>
                                <th class="px-3.5 py-3 text-left whitespace-nowrap">Komoditi</th>
                                <th class="px-3.5 py-3 text-left whitespace-nowrap">Periode</th>
                                <th class="px-3.5 py-3 text-left whitespace-nowrap">Dokumen File SIPT</th>
                                <th class="px-3.5 py-3 text-left whitespace-nowrap">Progres Evaluasi</th>
                                <th class="px-3.5 py-3 text-left whitespace-nowrap">Waktu</th>
                                <th class="px-3.5 py-3 text-right whitespace-nowrap">Baris Data</th>
                                <th class="px-3.5 py-3 text-center whitespace-nowrap">Status</th>
                                <th class="px-3.5 py-3 text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($datasets as $item)
                                @php
                                    $groupedSessions = $item->groupedSessions ?? collect([$item]);
                                    $hasMultipleSessions = $groupedSessions->count() > 1;
                                    $evaluatorNames = $groupedSessions->map(fn($s) => $s->uploader?->name ?? 'Evaluator')->unique()->values();
                                    $evaluatedCount = (int) ($item->evaluated_count ?? 0);
                                    $wajibEvaluasi = (int) ($item->wajib_evaluasi ?? $item->jumlah_baris);
                                    $progressPercent = (int) ($item->progress_percent ?? 0);
                                    $isGroupComplete = (bool) ($item->is_group_complete ?? $item->isFinal());
                                @endphp
                                <tr class="hover:bg-blue-50/30 transition-colors align-middle">
                                    <!-- Komoditi -->
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        @if ($item->komoditi === 'SK')
                                            <div class="inline-flex items-center gap-2">
                                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">SK</span>
                                                <span class="text-[11px] text-gray-600 font-medium hidden xl:inline">Suplemen Kesehatan</span>
                                            </div>
                                        @elseif ($item->komoditi === 'OK')
                                            <div class="inline-flex items-center gap-2">
                                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">OK</span>
                                                <span class="text-[11px] text-gray-600 font-medium hidden xl:inline">Obat Kuasi</span>
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-2">
                                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">OBA</span>
                                                <span class="text-[11px] text-gray-600 font-medium hidden xl:inline">Obat Bahan Alam</span>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Periode -->
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <a
                                            href="{{ route('dataset.show', $item) }}"
                                            class="group block"
                                            title="Klik untuk membuka tabel 26 kolom SIPT gabungan"
                                        >
                                            <div class="font-bold text-gray-900 group-hover:text-blue-600 text-xs flex items-center gap-1 transition-colors">
                                                <span class="group-hover:underline underline-offset-2">{{ $item->nama_bulan }} {{ $item->periode_tahun }}</span>
                                                <svg class="w-3.5 h-3.5 text-blue-500 opacity-60 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                        </a>
                                        <span class="text-[10px] text-gray-400 block mt-0.5">
                                            Diunggah: {{ $item->uploader?->name ?? 'Admin/PIC' }}
                                        </span>
                                    </td>

                                    <!-- Dokumen File SIPT -->
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0 max-w-52 xl:max-w-72">
                                                <a
                                                    href="{{ route('dataset.show', $item) }}"
                                                    class="font-semibold text-xs text-gray-900 hover:text-blue-600 truncate block"
                                                    title="{{ $item->nama_file_asli }}"
                                                >
                                                    {{ $item->nama_file_asli }}
                                                </a>
                                                <div class="flex items-center gap-2 mt-0.5 whitespace-nowrap">
                                                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-gray-100 text-gray-600 uppercase">
                                                        {{ pathinfo($item->nama_file_asli, PATHINFO_EXTENSION) ?: 'XLS' }}
                                                    </span>
                                                    <a
                                                        href="{{ route('dataset.download', $item) }}"
                                                        class="inline-flex items-center gap-1 text-[11px] text-blue-600 hover:text-blue-800 font-medium"
                                                        title="Unduh dokumen asli dari server"
                                                    >
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        <span>Unduh Asli</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Progres Evaluasi Gabungan -->
                                    <td class="px-3.5 py-3 whitespace-nowrap min-w-44">
                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                            <span class="font-bold text-gray-800">
                                                {{ number_format($evaluatedCount, 0, ',', '.') }} / {{ number_format($wajibEvaluasi, 0, ',', '.') }} Baris
                                            </span>
                                            <span class="font-bold {{ $isGroupComplete ? 'text-emerald-600' : ($evaluatedCount > 0 ? 'text-blue-600' : 'text-gray-400') }}">
                                                {{ $progressPercent }}%
                                            </span>
                                        </div>
                                        <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-300 {{ $isGroupComplete ? 'bg-emerald-500' : ($evaluatedCount > 0 ? 'bg-blue-500' : 'bg-gray-300') }}"
                                                style="width: {{ $progressPercent }}%"
                                            ></div>
                                        </div>
                                    </td>

                                    <!-- Waktu -->
                                    @php
                                        $waktuProses = $item->updated_at ?? $item->uploaded_at ?? $item->created_at ?? ($item->uploader?->last_login_at ?? auth()->user()?->last_login_at ?? now());
                                    @endphp
                                    <td class="px-3.5 py-3 text-left whitespace-nowrap">
                                        <span class="font-bold text-gray-900 text-xs block">{{ $waktuProses->translatedFormat('d F Y') }}</span>
                                        <span class="text-[10px] text-gray-500 font-mono block mt-0.5">
                                            {{ $waktuProses->format('H:i:s') }} WIB
                                        </span>
                                    </td>

                                    <!-- Baris Data -->
                                    <td class="px-3.5 py-3 text-right whitespace-nowrap">
                                        <span class="font-bold text-gray-900 text-xs block">{{ number_format($item->jumlah_baris, 0, ',', '.') }}</span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-3.5 py-3 text-center whitespace-nowrap">
                                        @if ($isGroupComplete)
                                            <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                Sudah Diproses
                                            </span>
                                        @elseif ($isAdmin && $evaluatedCount > 0)
                                            <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                Sedang Dievaluasi
                                            </span>
                                        @else
                                            <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold bg-red-100 text-red-700 border border-red-200">
                                                Diproses
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-3.5 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <a
                                                href="{{ route('dataset.show', $item) }}"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-2xs transition-colors"
                                                title="Buka Tabel Gabungan (26 Kolom SIPT)"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>{{ $isAdmin ? 'Buka Tabel' : 'Buka & Evaluasi' }}</span>
                                            </a>
                                            @if ($isAdmin && $item->canBeDeleted())
                                                <form method="POST" action="{{ route('dataset.destroy', $item) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin memindahkan berkas dataset ini ke Kotak Sampah?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                                                        title="Pindahkan ke Kotak Sampah"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center">
                                        <x-empty-state
                                            title="Belum Ada Dataset Terdaftar"
                                            description="{{ $isAdmin ? 'Belum ada berkas dataset yang diunggah. Silakan gunakan formulir di samping untuk mengunggah dataset SIPT.' : 'Belum ada berkas master dataset SIPT yang diunggah oleh Admin/PIC. Silakan hubungi Administrator untuk ketersediaan data periode ini.' }}"
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
