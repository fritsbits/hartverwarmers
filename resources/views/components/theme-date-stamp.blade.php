@props(['date', 'badge' => null])

@php
    $d = $date->locale('nl_BE');
@endphp

<div class="inline-flex flex-col items-center w-20">
    <div class="w-full bg-[var(--color-bg-white)] border border-[var(--color-border-light)] rounded-md shadow-[0_2px_5px_rgba(35,30,26,0.08)] overflow-hidden text-center">
        <div class="h-2 bg-[var(--color-primary)]"></div>
        <div class="px-2 pt-2.5 pb-3">
            <div class="text-[10px] font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)]">
                {{ $d->translatedFormat('D') }}
            </div>
            <div class="font-heading font-bold text-4xl text-[var(--color-text-primary)] tabular-nums leading-none mt-1">
                {{ $d->translatedFormat('j') }}
            </div>
            <div class="text-xs text-[var(--color-text-secondary)] mt-1.5 lowercase">
                {{ $d->translatedFormat('M') }}
            </div>
        </div>
        @if($badge)
            <div class="border-t border-[var(--color-border-light)] {{ $badge['emphatic'] ? 'bg-[var(--color-primary)] text-white' : 'bg-[var(--color-bg-accent-light)] text-[var(--color-primary)]' }} text-[10px] font-semibold uppercase tracking-wider py-1">
                {{ $badge['label'] }}
            </div>
        @endif
    </div>
</div>
