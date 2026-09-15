@props(['column', 'current', 'direction'])

@php
    $isActive = $current === $column;
    $ariaSort = $isActive ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none';
@endphp

<th scope="col"
    aria-sort="{{ $ariaSort }}"
    {{ $attributes->merge(['class' => 'px-4 py-3']) }}>
    <button type="button"
            wire:click="sort('{{ $column }}')"
            class="th-sortable inline-flex items-center gap-1 font-semibold tracking-wide uppercase {{ $isActive ? 'text-slate-900 dark:text-slate-100' : '' }}"
            aria-label="{{ __('app.common.sort_by', ['column' => strip_tags($slot)]) }}">
        {{ $slot }}
        @if ($isActive)
            <x-icon :name="$direction === 'asc' ? 'chevron-up' : 'chevron-down'" class="size-3.5 text-brand-500" />
        @else
            <x-icon name="sort" class="size-3 text-slate-300 dark:text-slate-600" />
        @endif
    </button>
</th>
