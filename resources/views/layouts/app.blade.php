<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? __('general.app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen bg-gray-100">

<div class="min-h-screen">

    {{-- Top Navigation --}}
    <header class="border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-3 sm:h-16 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-0">
            <div>
                <h1 class="text-lg font-bold text-blue-600 sm:text-xl">
                    {{ __('general.app.name') }}
                </h1>
            </div>
            <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap sm:gap-4 sm:whitespace-nowrap">

                <a
                    href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                    class="text-sm font-medium text-blue-600 hover:underline"
                >
                    {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                </a>

                <span class="text-sm text-gray-600">
                 {{ auth()->user()->name }}
               </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                    >
                        {{ __('general.buttons.logout') }}
                    </button>
                </form>

            </div>

        </div>

    </header>

    {{-- Page Content --}}
    <main class="mx-auto max-w-7xl px-6 py-8">

        <x-ui.flash />

        {{ $slot }}

    </main>

</div>

@livewireScripts

</body>

</html>
