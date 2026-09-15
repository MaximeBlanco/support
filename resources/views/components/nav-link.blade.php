@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}"
   wire:navigate
   @if ($active) aria-current="page" @endif
   class="group nav-link {{ $active ? 'nav-link-active' : 'nav-link-idle' }}">
    <x-icon :name="$icon" class="size-5 shrink-0 {{ $active ? 'text-brand-300' : 'text-slate-400 group-hover:text-slate-200' }}" />
    <span class="truncate">{{ $slot }}</span>
</a>
