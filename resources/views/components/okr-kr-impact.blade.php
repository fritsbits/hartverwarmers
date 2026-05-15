@props(['impact'])

@php
    /** @var \App\Services\Okr\InitiativeKrImpact $impact */
    $hasNumbers = $impact->baselineValue !== null && $impact->currentValue !== null;
    $deltaSign = $impact->delta === null ? '' : ($impact->delta > 0 ? '+' : '');
    $deltaColor = $impact->delta === null
        ? 'text-[var(--color-text-tertiary)]'
        : ($impact->delta > 0
            ? 'text-green-700'
            : ($impact->delta < 0 ? 'text-red-600' : 'text-[var(--color-text-tertiary)]'));
    $deltaUnit = $impact->unit === '%' ? 'pp' : $impact->unit;
    $startLabel = ! empty($impact->sparkline) ? ($impact->sparkline[$impact->markerIndex]['label'] ?? null) : null;
@endphp

<div class="space-y-2 rounded-lg border border-[var(--color-border-light)] bg-white p-4">
    <p class="text-sm font-semibold text-[var(--color-text-primary)]">{{ $impact->krLabel }}</p>

    @if($hasNumbers)
        <div class="flex items-baseline gap-2">
            <span class="text-sm text-[var(--color-text-tertiary)] tabular-nums">{{ $impact->baselineValue }}{{ $impact->unit }}</span>
            <span class="text-xs text-[var(--color-text-tertiary)]">&rarr;</span>
            <span class="text-2xl font-bold text-[var(--color-primary)] tabular-nums">{{ $impact->currentValue }}{{ $impact->unit }}</span>
            @if($impact->delta !== null && $impact->delta != 0)
                <span class="text-sm font-semibold tabular-nums {{ $deltaColor }}">{{ $deltaSign }}{{ $impact->delta }}{{ $deltaUnit }}</span>
            @endif
        </div>
    @else
        <p class="text-sm text-[var(--color-text-tertiary)]">Nog geen meting beschikbaar.</p>
    @endif

    @if(! empty($impact->sparkline))
        <flux:chart :value="$impact->sparkline" class="aspect-[6/1] mt-1">
            <flux:chart.svg>
                <flux:chart.bar field="value" class="text-[var(--color-primary)]" />
                <flux:chart.axis axis="x" field="label">
                    <flux:chart.axis.tick />
                </flux:chart.axis>
                <flux:chart.axis axis="y">
                    <flux:chart.axis.grid class="stroke-[var(--color-border-light)]" />
                    <flux:chart.axis.tick />
                </flux:chart.axis>
            </flux:chart.svg>
        </flux:chart>

        @if($startLabel)
            <p class="mt-1 text-[11px] text-[var(--color-text-tertiary)]">Gestart: {{ $startLabel }}</p>
        @endif
    @endif

    @if($impact->baselineLowData || $impact->currentLowData)
        <p class="text-xs text-[var(--color-text-tertiary)]">Te weinig data voor betrouwbare conclusies.</p>
    @endif
</div>
