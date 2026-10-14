@props([
    'activeStep' => 1, // 1 to 6
    'datasetId' => null,
])

@php
    $steps = [
        1 => ['label' => 'Unggah', 'desc' => 'Upload file SIPT'],
        2 => ['label' => 'Validasi', 'desc' => 'Pengecekan data'],
        3 => ['label' => 'Data Mentah', 'desc' => '26 kolom SIPT'],
        4 => ['label' => 'Konversi & Manual', 'desc' => 'Tabel angka kode'],
        5 => ['label' => 'Final', 'desc' => 'Kunci & beri NO'],
        6 => ['label' => 'Rekap', 'desc' => 'Ringkasan & IKU'],
    ];
@endphp

<div class="w-full bg-white rounded-xl border border-[var(--border)] p-4 shadow-xs overflow-x-auto">
    <ol class="flex items-center min-w-[700px] justify-between">
        @foreach ($steps as $stepNum => $step)
            @php
                $isCompleted = $stepNum < $activeStep;
                $isCurrent = $stepNum === (int)$activeStep;
            @endphp
            <li class="relative flex-1 flex items-center {{ !$loop->last ? 'pr-4' : '' }}">
                <div class="flex items-center gap-3">
                    <div @class([
                        'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors shrink-0',
                        'bg-emerald-500 text-white' => $isCompleted,
                        'text-white shadow-xs' => $isCurrent,
                        'bg-gray-100 text-gray-500 border border-gray-200' => !$isCompleted && !$isCurrent,
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
                            'text-xs font-semibold leading-tight',
                            'text-gray-900 font-bold' => $isCurrent,
                            'text-emerald-700' => $isCompleted,
                            'text-gray-500' => !$isCompleted && !$isCurrent,
                        ])>
                            {{ $step['label'] }}
                        </p>
                        <p class="text-[10px] text-gray-400 mt-0.5 hidden sm:block">{{ $step['desc'] }}</p>
                    </div>
                </div>

                @if (!$loop->last)
                    <div class="flex-1 ml-4 mr-2 h-0.5 {{ $isCompleted ? 'bg-emerald-400' : 'bg-gray-200' }}"></div>
                @endif
            </li>
        @endforeach
    </ol>
</div>
