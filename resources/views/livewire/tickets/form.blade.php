@php
    $isEdit = $ticket !== null;
@endphp

<div>
    <x-slot:header>{{ $isEdit ? __('ticket.actions.edit') : __('ticket.actions.create') }}</x-slot:header>

    <div class="mx-auto max-w-3xl space-y-5">

        <a href="{{ $isEdit ? route('tickets.show', $ticket) : route('tickets.index') }}"
           wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900 dark:hover:text-slate-100">
            <x-icon name="arrow-left" class="size-4" />
            {{ __('ticket.actions.back') }}
        </a>

        <form wire:submit="save" class="card overflow-hidden">
            <div class="border-b border-slate-100 p-5 sm:p-6 dark:border-slate-800">
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                    {{ $isEdit ? __('ticket.actions.edit') : __('ticket.actions.create') }}
                </h2>
                @if ($isEdit)
                    <p class="mt-1 font-mono text-xs text-brand-600 dark:text-brand-400">{{ $ticket->reference }}</p>
                @endif
            </div>

            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label for="title" class="field-label">{{ __('ticket.fields.title') }}</label>
                    <input id="title"
                           type="text"
                           required
                           autofocus
                           wire:model.blur="title"
                           placeholder="{{ __('ticket.placeholders.title') }}"
                           class="field-control @error('title') field-control-invalid @enderror">
                    @error('title')
                        <p class="field-error">
                            <x-icon name="warning" class="size-4 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="field-label">{{ __('ticket.fields.description') }}</label>
                    <textarea id="description"
                              rows="8"
                              required
                              wire:model.blur="description"
                              placeholder="{{ __('ticket.placeholders.description') }}"
                              class="field-control resize-y @error('description') field-control-invalid @enderror"></textarea>
                    @error('description')
                        <p class="field-error">
                            <x-icon name="warning" class="size-4 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <fieldset>
                    <legend class="field-label">{{ __('ticket.fields.priority') }}</legend>
                    <div class="grid gap-2 sm:grid-cols-4">
                        @foreach ($priorities as $case)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm ring-1 transition
                                    {{ $priority === $case->value
                                        ? 'bg-brand-50 ring-2 ring-brand-500 dark:bg-brand-950/40'
                                        : 'ring-slate-300 hover:bg-slate-50 dark:ring-slate-700 dark:hover:bg-slate-800' }}">
                                <input type="radio"
                                       name="priority"
                                       value="{{ $case->value }}"
                                       wire:model.live="priority"
                                       class="size-4 border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-slate-900 dark:text-slate-100">{{ $case->label() }}</span>
                                    <span class="block text-xs text-slate-400">{{ $case->targetResolutionHours() }} h</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('priority')
                        <p class="field-error">
                            <x-icon name="warning" class="size-4 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror
                </fieldset>
            </div>

            <div class="flex justify-end gap-3 bg-slate-50/70 px-5 py-4 sm:px-6 dark:bg-slate-800/30">
                <a href="{{ $isEdit ? route('tickets.show', $ticket) : route('tickets.index') }}"
                   wire:navigate
                   class="rounded-lg px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">
                    {{ __('ticket.actions.cancel') }}
                </a>
                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                    <x-icon name="check" class="size-4" />
                    {{ __('ticket.actions.save') }}
                </button>
            </div>
        </form>
    </div>
</div>
