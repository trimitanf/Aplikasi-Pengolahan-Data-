@props([
    'title' => 'Belum Ada Data',
    'description' => 'Data yang Anda cari saat ini belum tersedia atau belum ditambahkan.',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-8 md:p-12 text-center bg-white rounded-xl border border-dashed border-gray-300']) }}>
    <div class="w-16 h-16 rounded-full bg-blue-50 text-(--brand-blue) flex items-center justify-center mb-4">
        {{ $icon ?? '' }}
        @if (!isset($icon))
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        @endif
    </div>

    <h4 class="text-base font-semibold text-gray-800">{{ $title }}</h4>
    <p class="text-sm text-gray-500 max-w-sm mt-1 mb-6">{{ $description }}</p>

    @if (isset($action))
        <div>
            {{ $action }}
        </div>
    @endif
</div>
