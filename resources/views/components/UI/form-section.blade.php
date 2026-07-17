@props([
    'title',
    'description' => null,
])

<x-ui.card>

    <div class="mb-6">

        <h3 class="text-lg font-semibold text-gray-900">
            {{ $title }}
        </h3>

        @if($description)
            <p class="mt-1 text-sm text-gray-500">
                {{ $description }}
            </p>
        @endif

    </div>

    <div {{ $attributes->merge([
    'class' => 'grid grid-cols-1 gap-5 md:grid-cols-2',
]) }}>

        {{ $slot }}

    </div>

</x-ui.card>
