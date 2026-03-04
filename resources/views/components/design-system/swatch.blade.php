@props(['color', 'name', 'hex', 'border' => false])

<div class="text-center">
    <div
        class="w-full aspect-square rounded-lg {{ $border ? 'border border-[var(--color-border-light)]' : '' }}"
        style="background-color: {{ $color }};"
    ></div>
    <p class="mt-2 text-sm font-semibold">{{ $name }}</p>
    <p class="text-xs text-[var(--color-text-secondary)]">{{ $hex }}</p>
</div>
