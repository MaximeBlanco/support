<div x-data="{ open: false }" class="relative">
    <button type="button"
            @click="open = !open"
            @click.outside="open = false"
            class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-100"
            :aria-expanded="open.toString()"
            aria-haspopup="true"
            aria-label="{{ __('app.nav.notifications') }}">
        <x-icon name="bell" />
        @if ($unreadCount > 0)
            <span class="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-semibold text-white ring-2 ring-white dark:ring-slate-950">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
            <span class="sr-only">{{ trans_choice('app.notifications.unread', $unreadCount, ['count' => $unreadCount]) }}</span>
        @endif
    </button>

    <div x-show="open"
         x-transition.origin.top.right
         x-cloak
         class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">

        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('app.notifications.title') }}</p>
            @if ($unreadCount > 0)
                <button type="button"
                        wire:click="markAllRead"
                        class="text-xs font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400">
                    {{ __('app.notifications.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                <a href="{{ route('tickets.show', $notification->data['ticket_id']) }}"
                   wire:navigate
                   wire:key="notif-{{ $notification->id }}"
                   class="flex gap-3 border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50 {{ $notification->read_at === null ? 'bg-brand-50/50 dark:bg-brand-950/20' : '' }}">
                    <span class="mt-1 size-2 shrink-0 rounded-full {{ $notification->read_at === null ? 'bg-brand-500' : 'bg-transparent' }}" aria-hidden="true"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-slate-700 dark:text-slate-200">
                            {{ __($notification->data['message_key'], ['reference' => $notification->data['reference']]) }}
                        </p>
                        <p class="mt-0.5 truncate text-xs text-slate-400">{{ $notification->data['title'] }}</p>
                        <time datetime="{{ $notification->created_at->toIso8601String() }}" class="text-xs text-slate-400">
                            {{ $notification->created_at->diffForHumans() }}
                        </time>
                    </div>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-400">{{ __('app.notifications.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
