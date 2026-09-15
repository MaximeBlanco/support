@use('App\Enums\TicketStatus')

<div>
    <x-slot:header>{{ $ticket->reference }}</x-slot:header>

    <div class="mx-auto max-w-7xl space-y-5">

        <a href="{{ route('tickets.index') }}"
           wire:navigate
           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900 dark:hover:text-slate-100">
            <x-icon name="arrow-left" class="size-4" />
            {{ __('ticket.actions.back') }}
        </a>

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 p-5 sm:p-6 dark:border-slate-800">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-xs font-medium text-brand-600 dark:text-brand-400">{{ $ticket->reference }}</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl dark:text-slate-100">
                            {{ $ticket->title }}
                        </h2>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-badge :classes="$ticket->status->badgeClasses()" :dot="$ticket->status->dotClasses()">
                                {{ $ticket->status->label() }}
                            </x-badge>
                            <x-badge :classes="$ticket->priority->badgeClasses()" :dot="$ticket->priority->dotClasses()">
                                {{ $ticket->priority->label() }}
                            </x-badge>
                            <span class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                                <x-icon name="clock" class="size-3.5" />
                                {{ __('ticket.sla.target', ['hours' => $ticket->priority->targetResolutionHours()]) }}
                            </span>
                            @if ($ticket->resolved_within_target !== null)
                                <x-badge :classes="$ticket->resolved_within_target
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/30'
                                        : 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/30'">
                                    {{ $ticket->resolved_within_target ? __('ticket.sla.within') : __('ticket.sla.breached') }}
                                </x-badge>
                            @endif
                        </div>
                    </div>

                    @can('update', $ticket)
                        <a href="{{ route('tickets.edit', $ticket) }}"
                           wire:navigate
                           class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 ring-1 ring-slate-300 transition hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800">
                            <x-icon name="pencil" class="size-4" />
                            {{ __('ticket.actions.edit') }}
                        </a>
                    @endcan
                </div>
            </div>

            @php
                $canTransition = auth()->user()->can('transition', $ticket);
                $canAssign = auth()->user()->can('assign', $ticket);
                $canClose = auth()->user()->can('close', $ticket);
                $actions = [];

                if ($canTransition && $ticket->status === TicketStatus::Assigned) {
                    $actions[] = ['start', __('ticket.actions.start'), 'play', 'primary'];
                }
                if ($canTransition && $ticket->status === TicketStatus::InProgress) {
                    $actions[] = ['resolve', __('ticket.actions.resolve'), 'check-circle', 'primary'];
                }
                if ($canTransition && $ticket->status === TicketStatus::Resolved) {
                    $actions[] = ['reopen', __('ticket.actions.reopen'), 'refresh', 'neutral'];
                }
                if ($canClose) {
                    $actions[] = ['close', __('ticket.actions.close'), 'archive', 'primary'];
                }
                if ($canAssign && $ticket->status === TicketStatus::Assigned) {
                    $actions[] = ['unassign', __('ticket.actions.unassign'), 'close', 'neutral'];
                }
            @endphp

            @if ($actions !== [])
                <div class="flex flex-wrap gap-2 bg-slate-50/70 px-5 py-4 sm:px-6 dark:bg-slate-800/30">
                    @foreach ($actions as [$method, $label, $icon, $tone])
                        <button type="button"
                                wire:click="{{ $method }}"
                                wire:loading.attr="disabled"
                                wire:target="{{ $method }}"
                                class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition disabled:opacity-60
                                    {{ $tone === 'primary'
                                        ? 'bg-brand-600 text-white shadow-sm hover:bg-brand-700'
                                        : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800' }}">
                            <x-icon :name="$icon" class="size-4" />
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="grid gap-5 lg:grid-cols-3">

            <div class="space-y-5 lg:col-span-2">
                <section class="card p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ticket.fields.description') }}</h3>
                    <p class="mt-3 text-sm leading-relaxed whitespace-pre-line text-slate-600 dark:text-slate-300">{{ $ticket->description }}</p>
                </section>

                <section class="card overflow-hidden">
                    <h3 class="border-b border-slate-100 px-5 py-4 text-sm font-semibold text-slate-900 sm:px-6 dark:border-slate-800 dark:text-slate-100">
                        {{ trans_choice('ticket.counters.comments', $ticket->comments->count(), ['count' => $ticket->comments->count()]) }}
                    </h3>

                    @can('comment', $ticket)
                        <form wire:submit="comment" class="border-b border-slate-100 p-5 sm:p-6 dark:border-slate-800">
                            <div class="flex gap-3">
                                <x-avatar :user="auth()->user()" size="size-9" class="mt-0.5" />
                                <div class="min-w-0 flex-1">
                                    <label for="comment-body" class="sr-only">{{ __('ticket.actions.comment') }}</label>
                                    <textarea id="comment-body"
                                              rows="3"
                                              wire:model="body"
                                              placeholder="{{ __('ticket.placeholders.comment') }}"
                                              class="field-control resize-y @error('body') field-control-invalid @enderror"></textarea>
                                    @error('body')
                                        <p class="field-error">
                                            <x-icon name="warning" class="size-4 shrink-0" />
                                            {{ $message }}
                                        </p>
                                    @enderror
                                    <div class="mt-3 flex justify-end">
                                        <button type="submit"
                                                wire:loading.attr="disabled"
                                                wire:target="comment"
                                                class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-60">
                                            <x-icon name="send" class="size-4" />
                                            {{ __('ticket.actions.comment') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endcan

                    @forelse ($ticket->comments as $comment)
                        <article wire:key="comment-{{ $comment->id }}"
                                 class="flex gap-3 border-b border-slate-100 p-5 last:border-0 sm:px-6 dark:border-slate-800">
                            <x-avatar :user="$comment->author" size="size-9" class="mt-0.5" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-2">
                                    <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $comment->author->name }}</span>
                                    <time datetime="{{ $comment->created_at->toIso8601String() }}"
                                          title="{{ $comment->created_at->isoFormat('LLLL') }}"
                                          class="text-xs text-slate-400">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </time>
                                </div>
                                <p class="mt-1.5 text-sm leading-relaxed whitespace-pre-line text-slate-600 dark:text-slate-300">{{ $comment->body }}</p>
                            </div>
                        </article>
                    @empty
                        <x-empty-state icon="inbox" :title="__('ticket.empty.comments')" class="py-10" />
                    @endforelse
                </section>
            </div>

            <div class="space-y-5">
                <section class="card p-5 sm:p-6">
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">{{ __('ticket.fields.requester') }}</dt>
                            <dd class="mt-1.5 flex items-center gap-2">
                                <x-avatar :user="$ticket->requester" size="size-7" />
                                <span class="truncate text-slate-700 dark:text-slate-200">{{ $ticket->requester->name }}</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">{{ __('ticket.fields.assignee') }}</dt>
                            <dd class="mt-1.5 flex items-center gap-2">
                                <x-avatar :user="$ticket->assignee" size="size-7" />
                                <span class="truncate {{ $ticket->assignee ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400' }}">
                                    {{ $ticket->assignee?->name ?? __('ticket.filters.unassigned') }}
                                </span>
                            </dd>
                        </div>

                        <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">{{ __('ticket.fields.created_at') }}</dt>
                            <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $ticket->created_at->isoFormat('LLL') }}</dd>
                        </div>

                        @if ($ticket->resolved_at)
                            <div>
                                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">{{ __('ticket.fields.resolved_at') }}</dt>
                                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $ticket->resolved_at->isoFormat('LLL') }}</dd>
                            </div>
                        @endif

                        @if ($ticket->closed_at)
                            <div>
                                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase dark:text-slate-400">{{ __('ticket.fields.closed_at') }}</dt>
                                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ $ticket->closed_at->isoFormat('LLL') }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                @can('assign', $ticket)
                    <section class="card p-5 sm:p-6">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ $ticket->assignee ? __('ticket.actions.reassign') : __('ticket.actions.assign') }}
                        </h3>
                        <div class="mt-3 space-y-3">
                            <div>
                                <label for="assignee" class="sr-only">{{ __('ticket.fields.assignee') }}</label>
                                <select id="assignee"
                                        wire:model="assigneeId"
                                        class="field-control @error('assigneeId') field-control-invalid @enderror">
                                    <option value="">{{ __('ticket.filters.unassigned') }}</option>
                                    @foreach ($technicians as $technician)
                                        <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                                    @endforeach
                                </select>
                                @error('assigneeId')
                                    <p class="field-error">
                                        <x-icon name="warning" class="size-4 shrink-0" />
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <button type="button"
                                    wire:click="assign"
                                    wire:loading.attr="disabled"
                                    wire:target="assign"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                                <x-icon name="users" class="size-4" />
                                {{ __('ticket.actions.assign') }}
                            </button>
                        </div>
                    </section>
                @endcan

                <section class="card p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ticket.timeline.title') }}</h3>
                    <ol class="mt-4 space-y-4">
                        @foreach ($ticket->statusChanges as $change)
                            <li wire:key="change-{{ $change->id }}" class="relative flex gap-3 pb-4 last:pb-0">
                                @unless ($loop->last)
                                    <span class="absolute top-6 left-[7px] h-full w-px bg-slate-200 dark:bg-slate-700" aria-hidden="true"></span>
                                @endunless
                                <span class="relative mt-1 size-3.5 shrink-0 rounded-full ring-4 ring-white {{ $change->to_status->dotClasses() }} dark:ring-slate-900" aria-hidden="true"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-slate-700 dark:text-slate-300">
                                        @if ($change->from_status === null)
                                            {{ __('ticket.timeline.created', ['author' => $change->author?->name ?? __('ticket.timeline.system')]) }}
                                        @else
                                            {{ __('ticket.timeline.changed', [
                                                'author' => $change->author?->name ?? __('ticket.timeline.system'),
                                                'from' => $change->from_status->label(),
                                                'to' => $change->to_status->label(),
                                            ]) }}
                                        @endif
                                    </p>
                                    <time datetime="{{ $change->created_at->toIso8601String() }}" class="text-xs text-slate-400">
                                        {{ $change->created_at->isoFormat('LLL') }}
                                    </time>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </div>
        </div>
    </div>
</div>
