@props(['initiative', 'headline'])

@php
    /** @var \App\Models\Okr\Initiative $initiative */
    /** @var \App\Services\Okr\InitiativeKrImpact|null $headline */
    $objective = $initiative->objective;
    $weeksLive = $initiative->started_at !== null
        ? (int) $initiative->started_at->diffInWeeks(now())
        : null;
    $deepLink = '?tab='.$objective->slug.'&init='.$initiative->slug;

    $delta = $headline?->delta;
    $hasNumbers = $headline !== null
        && $headline->baselineValue !== null
        && $headline->currentValue !== null;
    $deltaUnit = $headline !== null
        ? ($headline->unit === '%' ? 'pp' : $headline->unit)
        : '';
    $deltaColor = $delta === null || $delta == 0
        ? 'text-[var(--color-text-tertiary)]'
        : ($delta > 0 ? 'text-green-700' : 'text-red-600');
@endphp

<a href="{{ $deepLink }}" class="group flex items-center justify-between gap-4 p-4 hover:bg-[var(--color-bg-accent-light)] transition-colors" aria-label="{{ $initiative->label }}">
    <div class="min-w-0">
        <p class="font-heading font-bold text-[var(--color-text-primary)] truncate">{{ $initiative->label }}</p>
        @if($weeksLive !== null)
            <p class="text-xs text-[var(--color-text-tertiary)] tabular-nums">{{ $weeksLive }} {{ $weeksLive === 1 ? 'week' : 'weken' }} live</p>
        @endif
    </div>

    <div class="flex items-center gap-4 whitespace-nowrap">
        <span class="text-sm {{ $deltaColor }}">
            @if($hasNumbers && $delta !== null && $delta != 0)
                <span class="tabular-nums">{{ $delta > 0 ? '+' : '' }}{{ $delta }}{{ $deltaUnit }}</span>
                <span class="text-[var(--color-text-secondary)]">sinds start</span>
            @elseif($hasNumbers)
                <span class="text-[var(--color-text-secondary)]">Nog geen verschil sinds start</span>
            @else
                <span class="text-[var(--color-text-tertiary)]">Nog geen meting</span>
            @endif
        </span>
        <span class="text-xs font-semibold uppercase tracking-widest text-[var(--color-primary)]">{{ $objective->title }} &rarr;</span>
    </div>
</a>
