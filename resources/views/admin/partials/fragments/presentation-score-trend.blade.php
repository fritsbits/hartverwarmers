@php
    $series = collect($weeklyTrend)->values();
    $lastIndex = $series->count() - 1;
    $hasTarget = ($kr->target_value ?? null) !== null;

    $periodWord = match ($range ?? 'month') {
        'week' => 'dag',
        'alltime' => 'maand',
        default => 'week',
    };

    $points = $series->map(fn ($w, $i) => [
        'week_label' => $w['week_label'],
        'value' => $i < $lastIndex ? $w['avg_score'] : null,
        'pending' => $i === $lastIndex ? $w['avg_score'] : null,
        'target' => $hasTarget ? $kr->target_value : null,
    ])->all();

    $completed = $series->take($lastIndex < 0 ? 0 : $lastIndex)
        ->filter(fn ($w) => $w['avg_score'] !== null)
        ->values();
    $lastCompleted = $completed->last()['avg_score'] ?? null;
    $completedDelta = $completed->count() >= 2
        ? $completed->last()['avg_score'] - $completed->first()['avg_score']
        : null;

    $inProgress = $series->last();
    $inProgressScore = $inProgress['avg_score'] ?? null;

    $anyScored = $series->contains(fn ($w) => $w['avg_score'] !== null);
    $hasEmptyPeriod = $series->take(max($lastIndex, 0))->contains(fn ($w) => $w['avg_score'] === null);
@endphp

@if(! $anyScored)
    <p class="text-sm text-[var(--color-text-secondary)] mt-3">Nog geen beoordeelde fiches.</p>
@else
    <flux:chart :value="$points" class="aspect-[6/1] mt-3">
        <flux:chart.svg>
            <flux:chart.bar field="value" class="text-[var(--color-primary)]" />
            <flux:chart.bar field="pending" class="text-[var(--color-primary)]/30" />
            @if($hasTarget)
                <flux:chart.line field="target" class="text-[var(--color-text-secondary)] [stroke-dasharray:6_5]" />
            @endif
            <flux:chart.axis axis="x" field="week_label">
                <flux:chart.axis.tick />
            </flux:chart.axis>
            <flux:chart.axis axis="y" :tick-values="[0, 25, 50, 75, 100]">
                <flux:chart.axis.grid class="stroke-[var(--color-border-light)]" />
                <flux:chart.axis.tick />
            </flux:chart.axis>
        </flux:chart.svg>

        <flux:chart.tooltip>
            <flux:chart.tooltip.heading field="week_label" />
            <flux:chart.tooltip.value field="value" label="Score" />
            <flux:chart.tooltip.value field="pending" label="Score (lopend)" />
        </flux:chart.tooltip>
    </flux:chart>

    <div class="text-xs text-[var(--color-text-tertiary)] mt-3 space-y-1 tabular-nums">
        @if($lastCompleted !== null)
            <p>
                Laatste volledige {{ $periodWord }}: <span class="font-semibold text-[var(--color-text-secondary)]">{{ $lastCompleted }}</span>
                @if($completedDelta !== null && $completedDelta !== 0)
                    <span class="font-semibold {{ $completedDelta > 0 ? 'text-green-600' : 'text-red-500' }}">
                        ({{ $completedDelta > 0 ? '+' : '' }}{{ $completedDelta }} sinds de start)
                    </span>
                @endif
            </p>
        @endif
        @if($inProgressScore !== null)
            <p>Lopende {{ $periodWord }}: {{ $inProgressScore }} &mdash; nog niet volledig, telt niet mee.</p>
        @endif
        @if($hasTarget)
            <p>Stippellijn = doel ({{ $kr->target_value }}{{ $kr->target_unit }}).</p>
        @endif
        @if($hasEmptyPeriod)
            <p>Een lege {{ $periodWord }} = geen beoordeelde fiches in die periode.</p>
        @endif
    </div>
@endif
