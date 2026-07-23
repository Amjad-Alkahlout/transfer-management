@props([
    'color' => 'gray',
])

@php
    $colors = [
        'gray' => 'bg-gray-100 text-gray-700',
        'blue' => 'bg-blue-100 text-blue-700',
        'green' => 'bg-green-100 text-green-700',
        'red' => 'bg-red-100 text-red-700',
        'yellow' => 'bg-yellow-100 text-yellow-700',
        'orange' => 'bg-orange-100 text-orange-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
    ];
@endphp

<span
    {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ' . ($colors[$color] ?? $colors['gray'])
    ]) }}
>
    {{ $slot }}
</span>
