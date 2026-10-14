@props([
    'type' => 'info', // success, error, warning, info
    'dismissible' => true,
])

@php
    $typeClasses = match($type) {
        'success' => 'bg-emerald-50 text-emerald-900 border-emerald-200',
        'error' => 'bg-red-50 text-red-900 border-red-200',
        'warning' => 'bg-amber-50 text-amber-900 border-amber-200',
        default => 'bg-blue-50 text-blue-900 border-blue-200',
    };

    $iconClasses = match($type) {
        'success' => 'text-emerald-500',
        'error' => 'text-red-500',
        'warning' => 'text-amber-500',
        default => 'text-blue-500',
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    role="alert"
    {{ $attributes->merge(['class' => "flex items-start gap-3 p-4 rounded-xl border {$typeClasses} shadow-xs"]) }}
>
    <div class="shrink-0 mt-0.5">
        @if ($type === 'success')
            <svg class="w-5 h-5 {{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        @elseif ($type === 'error')
            <svg class="w-5 h-5 {{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        @elseif ($type === 'warning')
            <svg class="w-5 h-5 {{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        @else
            <svg class="w-5 h-5 {{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        @endif
    </div>

    <div class="flex-1 text-sm leading-relaxed">
        {{ $slot }}
    </div>

    @if ($dismissible)
        <button
            type="button"
            @click="show = false"
            class="shrink-0 -mr-1 -mt-1 p-1 rounded-md text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
        >
            <span class="sr-only">Tutup</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
