<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Money Transfer System' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen bg-gray-100">

<div class="min-h-screen">

    {{-- Top Navigation --}}
    <header class="border-b border-gray-200 bg-white shadow-sm">

        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">

            <div>
                <h1 class="text-xl font-bold text-blue-600">
                    Money Transfer System
                </h1>
            </div>

            <div class="flex items-center gap-4">

                    <span class="text-sm text-gray-600">
                        {{ auth()->user()->name }}
                    </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700"
                    >
                        Logout
                    </button>
                </form>

            </div>

        </div>

    </header>

    {{-- Page Content --}}
    <main class="mx-auto max-w-7xl px-6 py-8">

        @if(session('message'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                {{ session('message') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if(session('cancel_message'))
            <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-700">
                {{ session('cancel_message') }}
            </div>
        @endif

        @if(session('complete_message'))
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-blue-700">
                {{ session('complete_message') }}
            </div>
        @endif

        {{ $slot }}

    </main>

</div>

@livewireScripts

</body>

</html>
