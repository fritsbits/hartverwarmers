@props(['impact'])

@php
    /** @var \App\Services\Okr\InitiativeKrImpact $impact */
    $hasNumbers = $impact->baselineValue !== null && $impact->currentValue !== null;
    $unit = $impact->unit;
    $isPercent = $unit === '%';

    $delta = $impact->delta;
    $hasChange = $delta !== null && $delta != 0;
    $up = $delta !== null && $delta > 0;

    $changeColor = ! $hasChange
        ? 'text-[var(--color-text-secondary)]'
        : ($up ? 'text-green-700' : 'text-red-600');

    // A change in a percentage is expressed in "procentpunt", not "%": going
    // from 0% to 8% is +8 procentpunt. Counts just go up or down by a number.
    $changeNoun = $isPercent ? ' procentpunt' : ($unit !== '' ? $unit : '');
    $changeWord = $isPercent ? ($up ? 'hoger' : 'lager') : ($up ? 'meer' : 'minder');

    // Bar heights, scaled to the larger of the two values so the taller bar
    // fills the chart. A zero value shows no bar — just its label.
    $base = (float) ($impact->baselineValue ?? 0);
    $current = (float) ($impact->currentValue ?? 0);
    $scale = max($base, $current, 1);
    $baseHeight = $base <= 0 ? 0 : max(6, round($base / $scale * 100));
    $currentHeight = $current <= 0 ? 0 : max(6, round($current / $scale * 100));
@endphp

<div class="space-y-3 rounded-lg border border-[var(--color-border-light)] bg-white p-4">
    <p class="text-sm font-semibold text-[var(--color-text-primary)]">{{ $impact->krLabel }}</p>

    @if($hasNumbers)
        <p class="text-sm text-[var(--color-text-secondary)]">
            Van <span class="font-semibold tabular-nums text-[var(--color-text-primary)]">{{ $impact->baselineValue }}{{ $unit }}</span> bij de start
            naar <span class="font-semibold tabular-nums text-[var(--color-text-primary)]">{{ $impact->currentValue }}{{ $unit }}</span> nu
            @if($hasChange)
                &mdash; <span class="font-bold tabular-nums {{ $changeColor }}">{!! $up ? '&uarr;' : '&darr;' !!} {{ abs($delta) }}{{ $changeNoun }} {{ $changeWord }}</span>.
            @else
                &mdash; nog geen verschil sinds de start.
            @endif
        </p>

        {{-- Before → after: two bars, "bij de start" (muted) versus "nu" (orange). --}}
        <div>
            <div class="flex items-end justify-around gap-6 h-24" role="img"
                 aria-label="Bij de start {{ $impact->baselineValue }}{{ $unit }}, nu {{ $impact->currentValue }}{{ $unit }}">
                <div class="flex flex-1 flex-col items-center justify-end h-full">
                    <span class="mb-1 text-sm font-bold tabular-nums text-[var(--color-text-secondary)]">{{ $impact->baselineValue }}{{ $unit }}</span>
                    <div class="w-full max-w-12 rounded-t bg-[var(--color-text-tertiary)]" style="height: {{ $baseHeight }}%"></div>
                </div>
                <div class="flex flex-1 flex-col items-center justify-end h-full">
                    <span class="mb-1 text-sm font-bold tabular-nums text-[var(--color-primary)]">{{ $impact->currentValue }}{{ $unit }}</span>
                    <div class="w-full max-w-12 rounded-t bg-[var(--color-primary)]" style="height: {{ $currentHeight }}%"></div>
                </div>
            </div>
            <div class="flex justify-around gap-6 mt-1.5 text-[11px] text-[var(--color-text-tertiary)]">
                <span class="flex-1 text-center">Bij de start</span>
                <span class="flex-1 text-center">Nu</span>
            </div>
        </div>
    @else
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen meting beschikbaar.</p>
    @endif

    @if($impact->baselineLowData || $impact->currentLowData)
        <p class="text-xs text-[var(--color-text-tertiary)]">Te weinig data voor betrouwbare conclusies.</p>
    @endif
</div>
