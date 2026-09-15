<div>
    <x-slot:header>{{ __('app.nav.tickets') }}</x-slot:header>

    <div class="mx-auto max-w-7xl space-y-5">

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                    {{ __('app.nav.tickets') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ trans_choice('ticket.counters.results', $tickets->total(), ['count' => $tickets->total()]) }}
                </p>
            </div>

            @can('create', App\Models\Ticket::class)
                <a href="{{ route('tickets.create') }}"
                   wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                    <x-icon name="plus" class="size-4" />
                    {{ __('ticket.actions.create') }}
                </a>
            @endcan
        </div>

        <div class="card p-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-0 flex-1 basis-64">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-icon name="search" class="size-4" />
                    </span>
                    <input type="search"
                           wire:model.live.debounce.400ms="search"
                           placeholder="{{ __('ticket.placeholders.search') }}"
                           aria-label="{{ __('app.common.search') }}"
                           class="field-control pl-9">
                    <span wire:loading wire:target="search"
                          class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                        </svg>
                    </span>
                </div>

                <select wire:model.live="status"
                        aria-label="{{ __('ticket.fields.status') }}"
                        class="field-control w-auto min-w-44">
                    <option value="">{{ __('ticket.filters.all_statuses') }}</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>

                <select wire:model.live="priority"
                        aria-label="{{ __('ticket.fields.priority') }}"
                        class="field-control w-auto min-w-44">
                    <option value="">{{ __('ticket.filters.all_priorities') }}</option>
                    @foreach ($priorities as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>

                @can('viewAll', App\Models\Ticket::class)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-slate-600 ring-1 ring-slate-300 transition hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800">
                        <input type="checkbox"
                               wire:model.live="onlyMine"
                               class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 dark:border-slate-600 dark:bg-slate-800">
                        {{ __('ticket.filters.mine') }}
                    </label>
                @endcan

                @if ($this->hasFilters())
                    <button type="button"
                            wire:click="resetFilters"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                        <x-icon name="close" class="size-4" />
                        {{ __('ticket.filters.reset') }}
                    </button>
                @endif
            </div>
        </div>

        <div class="card overflow-hidden">
            @if ($tickets->isEmpty())
                <x-empty-state icon="inbox"
                               :title="__('ticket.empty.title')"
                               :description="$this->hasFilters() ? __('ticket.empty.description') : __('ticket.empty.create')">
                    @if ($this->hasFilters())
                        <button type="button"
                                wire:click="resetFilters"
                                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                            {{ __('ticket.filters.reset') }}
                        </button>
                    @endif
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/40">
                            <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400">
                                <x-th-sort column="reference" :current="$sortColumn" :direction="$sortDirection" class="pl-5">
                                    {{ __('ticket.fields.reference') }}
                                </x-th-sort>
                                <x-th-sort column="title" :current="$sortColumn" :direction="$sortDirection">
                                    {{ __('ticket.fields.title') }}
                                </x-th-sort>
                                <x-th-sort column="status" :current="$sortColumn" :direction="$sortDirection">
                                    {{ __('ticket.fields.status') }}
                                </x-th-sort>
                                <x-th-sort column="priority" :current="$sortColumn" :direction="$sortDirection">
                                    {{ __('ticket.fields.priority') }}
                                </x-th-sort>
                                <th scope="col" class="px-4 py-3">{{ __('ticket.fields.requester') }}</th>
                                <th scope="col" class="px-4 py-3">{{ __('ticket.fields.assignee') }}</th>
                                <x-th-sort column="created_at" :current="$sortColumn" :direction="$sortDirection" class="pr-5">
                                    {{ __('ticket.fields.created_at') }}
                                </x-th-sort>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70" wire:loading.class="opacity-50">
                            @foreach ($tickets as $ticket)
                                <tr wire:key="ticket-{{ $ticket->id }}"
                                    class="group transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="py-3.5 pr-4 pl-5 align-middle">
                                        <a href="{{ route('tickets.show', $ticket) }}"
                                           wire:navigate
                                           class="font-mono text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                                            {{ $ticket->reference }}
                                        </a>
                                    </td>
                                    <td class="max-w-xs px-4 py-3.5 align-middle">
                                        <a href="{{ route('tickets.show', $ticket) }}" wire:navigate class="block">
                                            <span class="block truncate text-sm font-medium text-slate-900 group-hover:text-brand-600 dark:text-slate-100 dark:group-hover:text-brand-400">
                                                {{ $ticket->title }}
                                            </span>
                                            @if ($ticket->comments_count > 0)
                                                <span class="mt-0.5 inline-flex items-center gap-1 text-xs text-slate-400">
                                                    <x-icon name="inbox" class="size-3" />
                                                    {{ trans_choice('ticket.counters.comments', $ticket->comments_count, ['count' => $ticket->comments_count]) }}
                                                </span>
                                            @endif
                                        </a>
                                    </td>
                                    <td class="px-4 py-3.5 align-middle">
                                        <x-badge :classes="$ticket->status->badgeClasses()" :dot="$ticket->status->dotClasses()">
                                            {{ $ticket->status->label() }}
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3.5 align-middle">
                                        <x-badge :classes="$ticket->priority->badgeClasses()" :dot="$ticket->priority->dotClasses()">
                                            {{ $ticket->priority->label() }}
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3.5 align-middle">
                                        <div class="flex items-center gap-2">
                                            <x-avatar :user="$ticket->requester" size="size-7" />
                                            <span class="truncate text-sm text-slate-600 dark:text-slate-300">{{ $ticket->requester->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 align-middle">
                                        @if ($ticket->assignee)
                                            <div class="flex items-center gap-2">
                                                <x-avatar :user="$ticket->assignee" size="size-7" />
                                                <span class="truncate text-sm text-slate-600 dark:text-slate-300">{{ $ticket->assignee->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-sm text-slate-400">{{ __('ticket.filters.unassigned') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 pr-5 pl-4 align-middle">
                                        <time datetime="{{ $ticket->created_at->toIso8601String() }}"
                                              title="{{ $ticket->created_at->isoFormat('LLLL') }}"
                                              class="text-sm whitespace-nowrap text-slate-500 dark:text-slate-400">
                                            {{ $ticket->created_at->diffForHumans(short: true) }}
                                        </time>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($tickets->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                        {{ $tickets->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
