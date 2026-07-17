@props([
    'label' => null,
    'name' => null,
])

<div class="space-y-2">

    @if($label)
        <label
            @if($name)
                for="{{ $name }}"
            @endif
            class="block text-sm font-medium text-gray-700"
        >
            {{ $label }}
        </label>
    @endif

    <select
        @if($name)
            id="{{ $name }}"
        name="{{ $name }}"
        @endif

        {{ $attributes->merge([
            'class' => 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500',
        ]) }}
    >
        {{ $slot }}
    </select>

    @if($name)
        @error($name)
        <p class="text-sm text-red-600">
            {{ $message }}
        </p>
        @enderror
    @endif

</div>
