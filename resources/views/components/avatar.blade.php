@props(['user' => null, 'size' => 'size-8'])

@if ($user)
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-600 font-semibold text-white '.$size]) }}
          title="{{ $user->name }}">
        <span class="text-[0.7em]">{{ $user->initials() }}</span>
    </span>
@else
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400 ring-1 ring-slate-200 ring-inset dark:bg-slate-800 dark:text-slate-500 dark:ring-slate-700 '.$size]) }}>
        <x-icon name="user" class="size-[0.6em]" />
    </span>
@endif
