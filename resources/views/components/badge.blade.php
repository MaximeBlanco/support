@props(['classes' => '', 'dot' => null])

<span {{ $attributes->merge(['class' => 'badge '.$classes]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dot }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
