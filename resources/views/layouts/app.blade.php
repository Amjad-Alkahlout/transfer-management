<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? __('general.app.name') }}</title>

    {{-- Runs before anything paints, so the collapsed sidebar state
         is applied instantly on every full-page navigation instead of
         flashing open and then snapping shut once Alpine boots. --}}
    <script>
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        {{-- Ensures elements using x-cloak stay hidden until Alpine finishes
             initializing, even if app.css doesn't define this rule --}}
        [x-cloak] { display: none !important; }

        {{-- Mirrors the Alpine-driven collapsed state in plain CSS so it
             takes effect immediately, before Alpine has mounted. --}}
        @media (min-width: 1024px) {
            html.sidebar-collapsed #mobile-sidebar { width: 5rem; }
            html.sidebar-collapsed #mobile-sidebar .sidebar-label { display: none; }
            html.sidebar-collapsed #mobile-sidebar .sidebar-row { justify-content: center; padding-inline: 0.5rem; }
        }
    </style>
</head>
<body
    class="bg-gray-100"
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true'
    }"
    x-init="$watch('sidebarCollapsed', value => {
        localStorage.setItem('sidebarCollapsed', value);
        document.documentElement.classList.toggle('sidebar-collapsed', value);
    })"
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
        :class="[
            sidebarOpen ? 'translate-x-0' : 'max-lg:-translate-x-full max-lg:rtl:translate-x-full',
            sidebarCollapsed ? 'lg:w-20' : 'lg:w-56'
        ]"
        class="fixed inset-y-0 inset-s-0 z-40 w-56 border-e border-gray-200 bg-white transition-all duration-200 lg:static lg:translate-x-0 lg:shrink-0"
    >
        <div class="sticky top-0 flex h-screen flex-col overflow-y-auto">

            <div
                class="sidebar-row flex min-h-16 shrink-0 items-center gap-2 border-b border-gray-200 px-6 py-3"
                :class="sidebarCollapsed && 'lg:justify-center lg:px-2'"
            >
                <h1
                    class="sidebar-label text-lg font-bold leading-tight text-blue-600"
                    :class="sidebarCollapsed && 'lg:hidden'"
                >
                    {{ __('general.app.name') }}
                </h1>

                {{-- Desktop-only collapse toggle --}}
                <button
                    type="button"
                    @click="sidebarCollapsed = !sidebarCollapsed"
                    class="hidden rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 lg:flex"
                    :class="sidebarCollapsed ? '' : 'ms-auto'"
                    :aria-expanded="(!sidebarCollapsed).toString()"
                    aria-controls="mobile-sidebar"
                    :aria-label="sidebarCollapsed ? '{{ __('navigation.expand_menu') }}' : '{{ __('navigation.collapse_menu') }}'"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 transition-transform duration-200"
                        :class="sidebarCollapsed ? 'rotate-180 rtl:rotate-0' : 'rotate-0 rtl:rotate-180'"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7M20 19l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">

                <a href="{{ route('dashboard') }}"
                   :class="sidebarCollapsed && 'lg:justify-center lg:px-2'"
                   :title="sidebarCollapsed ? '{{ __('navigation.dashboard') }}' : null"
                   class="sidebar-row flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                >
                    <x-heroicon-o-home class="h-5 w-5 shrink-0" />
                    <span class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">{{ __('navigation.dashboard') }}</span>
                </a>

                <a href="{{ route('transfers.index') }}"
                   :class="sidebarCollapsed && 'lg:justify-center lg:px-2'"
                   :title="sidebarCollapsed ? '{{ __('navigation.transfers') }}' : null"
                   class="sidebar-row flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs('transfers.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                >
                    <x-heroicon-o-arrows-right-left class="h-5 w-5 shrink-0" />
                    <span class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">{{ __('navigation.transfers') }}</span>
                </a>

                @can('view-capital-transfers')
                    <a href="{{ route('capital-transfers.index') }}"
                       :class="sidebarCollapsed && 'lg:justify-center lg:px-2'"
                       :title="sidebarCollapsed ? '{{ __('navigation.capital_transfers') }}' : null"
                       class="sidebar-row flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs('capital-transfers.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-banknotes class="h-5 w-5 shrink-0" />
                        <span class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">{{ __('navigation.capital_transfers') }}</span>
                    </a>
                @endcan

                @can('view-capital-accounts')
                    <a href="{{ route('capital-accounts.index') }}"
                       :class="sidebarCollapsed && 'lg:justify-center lg:px-2'"
                       :title="sidebarCollapsed ? '{{ __('navigation.capital_accounts') }}' : null"
                       class="sidebar-row flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs('capital-accounts.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-building-library class="h-5 w-5 shrink-0" />
                        <span class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">{{ __('navigation.capital_accounts') }}</span>
                    </a>
                @endcan

                @can('manage-exchange-rates')
                    <a href="{{ route('exchange-rates.index') }}"
                       :class="sidebarCollapsed && 'lg:justify-center lg:px-2'"
                       :title="sidebarCollapsed ? '{{ __('navigation.exchange_rates') }}' : null"
                       class="sidebar-row flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs('exchange-rates.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-currency-dollar class="h-5 w-5 shrink-0" />
                        <span class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">{{ __('navigation.exchange_rates') }}</span>
                    </a>
                @endcan

                @can('manage-users')
                    <a href="{{ route('users.index') }}"
                       :class="sidebarCollapsed && 'lg:justify-center lg:px-2'"
                       :title="sidebarCollapsed ? '{{ __('navigation.users') }}' : null"
                       class="sidebar-row flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs('users.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-users class="h-5 w-5 shrink-0" />
                        <span class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">{{ __('navigation.users') }}</span>
                    </a>
                @endcan

                @can('view-capital-ledger')
                    <a href="{{ route('capital-ledger.index') }}"
                       :class="sidebarCollapsed && 'lg:justify-center lg:px-2'"
                       :title="sidebarCollapsed ? '{{ __('navigation.capital_ledger') }}' : null"
                       class="sidebar-row flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs('capital-ledger.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-heroicon-o-document-text class="h-5 w-5 shrink-0" />
                        <span class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">{{ __('navigation.capital_ledger') }}</span>
                    </a>
                @endcan

            </nav>

            <div class="border-t border-gray-200 p-4">

                <div
                    class="sidebar-row mb-4 flex items-center gap-3"
                    :class="sidebarCollapsed && 'lg:justify-center'"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100">
                        <x-heroicon-o-user class="h-5 w-5 text-blue-600"/>
                    </div>

                    <div class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">
                        <p class="font-medium text-gray-900">
                            {{ auth()->user()->name }}
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        :title="sidebarCollapsed ? '{{ __('general.buttons.logout') }}' : null"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-red-50 hover:text-red-600 hover:border-red-200"
                    >
                        <x-heroicon-o-arrow-left-on-rectangle class="h-5 w-5"/>
                        <span class="sidebar-label" :class="sidebarCollapsed && 'lg:hidden'">{{ __('general.buttons.logout') }}</span>
                    </button>
                </form>

            </div>

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

                <a
                    href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"                >
                    🌐 {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                </a>

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
