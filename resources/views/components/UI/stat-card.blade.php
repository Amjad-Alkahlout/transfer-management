@props([
    'title',
    'value',
    'subtitle' => null,
    'color' => 'blue',
])

@php
    $colors = [
        'blue' => [
            'text' => 'text-blue-600',
            'bg' => 'bg-blue-100',
        ],
        'green' => [
            'text' => 'text-green-600',
            'bg' => 'bg-green-100',
        ],
        'orange' => [
            'text' => 'text-orange-500',
            'bg' => 'bg-orange-100',
        ],
        'red' => [
            'text' => 'text-red-600',
            'bg' => 'bg-red-100',
        ],
        'emerald' => [
            'text' => 'text-emerald-600',
            'bg' => 'bg-emerald-100',
        ],
    ];

    $theme = $colors[$color] ?? $colors['blue'];
@endphp

<x-ui.card class="h-full">

    <div class="flex items-start justify-between">

        <div>

            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">
                {{ $title }}
            </p>

            <h2 class="mt-3 text-3xl font-bold {{ $theme['text'] }}">
                {{ $value }}
            </h2>

            @if($subtitle)
                <p class="mt-2 text-sm text-gray-400">
                    Current Balance
                </p>
            @endif

        </div>

        @if(isset($icon))
            <div class="flex h-14 w-14 items-center justify-center rounded-xl {{ $theme['bg'] }}">
                {{ $icon }}
            </div>
        @endif

    </div>

</x-ui.card>
