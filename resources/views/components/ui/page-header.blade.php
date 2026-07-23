@props([
    'title',
    'description' => null,
])

<div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

    <div>

        <h1 class="text-3xl font-bold tracking-tight text-gray-900">
            {{ $title }}
        </h1>

        @if($description)
            <p class="mt-2 text-sm text-gray-500">
                {{ $description }}
            </p>
        @endif

    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-3">
            {{ $actions }}
        </div>
    @endisset

</div>
