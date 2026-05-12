@props(['date', 'badge' => null])

@php
    $d = $date->locale('nl_BE');
    $isToday = $badge && $badge['label'] === 'Vandaag';
@endphp

<div class="inline-flex flex-col items-stretch w-28">
    <div class="w-full overflow-hidden rounded border shadow-[0_6px_16px_-4px_rgba(35,30,26,0.12),0_2px_4px_rgba(35,30,26,0.06)]
        {{ $isToday
            ? 'bg-[var(--color-primary)] border-[var(--color-primary)]'
            : 'bg-[var(--color-bg-cream)] border-[var(--color-border-light)]' }}">
        @if(! $isToday)
            <div class="h-2 bg-[var(--color-primary)]"></div>
        @endif
        <div class="px-3 py-3 text-center">
            <div class="font-body text-[10px] font-semibold uppercase tracking-wider
                {{ $isToday ? 'text-white/85' : 'text-[var(--color-text-tertiary)]' }}">
                {{ $d->translatedFormat('l') }}
            </div>
            <div class="font-heading font-bold text-5xl tabular-nums leading-none mt-2
                {{ $isToday ? 'text-white' : 'text-[var(--color-primary)]' }}">
                {{ $d->translatedFormat('j') }}
            </div>
            <div class="font-body text-xs lowercase mt-2
                {{ $isToday ? 'text-white/85' : 'text-[var(--color-text-secondary)]' }}">
                {{ $d->translatedFormat('M') }}
            </div>
        </div>
    </div>
    @if($badge)
        <span class="mt-2 self-center text-[10px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full whitespace-nowrap
            {{ $badge['emphatic']
                ? 'bg-[var(--color-primary)] text-white'
                : 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' }}">
            {{ $badge['label'] }}
        </span>
    @endif
</div>
