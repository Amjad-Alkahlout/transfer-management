<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        {{ $title ?? __('general.app.name') }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @livewireStyles
</head>

<body class="min-h-screen bg-gray-100">

<div class="flex min-h-screen items-center justify-center p-6">

    <div class="w-full max-w-md">

        {{ $slot }}

    </div>

</div>

@livewireScripts

</body>

</html>
