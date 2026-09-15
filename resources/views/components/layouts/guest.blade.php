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
<div class="grid min-h-full lg:grid-cols-2">

    <div class="relative hidden overflow-hidden bg-slate-900 lg:block">
        <div class="absolute inset-0 bg-gradient-to-br from-brand-700 via-brand-900 to-slate-950"></div>
        <div class="absolute -top-24 -right-24 size-96 rounded-full bg-brand-400/20 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-16 size-96 rounded-full bg-brand-500/20 blur-3xl"></div>

        <div class="relative flex h-full flex-col justify-between p-12">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-white/20 backdrop-blur">
                    <x-icon name="inbox" />
                </span>
                <span class="text-lg font-semibold text-white">{{ __('app.name') }}</span>
            </div>

            <div class="max-w-md">
                <h2 class="text-3xl leading-tight font-semibold text-white">
                    {{ __('app.tagline') }}
                </h2>
                <p class="mt-4 text-brand-100/80">
                    Déposez une demande, suivez son avancement, et sachez toujours qui s’en occupe.
                </p>

                <dl class="mt-10 grid grid-cols-3 gap-4">
                    @foreach ([['Statuts', '5'], ['Priorités', '4'], ['Profils', '3']] as [$label, $value])
                        <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur">
                            <dt class="text-xs text-brand-100/70">{{ $label }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <p class="text-xs text-brand-100/50">{{ date('Y') }} — {{ __('app.name') }}</p>
        </div>
    </div>

    <div class="flex items-center justify-center px-4 py-12 sm:px-8">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-600/25">
                    <x-icon name="inbox" />
                </span>
                <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('app.name') }}</span>
            </div>

            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
