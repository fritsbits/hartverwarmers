@php
    $series = collect($diamantShareTrend)->values();
    $hasTarget = ($kr->target_value ?? null) !== null;

    $points = $series->map(fn ($point) => [
        'label' => $point['label'],
        'share' => $point['share'],
        'target' => $hasTarget ? $kr->target_value : null,
    ])->all();

    $measured = $series->filter(fn ($point) => $point['share'] !== null)->values();
    $delta = $measured->count() >= 2
        ? $measured->last()['share'] - $measured->first()['share']
        : null;

    $strong = $diamantShareCounts['strong'] ?? 0;
    $scored = $diamantShareCounts['scored'] ?? 0;
    $threshold = $diamantShareCounts['threshold'] ?? 70;
@endphp

@if($measured->isEmpty())
    <p class="text-sm text-[var(--color-text-secondary)] mt-3">Nog geen beoordeelde fiches.</p>
@else
    <flux:chart :value="$points" class="aspect-[6/1] mt-3">
        <flux:chart.svg>
            <flux:chart.line field="share" class="text-[var(--color-primary)]" />
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
            <flux:chart.tooltip.value field="share" label="Aandeel" suffix="%" />
        </flux:chart.tooltip>
    </flux:chart>

    <div class="text-xs text-[var(--color-text-tertiary)] mt-3 space-y-1 tabular-nums">
        <p><span class="font-semibold text-[var(--color-text-secondary)]">{{ $strong }}</span> van de {{ $scored }} beoordeelde fiches halen {{ $threshold }}+ op de diamantscore.</p>

        @if($delta !== null)
            <p>
                @if($delta === 0)
                    Ongewijzigd sinds {{ $measured->first()['label'] }}
                @else
                    {{ $delta > 0 ? '+' : '' }}{{ $delta }} procentpunt sinds {{ $measured->first()['label'] }}
                @endif
            </p>
        @endif

        <p>Elk punt telt de fiches die op dat moment bestonden, met hun huidige score.</p>
    </div>
@endif
