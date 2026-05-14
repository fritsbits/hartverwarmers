@php
    $trimmedTrend = collect($thankTrend)
        ->skipWhile(fn ($b) => $b['downloads'] === 0)
        ->values()
        ->all();
@endphp

<p class="text-xs text-[var(--color-text-tertiary)] mt-3">Aandeel downloads door leden dat bedankt werd · {{ $rangeLabel }}</p>

@if(empty($trimmedTrend) || collect($trimmedTrend)->sum('downloads') === 0)
    <p class="text-sm text-[var(--color-text-secondary)] mt-3">Nog geen downloads in deze periode.</p>
@else
    <flux:chart :value="$trimmedTrend" class="aspect-[6/1] mt-2">
        <flux:chart.svg>
            <flux:chart.bar field="rate" class="text-[var(--color-primary)]" />
            <flux:chart.axis axis="x" field="label">
                <flux:chart.axis.tick />
            </flux:chart.axis>
            <flux:chart.axis axis="y" :tick-values="[0, 25, 50, 75, 100]">
                <flux:chart.axis.grid class="stroke-[var(--color-border-light)]" />
                <flux:chart.axis.tick format="0%" />
            </flux:chart.axis>
        </flux:chart.svg>
    </flux:chart>

    @if($thankStats['totalThankedAllTime'] > 0)
        <p class="text-xs text-[var(--color-text-tertiary)] mt-3 tabular-nums">
            {{ $thankStats['totalThankedAllTime'] }} totaal bedankt sinds start
        </p>
    @endif

    @if($thankStats['lowData'])
        <p class="text-xs text-[var(--color-text-tertiary)] mt-1">Te weinig data voor betrouwbare conclusies.</p>
    @endif
@endif
