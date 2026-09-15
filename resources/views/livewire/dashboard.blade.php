<div>
    <x-slot:header>{{ __('app.dashboard.title') }}</x-slot:header>

    <div class="mx-auto max-w-7xl space-y-6">

        <div>
            <h2 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl dark:text-slate-100">
                {{ __('app.dashboard.greeting', ['name' => auth()->user()->name]) }}
            </h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('app.dashboard.subtitle') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</p>
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $stat['tone'] }}">
                            <x-icon :name="$stat['icon']" class="size-[18px]" />
                        </span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 tabular-nums dark:text-slate-100">
                        {{ $stat['value'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="card p-5 sm:p-6">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('app.dashboard.by_status') }}</h3>
                <ul class="mt-5 space-y-3.5">
                    @foreach ($byStatus as $row)
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                    <span class="size-2 rounded-full {{ $row['case']->dotClasses() }}" aria-hidden="true"></span>
                                    {{ $row['case']->label() }}
                                </span>
                                <span class="font-semibold text-slate-900 tabular-nums dark:text-slate-100">{{ $row['total'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full {{ $row['case']->dotClasses() }} transition-all duration-500"
                                     style="width: {{ $total > 0 ? round($row['total'] / $total * 100, 1) : 0 }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card p-5 sm:p-6">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('app.dashboard.by_priority') }}</h3>
                <ul class="mt-5 space-y-3.5">
                    @foreach ($byPriority as $row)
                        <li>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                    <span class="size-2 rounded-full {{ $row['case']->dotClasses() }}" aria-hidden="true"></span>
                                    {{ $row['case']->label() }}
                                    <span class="text-xs text-slate-400">· {{ $row['case']->targetResolutionHours() }} h</span>
                                </span>
                                <span class="font-semibold text-slate-900 tabular-nums dark:text-slate-100">{{ $row['total'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full {{ $row['case']->dotClasses() }} transition-all duration-500"
                                     style="width: {{ $total > 0 ? round($row['total'] / $total * 100, 1) : 0 }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('app.dashboard.recent') }}</h3>
                <a href="{{ route('tickets.index') }}"
                   wire:navigate
                   class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400">
                    {{ __('app.dashboard.see_all') }}
                    <x-icon name="chevron-right" class="size-4" />
                </a>
            </div>

            @forelse ($recent as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}"
                   wire:navigate
                   wire:key="recent-{{ $ticket->id }}"
                   class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 transition last:border-0 hover:bg-slate-50 sm:px-6 dark:border-slate-800 dark:hover:bg-slate-800/40">
                    <x-avatar :user="$ticket->requester" size="size-9" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $ticket->title }}</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                            {{ $ticket->reference }} · {{ $ticket->requester->name }} · {{ $ticket->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="hidden shrink-0 items-center gap-2 sm:flex">
                        <x-badge :classes="$ticket->priority->badgeClasses()" :dot="$ticket->priority->dotClasses()">
                            {{ $ticket->priority->label() }}
                        </x-badge>
                        <x-badge :classes="$ticket->status->badgeClasses()" :dot="$ticket->status->dotClasses()">
                            {{ $ticket->status->label() }}
                        </x-badge>
                    </div>
                    <x-icon name="chevron-right" class="size-4 shrink-0 text-slate-300 dark:text-slate-600" />
                </a>
            @empty
                <x-empty-state icon="inbox" :title="__('ticket.empty.title')" :description="__('ticket.empty.create')" />
            @endforelse
        </section>
    </div>
</div>
