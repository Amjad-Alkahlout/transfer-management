@props([
    'show' => false,
    'title' => null,
    'description' => null,
    'maxWidth' => '4xl',
    'close' => null,
    'dismissible' => true,
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
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/60 p-3 backdrop-blur-[2px] sm:p-6"
        role="presentation"
        @if($close && $dismissible)
            wire:click.self="{{ $close }}"
            @keydown.escape.window="$refs.modalClose?.click()"
        @endif
    >

        <div
            wire:click.stop
            class="flex max-h-[calc(100dvh-1.5rem)] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 sm:max-h-[90vh] {{ $widths[$maxWidth] ?? $widths['4xl'] }}"
            role="dialog"
            aria-modal="true"
            @if($title)
                aria-labelledby="dialog-title-{{ substr(sha1($title), 0, 12) }}"
            @else
                aria-label="{{ __('general.modal.dialog') }}"
            @endif
            tabindex="-1"
            x-init="$nextTick(() => $el.focus())"
            @keydown.tab="const focusable = [...$el.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=\'-1\'])')].filter(el => el.offsetParent !== null); if (!focusable.length) { $event.preventDefault(); } else if ($event.shiftKey && (document.activeElement === focusable[0] || document.activeElement === $el)) { $event.preventDefault(); focusable[focusable.length - 1].focus(); } else if (!$event.shiftKey && document.activeElement === focusable[focusable.length - 1]) { $event.preventDefault(); focusable[0].focus(); }"
        >

            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-4 py-4 sm:px-6">

                <div class="min-w-0">
                    @if($title)
                        <h2 id="dialog-title-{{ substr(sha1($title), 0, 12) }}" class="text-lg font-semibold text-slate-900">
                            {{ $title }}
                        </h2>
                    @endif
                    @if($description)
                        <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
                    @endif
                </div>

                @if($close)
                    <button
                        type="button"
                        x-ref="modalClose"
                        wire:click="{{ $close }}"
                        wire:loading.attr="disabled"
                        aria-label="{{ __('general.buttons.close') }}"
                        class="-me-2 -mt-1 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-2xl leading-none text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 disabled:opacity-50"
                    >
                        &times;
                    </button>
                @endif

            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">

                {{ $slot }}

            </div>

            @isset($footer)

                <div class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-4 sm:px-6">

                    {{ $footer }}

                </div>

            @endisset

        </div>

    </div>

@endif
