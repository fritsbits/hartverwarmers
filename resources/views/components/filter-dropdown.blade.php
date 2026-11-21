@props(['label', 'selected' => 0])

<div x-data="{ open: false }" x-on:keydown.escape.window="if (open) { open = false; $refs.button.focus() }" x-on:click.outside="open = false" class="relative max-sm:flex-1 max-sm:basis-[calc(50%-0.3125rem)]">
    <button type="button" x-ref="button" x-on:click="open = !open" :aria-expanded="open" aria-haspopup="true"
            @class([
                'inline-flex items-center gap-2 h-11 w-full sm:w-auto rounded-[10px] border px-3.5 pr-3 text-[15px] font-medium cursor-pointer transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]',
                'border-[var(--color-primary)] bg-[var(--color-bg-accent-light)] text-[var(--color-primary-hover)]' => $selected > 0,
                'border-[var(--color-border-light)] bg-white text-[var(--color-text-primary)] hover:border-[var(--color-border-hover)]' => $selected === 0,
            ])>
        {{ $label }}
        @if($selected > 0)
            <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-[var(--color-primary)] text-white text-xs font-bold tabular-nums">{{ $selected }}</span>
        @endif
        <svg class="h-4 w-4 ml-auto text-[var(--color-text-secondary)] transition-transform" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
    </button>

    <div x-cloak x-show="open" x-transition.opacity.duration.100ms role="group" aria-label="{{ $label }}"
         class="absolute top-full left-0 mt-1.5 z-40 w-[300px] max-w-[calc(100vw-3rem)] max-h-[380px] overflow-auto rounded-xl border border-[var(--color-border-light)] bg-white p-1.5 shadow-lg max-sm:[div:nth-child(odd)>&]:left-auto max-sm:[div:nth-child(odd)>&]:right-0">
        {{ $slot }}
    </div>
</div>
