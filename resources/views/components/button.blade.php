@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, danger, outline
    'size' => 'md', // sm, md, lg
    'disabled' => false,
])

@php
    $baseClasses = "inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed";

    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-xs gap-1.5',
        'lg' => 'px-6 py-3 text-base gap-3',
        default => 'px-4 py-2 text-sm gap-2',
    };

    $variantClasses = match($variant) {
        'primary' => 'text-white shadow-sm hover:shadow hover:opacity-95 focus:ring-blue-600',
        'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 hover:text-gray-900 focus:ring-blue-500 shadow-sm',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm',
        'outline' => 'bg-transparent text-blue-700 border border-blue-600 hover:bg-blue-50 focus:ring-blue-500',
        default => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    };

    $customStyle = $variant === 'primary' ? 'background-image: var(--grad-accent);' : '';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "{$baseClasses} {$sizeClasses} {$variantClasses}"]) }}
    style="{{ $customStyle }}"
    @disabled($disabled)
>
    {{ $slot }}
</button>
