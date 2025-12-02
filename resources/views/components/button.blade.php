@props([
    'type' => 'button',   // default type
    'variant' => 'primary', // primary, secondary, etc
])

@php
    $baseClasses = 'px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition duration-200 ease-in-out';
    $variants = [
        'primary' => 'bg-blue-500 text-white hover:bg-blue-600',
        'secondary' => 'bg-gray-200 text-gray-700 hover:bg-gray-300',
        'danger' => 'bg-red-500 text-white hover:bg-red-600',
    ];
    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
