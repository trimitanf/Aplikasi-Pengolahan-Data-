@props([
    'variant' => 'neutral', // primary, success, warning, danger, neutral, manual
    'size' => 'md', // sm, md
])

@php
    $sizeClasses = match($size) {
        'sm' => 'px-2 py-0.5 text-xs',
        default => 'px-2.5 py-1 text-xs',
    };

    $variantClasses = match($variant) {
        'primary' => 'bg-blue-100 text-blue-800 border-blue-200',
        'success' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'warning' => 'bg-amber-100 text-amber-800 border-amber-200',
        'danger' => 'bg-red-100 text-red-800 border-red-200',
        'manual' => 'bg-[var(--manual-soft)] text-red-700 border-[var(--manual-border)] font-semibold',
        default => 'bg-gray-100 text-gray-700 border-gray-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full font-medium border {$sizeClasses} {$variantClasses}"]) }}>
    {{ $slot }}
</span>
