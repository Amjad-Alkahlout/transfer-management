
@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'secondary' => 'bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300',
        'success' => 'bg-green-600 hover:bg-green-700 text-white',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white',
        'warning' => 'bg-amber-500 hover:bg-amber-600 text-white',
        default => 'bg-blue-600 hover:bg-blue-700 text-white',
    };

    $base =
        'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';
@endphp

@if($href)

    <a
        href="{{ $href }}"
        {{ $attributes->merge([
            'class' => "$base $classes",
        ]) }}
    >
        {{ $slot }}
    </a>

@else

    <button
        {{ $attributes->merge([
            'class' => "$base $classes disabled:cursor-not-allowed disabled:opacity-50"
        ]) }}
        type="{{ $type }}"
    >
        {{ $slot }}
    </button>

@endif
