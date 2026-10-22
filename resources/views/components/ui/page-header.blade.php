@props([
    'title',
    'description' => null,
])

@push('app-page-heading')
    <div {{ $attributes->only('class')->class(['min-w-0']) }}>

        <h1 class="truncate text-base font-semibold tracking-tight text-slate-900 sm:text-lg">
            {{ $title }}
        </h1>

        @if($description)
            <p class="truncate text-xs text-slate-500 sm:text-sm">
                {{ $description }}
            </p>
        @endif

    </div>
@endpush

@isset($actions)
    <div {{ $attributes->class(['mb-6 flex flex-wrap items-center gap-3']) }}>
        {{ $actions }}
    </div>
@endisset

<x-ui.flash />
