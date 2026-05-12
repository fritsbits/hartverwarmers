@props(['date', 'badge' => null])

@php
    $d = $date->locale('nl_BE');
    $isToday = $badge && $badge['label'] === 'Vandaag';
@endphp

<div class="inline-flex flex-col items-stretch w-20">
    <div class="w-full text-center px-3 py-4 rounded-md border shadow-[0_2px_5px_rgba(35,30,26,0.06)]
        {{ $isToday
            ? 'bg-[var(--color-primary)] border-[var(--color-primary)]'
            : 'bg-[var(--color-bg-white)] border-[var(--color-border-light)]' }}">
        <div class="text-[10px] font-semibold uppercase tracking-widest
            {{ $isToday ? 'text-white/85' : 'text-[var(--color-text-tertiary)]' }}">
            {{ $d->translatedFormat('D') }}
        </div>
        <div class="font-heading font-bold text-4xl tabular-nums leading-none mt-2
            {{ $isToday ? 'text-white' : 'text-[var(--color-primary)]' }}">
            {{ $d->translatedFormat('j') }}
        </div>
        <div class="text-xs mt-2 lowercase
            {{ $isToday ? 'text-white/85' : 'text-[var(--color-text-secondary)]' }}">
            {{ $d->translatedFormat('M') }}
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
