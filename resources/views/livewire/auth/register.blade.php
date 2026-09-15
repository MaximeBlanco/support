<div>
    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
        {{ __('app.auth.register_title') }}
    </h1>
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        {{ __('app.auth.register_subtitle') }}
    </p>

    <form wire:submit="register" class="mt-8 space-y-5">
        @foreach ([
            ['name', 'text', 'name', __('app.auth.name')],
            ['email', 'email', 'email', __('app.auth.email')],
            ['password', 'password', 'new-password', __('app.auth.password')],
            ['password_confirmation', 'password', 'new-password', __('app.auth.password_confirmation')],
        ] as [$field, $type, $autocomplete, $label])
            <div>
                <label for="{{ $field }}" class="field-label">{{ $label }}</label>
                <input id="{{ $field }}"
                       type="{{ $type }}"
                       autocomplete="{{ $autocomplete }}"
                       required
                       @if ($loop->first) autofocus @endif
                       wire:model="{{ $field }}"
                       class="field-control @error($field) field-control-invalid @enderror">
                @error($field)
                    <p class="field-error">
                        <x-icon name="warning" class="size-4 shrink-0" />
                        {{ $message }}
                    </p>
                @enderror
            </div>
        @endforeach

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 disabled:opacity-60">
            <span wire:loading.remove wire:target="register">{{ __('app.auth.register') }}</span>
            <span wire:loading wire:target="register">{{ __('app.common.loading') }}</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        {{ __('app.auth.have_account') }}
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-500 dark:text-brand-400">
            {{ __('app.auth.login') }}
        </a>
    </p>
</div>
