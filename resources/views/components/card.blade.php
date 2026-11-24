@props([
    'title' => null,
    'subtitle' => null,
    'variant' => 'default', // default, soft
    'class' => '',
])

@php
    $bgStyle = $variant === 'soft' ? 'background-image: var(--grad-soft);' : 'background-color: var(--surface);';
@endphp

<div
    {{ $attributes->merge(['class' => "rounded-xl border border-(--border) shadow-xs transition-shadow duration-200 overflow-hidden {$class}"]) }}
    style="{{ $bgStyle }}"
>
    @if ($title || isset($headerAction))
        <div class="px-6 py-4 border-b border-(--border) flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 shrink-0">
                @if ($title)
                    <h3 class="text-base font-semibold text-(--text) tracking-tight">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-(--muted) mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if (isset($headerAction))
                <div class="flex-1 sm:flex-initial flex items-center justify-end">
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="px-6 py-3.5 bg-gray-50 border-t border-(--border) flex items-center justify-end gap-3 rounded-b-xl">
            {{ $footer }}
        </div>
    @endif
</div>
