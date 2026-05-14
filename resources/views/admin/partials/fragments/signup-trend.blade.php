@php
    $trimmedTrend = collect($signupTrend)
        ->skipWhile(fn ($b) => $b['count'] === 0)
        ->values()
        ->all();
@endphp

@if(! empty($trimmedTrend))
    <flux:chart :value="$trimmedTrend" class="aspect-[6/1] mt-3">
        <flux:chart.svg>
            <flux:chart.bar field="count" class="text-[var(--color-primary)]" />
            <flux:chart.axis axis="x" field="label">
                <flux:chart.axis.tick />
            </flux:chart.axis>
            <flux:chart.axis axis="y">
                <flux:chart.axis.grid class="stroke-[var(--color-border-light)]" />
                <flux:chart.axis.tick />
            </flux:chart.axis>
        </flux:chart.svg>
    </flux:chart>

    @if($signupStats['totalMembers'] > 0)
        <p class="text-xs text-[var(--color-text-tertiary)] mt-3 tabular-nums">
            {{ $signupStats['totalMembers'] }} totaal leden
        </p>
    @endif
@endif
