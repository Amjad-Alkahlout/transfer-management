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
    {{-- Ensures elements using x-cloak stay hidden until Alpine finishes
         initializing, even if app.css doesn't define this rule --}}
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body
    class="bg-gray-100"
    x-data="{ sidebarOpen: false }"
    @keydown.escape.window="sidebarOpen = false"
>

<div class="flex min-h-screen">

    {{-- Mobile backdrop --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
    ></div>

    {{-- Sidebar --}}
    <aside
        id="mobile-sidebar"
        :class="sidebarOpen ? 'translate-x-0' : 'max-lg:-translate-x-full max-lg:rtl:translate-x-full'"
        class="fixed inset-y-0 start-0 z-40 w-64 border-e border-gray-200 bg-white transition-transform duration-200 lg:static lg:translate-x-0 lg:shrink-0"
    >
        <div class="sticky top-0 flex h-screen flex-col overflow-y-auto">

            <div class="flex h-16 shrink-0 items-center border-b border-gray-200 px-6">
                <h1 class="text-xl font-bold text-blue-600">
                    {{ __('general.app.name') }}
                </h1>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">


                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                >
                    <x-heroicon-o-home class="h-5 w-5 shrink-0" />
                    {{ __('navigation.dashboard') }}
                </a>


                <a href="{{ route('transfers.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('transfers.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                >
                    <x-heroicon-o-arrows-right-left class="h-5 w-5 shrink-0" />
                    {{ __('navigation.transfers') }}
                </a>

                @can('view-capital-transfers')

                    <a href="{{ route('capital-transfers.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('capital-transfers.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-banknotes class="h-5 w-5 shrink-0" />
                        {{ __('navigation.capital_transfers') }}
                    </a>
                @endcan

                @can('view-capital-accounts')

                    <a href="{{ route('capital-accounts.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('capital-accounts.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-building-library class="h-5 w-5 shrink-0" />
                        {{ __('navigation.capital_accounts') }}
                    </a>
                @endcan

                @can('manage-exchange-rates')

                    <a href="{{ route('exchange-rates.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('exchange-rates.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-currency-dollar class="h-5 w-5 shrink-0" />
                        {{ __('navigation.exchange_rates') }}
                    </a>
                @endcan


                @can('manage-users')

                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-users class="h-5 w-5 shrink-0" />
                        {{ __('navigation.users') }}
                    </a>
                @endcan

            </nav>

        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex min-w-0 flex-1 flex-col">

        <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 shadow-sm sm:px-6">

            <div class="flex items-center gap-3">

                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden"
                    aria-label="{{ __('navigation.toggle_menu') }}"
                    aria-controls="mobile-sidebar"
                    :aria-expanded="sidebarOpen"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                {{-- Decorative duplicate of the sidebar's <h1> for mobile
                     (where the real title is scrolled off-canvas). Not a
                     heading itself, and hidden from assistive tech to avoid
                     the page title being announced twice. --}}
                <span class="text-lg font-bold text-blue-600 lg:hidden" aria-hidden="true">
                    {{ __('general.app.name') }}
                </span>

            </div>

            <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap sm:gap-4 sm:whitespace-nowrap">


                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
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

        </header>

        <main class="flex-1 px-4 py-8 sm:px-6">
            <x-ui.flash />
            {{ $slot }}
        </main>

    </div>

</div>

@livewireScripts
</body>
</html>
