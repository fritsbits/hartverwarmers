@php
    $rows = collect($thankTrend)
        ->skipWhile(fn ($b) => $b['downloads'] === 0)
        ->values();

    $periodWord = match ($range ?? 'month') {
        'quarter' => 'week',
        'alltime' => 'maand',
        default => 'dag',
    };

    $lastIndex = $rows->count() - 1;
    $hasTarget = ($kr->target_value ?? null) !== null;

    // A bucket with no downloads has no rate to plot — keep it null (an honest
    // "no data" gap) rather than a fake 0% bar.
    $points = $rows->map(fn ($b, $i) => [
        'label' => $b['label'],
        'value' => $i < $lastIndex && $b['downloads'] > 0 ? $b['rate'] : null,
        'pending' => $i === $lastIndex && $b['downloads'] > 0 ? $b['rate'] : null,
        'target' => $hasTarget ? $kr->target_value : null,
    ])->all();

    $hasEmptyPeriod = $rows->slice(0, max($lastIndex, 0))->contains(fn ($b) => $b['downloads'] === 0);
@endphp

@if($rows->isEmpty() || $rows->sum('downloads') === 0)
    <p class="text-sm text-[var(--color-text-secondary)] mt-3">Nog geen downloads in deze periode.</p>
@else
    <flux:chart :value="$points" class="aspect-[6/1] mt-2">
        <flux:chart.svg>
            <flux:chart.bar field="value" class="text-[var(--color-primary)]" />
            <flux:chart.bar field="pending" class="text-[var(--color-primary)]/30" />
            @if($hasTarget)
                <flux:chart.line field="target" class="text-[var(--color-text-secondary)] [stroke-dasharray:6_5]" />
            @endif
            <flux:chart.axis axis="x" field="label">
                <flux:chart.axis.tick />
            </flux:chart.axis>
            <flux:chart.axis axis="y" :tick-values="[0, 25, 50, 75, 100]">
                <flux:chart.axis.grid class="stroke-[var(--color-border-light)]" />
                <flux:chart.axis.tick />
            </flux:chart.axis>
        </flux:chart.svg>

        <flux:chart.tooltip>
            <flux:chart.tooltip.heading field="label" />
            <flux:chart.tooltip.value field="value" label="Bedankt" suffix="%" />
            <flux:chart.tooltip.value field="pending" label="Bedankt (lopend)" suffix="%" />
        </flux:chart.tooltip>
    </flux:chart>

    <p class="text-xs text-[var(--color-text-tertiary)] mt-2">
        Laatste balk = lopende {{ $periodWord }} (nog niet volledig).
        @if($hasTarget)
            Stippellijn = doel ({{ $kr->target_value }}{{ $kr->target_unit }}).
        @endif
        @if($hasEmptyPeriod)
            Een lege {{ $periodWord }} = geen downloads in die periode.
        @endif
    </p>

    @if($thankStats['totalThankedAllTime'] > 0)
        <p class="text-xs text-[var(--color-text-tertiary)] mt-1 tabular-nums">
            Sinds de start: {{ $thankStats['totalThankedAllTime'] }} downloads in totaal bedankt.
        </p>
    @endif

    @if($thankStats['lowData'])
        <p class="text-xs text-[var(--color-text-tertiary)] mt-1">Te weinig data voor betrouwbare conclusies.</p>
    @endif
@endif
