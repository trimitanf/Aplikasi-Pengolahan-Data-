@props([
    'activeStep' => 1, // 1 to 6
    'datasetId' => null,
])

@php
    $steps = [
        1 => [
            'label' => 'Unggah',
            'desc' => 'Upload file SIPT',
            'url' => route('dataset.index'),
            'title' => 'Tahap 1: Unggah & Daftar Dataset',
        ],
        2 => [
            'label' => 'Validasi',
            'desc' => 'Pengecekan data',
            'url' => $datasetId ? route('dataset.show', $datasetId) : route('dataset.index'),
            'title' => 'Tahap 2: Validasi & Detail Pengecekan Data',
        ],
        3 => [
            'label' => 'Data Mentah & Manual',
            'desc' => '26 kolom & 2 manual',
            'url' => $datasetId ? route('dataset.show', $datasetId) : route('dataset.index'),
            'title' => 'Tahap 3: Tabel Data Mentah (26 Kolom SIPT & 2 Kolom Input Manual)',
        ],
        4 => [
            'label' => 'Standarisasi',
            'desc' => '18 kolom kode angka',
            'url' => $datasetId ? route('dataset.standardisasi', $datasetId) : route('standarisasi.index'),
            'title' => 'Tahap 4: Tabel Standarisasi Data (18 Kolom Kode Angka Siap Final)',
        ],
        5 => [
            'label' => 'Final',
            'desc' => 'Kunci & beri NO',
            'url' => route('data-tersimpan.index'),
            'title' => 'Tahap 5: Data Tersimpan (Dataset Final)',
        ],
        6 => [
            'label' => 'Rekap',
            'desc' => 'Ringkasan & IKU',
            'url' => route('rekap.index'),
            'title' => 'Tahap 6: Rekapitulasi & Capaian IKU',
        ],
    ];
@endphp

<div class="w-full bg-white rounded-xl border border-(--border) p-4 shadow-xs overflow-x-auto">
    <ol class="flex items-center min-w-175 justify-between">
        @foreach ($steps as $stepNum => $step)
            @php
                $isCompleted = $stepNum < $activeStep;
                $isCurrent = $stepNum === (int)$activeStep;
            @endphp
            <li class="relative flex-1 flex items-center {{ !$loop->last ? 'pr-4' : '' }}">
                <a
                    href="{{ $step['url'] }}"
                    title="{{ $step['title'] }}"
                    class="group flex items-center gap-3 transition-transform duration-150 hover:scale-[1.02] cursor-pointer"
                >
                    <div @class([
                        'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all shrink-0',
                        'bg-emerald-500 text-white group-hover:bg-emerald-600 group-hover:shadow-sm' => $isCompleted,
                        'text-white shadow-xs group-hover:shadow-md' => $isCurrent,
                        'bg-gray-100 text-gray-500 border border-gray-200 group-hover:border-blue-400 group-hover:text-blue-600 group-hover:bg-blue-50/50' => !$isCompleted && !$isCurrent,
                    ]) style="{{ $isCurrent ? 'background-image: var(--grad-accent);' : '' }}">
                        @if ($isCompleted)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        @else
                            {{ $stepNum }}
                        @endif
                    </div>
                    <div>
                        <p @class([
                            'text-xs font-semibold leading-tight transition-colors',
                            'text-gray-900 font-bold group-hover:text-blue-700' => $isCurrent,
                            'text-emerald-700 group-hover:text-emerald-800' => $isCompleted,
                            'text-gray-500 group-hover:text-gray-800' => !$isCompleted && !$isCurrent,
                        ])>
                            {{ $step['label'] }}
                        </p>
                        <p class="text-[10px] text-gray-400 mt-0.5 hidden sm:block group-hover:text-gray-600">{{ $step['desc'] }}</p>
                    </div>
                </a>

                @if (!$loop->last)
                    <div class="flex-1 ml-4 mr-2 h-0.5 {{ $isCompleted ? 'bg-emerald-400' : 'bg-gray-200' }}"></div>
                @endif
            </li>
        @endforeach
    </ol>
</div>
