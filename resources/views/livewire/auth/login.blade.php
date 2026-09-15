<div>
    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
        {{ __('app.auth.login_title') }}
    </h1>
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        {{ __('app.auth.login_subtitle') }}
    </p>

    <form wire:submit="login" class="mt-8 space-y-5">
        <div>
            <label for="email" class="field-label">{{ __('app.auth.email') }}</label>
            <input id="email"
                   type="email"
                   autocomplete="email"
                   required
                   autofocus
                   wire:model="email"
                   class="field-control @error('email') field-control-invalid @enderror"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p id="email-error" class="field-error">
                    <x-icon name="warning" class="size-4 shrink-0" />
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label for="password" class="field-label">{{ __('app.auth.password') }}</label>
            <input id="password"
                   type="password"
                   autocomplete="current-password"
                   required
                   wire:model="password"
                   class="field-control @error('password') field-control-invalid @enderror">
            @error('password')
                <p class="field-error">
                    <x-icon name="warning" class="size-4 shrink-0" />
                    {{ $message }}
                </p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 dark:text-slate-400">
            <input type="checkbox"
                   wire:model="remember"
                   class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
            {{ __('app.auth.remember') }}
        </label>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 disabled:opacity-60">
            <span wire:loading.remove wire:target="login">{{ __('app.auth.login') }}</span>
            <span wire:loading wire:target="login" class="flex items-center gap-2">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                </svg>
                {{ __('app.common.loading') }}
            </span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        {{ __('app.auth.no_account') }}
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-500 dark:text-brand-400">
            {{ __('app.auth.register') }}
        </a>
    </p>

    <div class="mt-10 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <p class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
            <x-icon name="sparkles" class="size-4 text-brand-500" />
            {{ __('app.auth.demo_hint') }}
        </p>
        <div class="mt-3 grid gap-2">
            @foreach ([
                ['manager@support.test', __('role.manager')],
                ['nadia@support.test', __('role.technician')],
                ['maxime@support.test', __('role.requester')],
            ] as [$demoEmail, $demoRole])
                <button type="button"
                        wire:click="fillDemo('{{ $demoEmail }}')"
                        class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-left text-xs ring-1 ring-slate-200 transition hover:ring-brand-400 dark:bg-slate-800 dark:ring-slate-700 dark:hover:ring-brand-500">
                    <span class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $demoEmail }}</span>
                    <span class="shrink-0 text-slate-400">{{ $demoRole }}</span>
                </button>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-400">{{ __('app.auth.demo_password') }}</p>
    </div>
</div>
