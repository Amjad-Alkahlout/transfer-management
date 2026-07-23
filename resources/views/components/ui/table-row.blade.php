<tr
    {{ $attributes->merge([
        'class' => 'hover:bg-gray-50 transition'
    ]) }}
>

    {{ $slot }}

</tr>
