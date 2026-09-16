<div>
    <x-slot:header>{{ __('import.title') }}</x-slot:header>

    <div class="mx-auto max-w-3xl space-y-5">

        <a href="{{ route('tickets.index') }}"
           wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900 dark:hover:text-slate-100">
            <x-icon name="arrow-left" class="size-4" />
            {{ __('ticket.actions.back') }}
        </a>

        <form wire:submit="save" class="card overflow-hidden">
            <div class="border-b border-slate-100 p-5 sm:p-6 dark:border-slate-800">
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                    {{ __('import.title') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('import.subtitle') }}</p>
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="file" class="field-label">{{ __('import.field') }}</label>
                    <input id="file"
                           type="file"
                           accept=".csv,text/csv"
                           wire:model="file"
                           class="field-control file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 dark:file:bg-brand-950/50 dark:file:text-brand-300 @error('file') field-control-invalid @enderror">

                    <p wire:loading wire:target="file" class="mt-1.5 text-sm text-slate-500">
                        {{ __('app.common.loading') }}
                    </p>

                    @error('file')
                        <p class="field-error">
                            <x-icon name="warning" class="size-4 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-800/40 dark:ring-slate-700">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('import.format.title') }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('import.format.description') }}</p>

                    <dl class="mt-3 space-y-1.5 text-sm">
                        @foreach (['email', 'title', 'description', 'priority'] as $column)
                            <div class="flex flex-wrap gap-x-2">
                                <dt class="font-mono text-xs text-brand-600 dark:text-brand-400">{{ $column }}</dt>
                                <dd class="text-slate-500 dark:text-slate-400">{{ __('import.columns.'.$column) }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-200"><code>email,title,description,priority
maxime@support.test,Imprimante hors service,Bourrage papier fantome au 2e etage,high</code></pre>
                </div>
            </div>

            <div class="flex justify-end gap-3 bg-slate-50/70 px-5 py-4 sm:px-6 dark:bg-slate-800/30">
                <a href="{{ route('tickets.index') }}"
                   wire:navigate
                   class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    {{ __('ticket.actions.cancel') }}
                </a>
                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save,file"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                    <x-icon name="inbox" class="size-4" />
                    {{ __('import.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
