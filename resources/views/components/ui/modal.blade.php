@props([
    'show' => false,
    'title' => null,
    'maxWidth' => '4xl',
    'close' => null,
])

@php
    $widths = [
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '4xl' => 'max-w-4xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
    ];
@endphp

@if($show)

    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-6"
        @if($close)
            wire:click="{{ $close }}"
        @endif
    >

        <div
            wire:click.stop
            class="flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl {{ $widths[$maxWidth] ?? $widths['4xl'] }}"
        >

            <div class="flex items-center justify-between border-b px-6 py-4">

                @if($title)
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ $title }}
                    </h2>
                @endif

                @if($close)
                    <button
                        wire:click="{{ $close }}"
                        class="rounded-lg p-2 text-xl leading-none text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
                    >
                        &times;
                    </button>
                @endif

            </div>

            <div class="flex-1 overflow-auto p-6">

                {{ $slot }}

            </div>

            @isset($footer)

                <div class="border-t bg-gray-50 px-6 py-4">

                    {{ $footer }}

                </div>

            @endisset

        </div>

    </div>

@endif
