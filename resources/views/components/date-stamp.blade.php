@props(['date', 'badge' => null])

@php
    $d = $date->locale('nl_BE');
    $isToday = $badge && $badge['label'] === 'Vandaag';
@endphp

<div class="inline-flex flex-col items-stretch w-[5.5rem]">
    <div class="w-full overflow-hidden rounded border shadow-[0_5px_12px_-3px_rgba(35,30,26,0.10),0_2px_4px_rgba(35,30,26,0.05)]
        {{ $isToday
            ? 'bg-[var(--color-primary)] border-[var(--color-primary)]'
            : 'bg-[var(--color-bg-white)] border-[var(--color-border-light)]' }}">
        @if(! $isToday)
            <div class="h-1.5 bg-[var(--color-primary)]"></div>
        @endif
        <div class="px-2 py-2 text-center">
            <div class="font-body text-[9px] font-semibold uppercase tracking-wide
                {{ $isToday ? 'text-white/85' : 'text-[var(--color-text-secondary)]' }}">
                {{ $d->translatedFormat('l') }}
            </div>
            <div class="font-heading font-bold text-4xl tabular-nums leading-none mt-1
                {{ $isToday ? 'text-white' : 'text-[var(--color-primary)]' }}">
                {{ $d->translatedFormat('j') }}
            </div>
            <div class="font-body text-[11px] lowercase mt-0.5
                {{ $isToday ? 'text-white/85' : 'text-[var(--color-text-secondary)]' }}">
                {{ $d->translatedFormat('F') }}
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
