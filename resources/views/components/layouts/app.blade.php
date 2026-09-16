<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('app.name') }} · {{ __('app.name') }}</title>

    <script>
        (() => {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (stored === null && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div x-data="{ sidebarOpen: false }" class="min-h-full">

    <div x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"
         x-cloak></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-900 transition-transform duration-300 ease-out lg:translate-x-0 dark:bg-slate-900 dark:ring-1 dark:ring-slate-800"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        <div class="flex h-16 shrink-0 items-center gap-3 px-6">
            <span class="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-600/25">
                <x-icon name="inbox" class="size-5" />
            </span>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white">{{ __('app.name') }}</p>
                <p class="truncate text-xs text-slate-400">{{ __('app.tagline') }}</p>
            </div>
            <button type="button"
                    @click="sidebarOpen = false"
                    class="ml-auto rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="{{ __('app.common.close') }}">
                <x-icon name="close" />
            </button>
        </div>

        <nav class="flex flex-1 flex-col gap-1 px-4 py-4" aria-label="{{ __('app.nav.main') }}">
            <x-nav-link :href="route('dashboard')" icon="dashboard" :active="request()->routeIs('dashboard')">
                {{ __('app.nav.dashboard') }}
            </x-nav-link>

            <x-nav-link :href="route('tickets.index')" icon="ticket" :active="request()->routeIs('tickets.index')">
                {{ __('app.nav.tickets') }}
            </x-nav-link>

            @can('create', App\Models\Ticket::class)
                <x-nav-link :href="route('tickets.create')" icon="plus" :active="request()->routeIs('tickets.create')">
                    {{ __('app.nav.new_ticket') }}
                </x-nav-link>
            @endcan

            @can('import', App\Models\Ticket::class)
                <x-nav-link :href="route('tickets.import')" icon="archive" :active="request()->routeIs('tickets.import')">
                    {{ __('import.title') }}
                </x-nav-link>
            @endcan

            <div class="mt-auto rounded-xl bg-white/5 p-4 ring-1 ring-white/10">
                <p class="text-xs font-medium text-slate-300">{{ auth()->user()->name }}</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach (auth()->user()->roles as $role)
                        <span class="badge {{ $role->name->badgeClasses() }}">{{ $role->name->label() }}</span>
                    @endforeach
                </div>
            </div>
        </nav>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur-lg sm:px-6 lg:px-8 dark:border-slate-800 dark:bg-slate-950/80">
            <button type="button"
                    @click="sidebarOpen = true"
                    class="-ml-1 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden dark:hover:bg-slate-800 dark:hover:text-slate-100"
                    aria-label="{{ __('app.nav.toggle_sidebar') }}">
                <x-icon name="menu" />
            </button>

            <div class="flex-1">
                @isset($header)
                    <h1 class="truncate text-base font-semibold text-slate-900 sm:text-lg dark:text-slate-100">{{ $header }}</h1>
                @endisset
            </div>

            <button type="button"
                    x-data
                    @click="
                        const dark = document.documentElement.classList.toggle('dark');
                        localStorage.setItem('theme', dark ? 'dark' : 'light');
                    "
                    class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                    aria-label="{{ __('app.nav.toggle_theme') }}">
                <x-icon name="sun" class="size-5 dark:hidden" />
                <x-icon name="moon" class="hidden size-5 dark:block" />
            </button>

            <livewire:notification-bell />

            <div x-data="{ open: false }" class="relative">
                <button type="button"
                        @click="open = !open"
                        @click.outside="open = false"
                        class="flex items-center gap-2 rounded-lg p-1 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                        :aria-expanded="open.toString()"
                        aria-haspopup="true"
                        aria-label="{{ __('app.nav.user') }}">
                    <span class="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-xs font-semibold text-white">
                        {{ auth()->user()->initials() }}
                    </span>
                    <x-icon name="chevron-down" class="size-4 text-slate-400" />
                </button>

                <div x-show="open"
                     x-transition.origin.top.right
                     x-cloak
                     class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">
                            <x-icon name="logout" class="size-4" />
                            {{ __('app.auth.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    <x-toast />
</div>
</body>
</html>
