@php
    $normalizedTrend = collect($weeklyTrend)
        ->map(fn ($w) => [...$w, 'avg_score' => $w['avg_score'] ?? 0])
        ->values()
        ->all();

    $scored = collect($normalizedTrend)->filter(fn ($w) => $w['avg_score'] > 0);
    $currentScore = $scored->last()['avg_score'] ?? null;
    $currentLabel = match ($range) {
        'week' => 'meest recent',
        'alltime' => 'huidige maand',
        default => 'huidige week',
    };
@endphp

@if($scored->isEmpty())
    <p class="text-sm text-[var(--color-text-secondary)] mt-3">Nog geen beoordeelde fiches.</p>
@else
    <flux:chart :value="$normalizedTrend" class="aspect-[6/1] mt-3">
        <flux:chart.svg>
            <flux:chart.bar field="avg_score" class="text-[var(--color-primary)]" />
            <flux:chart.axis axis="x" field="week_label">
                <flux:chart.axis.tick />
            </flux:chart.axis>
            <flux:chart.axis axis="y" :tick-values="[0, 25, 50, 75, 100]">
                <flux:chart.axis.grid class="stroke-[var(--color-border-light)]" />
                <flux:chart.axis.tick />
            </flux:chart.axis>
        </flux:chart.svg>
    </flux:chart>

    @if($currentScore !== null)
        <p class="text-xs text-[var(--color-text-tertiary)] mt-3 tabular-nums">
            {{ $currentScore }} {{ $currentLabel }}
            @if($trendDelta !== null && $trendDelta !== 0)
                <span class="font-semibold {{ $trendDelta >= 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ $trendDelta >= 0 ? '+' : '' }}{{ $trendDelta }}
                </span>
            @endif
        </p>
    @endif
@endif
