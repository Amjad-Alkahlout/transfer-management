@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge([
    'class' => 'rounded-2xl border border-gray-200 bg-white shadow-sm'
]) }}>

    @if($title || $description)
        <div class="border-b border-gray-200 px-6 py-4">

            @if($title)
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $title }}
                </h2>
            @endif

            @if($description)
                <p class="mt-1 text-sm text-gray-500">
                    {{ $description }}
                </p>
            @endif

        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
        @if(isset($footer))
            <div class="border-t border-gray-200 px-7 py-4">
                {{ $footer }}
            </div>
        @endif
    </div>

</div>
