@props([
    'title' => 'No data found',
    'description' => null,
])

<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-8 py-12 text-center">

    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white text-3xl shadow-sm">
        📭
    </div>

    <h3 class="text-lg font-semibold text-gray-800">
        {{ $title }}
    </h3>

    @if($description)
        <p class="mt-2 max-w-md text-sm text-gray-500">
            {{ $description }}
        </p>
    @endif

    @if(isset($actions))
        <div class="mt-6">
            {{ $actions }}
        </div>
    @endif

</div>
