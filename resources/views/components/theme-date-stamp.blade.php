@props(['date'])

@php
    $d = $date->locale('nl_BE');
@endphp

<div class="inline-flex flex-col w-20 bg-[var(--color-bg-white)] border border-[var(--color-border-light)] rounded-md shadow-[0_1px_2px_rgba(35,30,26,0.06)] overflow-hidden text-center">
    <div class="h-1 bg-[var(--color-primary)]"></div>
    <div class="px-2 pt-2 pb-3">
        <div class="text-[10px] font-semibold uppercase tracking-widest text-[var(--color-text-tertiary)]">
            {{ $d->translatedFormat('D') }}
        </div>
        <div class="font-heading font-bold text-3xl text-[var(--color-text-primary)] tabular-nums leading-none mt-1">
            {{ $d->translatedFormat('j') }}
        </div>
        <div class="text-xs text-[var(--color-text-secondary)] mt-1.5">
            {{ $d->translatedFormat('M') }}
        </div>
    </div>
</div>
