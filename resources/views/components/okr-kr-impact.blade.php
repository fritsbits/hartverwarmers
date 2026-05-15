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
            @if($impact->delta !== null && $impact->delta != 0)
                <span class="text-2xl font-bold tabular-nums {{ $deltaColor }}">{{ $deltaSign }}{{ $impact->delta }}{{ $deltaUnit }}</span>
                <span class="text-sm text-[var(--color-text-secondary)] tabular-nums">sinds de start</span>
            @else
                <span class="text-sm font-semibold text-[var(--color-text-secondary)]">Nog geen verschil sinds de start</span>
            @endif
        </div>
        <p class="text-xs text-[var(--color-text-secondary)] tabular-nums">
            {{ $impact->baselineValue }}{{ $impact->unit }} &rarr; {{ $impact->currentValue }}{{ $impact->unit }}
        </p>
    @else
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen meting beschikbaar.</p>
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

            <flux:chart.tooltip>
                <flux:chart.tooltip.heading field="label" />
                <flux:chart.tooltip.value field="value" label="{{ $impact->krLabel }}" suffix="{{ $impact->unit }}" />
            </flux:chart.tooltip>
        </flux:chart>

        @if($startLabel)
            <p class="mt-1 text-[11px] text-[var(--color-text-tertiary)]">Gestart: {{ $startLabel }}</p>
        @endif
    @endif

    @if($impact->baselineLowData || $impact->currentLowData)
        <p class="text-xs text-[var(--color-text-tertiary)]">Te weinig data voor betrouwbare conclusies.</p>
    @endif
</div>
