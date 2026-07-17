@props([
    'color' => 'success',
])

@php
    $classes = match ($color) {
        'success' => 'border-green-200 bg-green-50 text-green-800',
        'danger' => 'border-red-200 bg-red-50 text-red-800',
        'warning' => 'border-yellow-200 bg-yellow-50 text-yellow-800',
        'info' => 'border-blue-200 bg-blue-50 text-blue-800',
        default => 'border-gray-200 bg-gray-50 text-gray-800',
    };
@endphp

<div
    {{ $attributes->merge([
        'class' => "mb-6 rounded-lg border px-4 py-3 text-sm font-medium $classes",
    ]) }}
>
    {{ $slot }}
</div>
