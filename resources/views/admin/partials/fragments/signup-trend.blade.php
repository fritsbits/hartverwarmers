@php
    $rows = collect($signupTrend)
        ->skipWhile(fn ($b) => $b['count'] === 0)
        ->values();

    $lastIndex = $rows->count() - 1;
    $hasTarget = ($kr->target_value ?? null) !== null;

    $periodWord = match ($range ?? 'month') {
        'quarter' => 'week',
        'alltime' => 'maand',
        default => 'dag',
    };

    $points = $rows->map(fn ($b, $i) => [
        'label' => $b['label'],
        'value' => $i < $lastIndex ? $b['count'] : null,
        'pending' => $i === $lastIndex ? $b['count'] : null,
        'target' => $hasTarget ? $kr->target_value : null,
    ])->all();

    // Flux renders one x-tick per category, so a long history (e.g. ~93
    // months) becomes an unreadable diagonal smear. Past ~16 buckets we drop
    // the per-bucket axis labels and state the span in words instead; the
    // tooltip still names each bucket on hover.
    $dense = $rows->count() > 16;
@endphp

@if($rows->isNotEmpty())
    <flux:chart :value="$points" class="aspect-[6/1] mt-3">
        <flux:chart.svg>
            <flux:chart.bar field="value" class="text-[var(--color-primary)]" />
            <flux:chart.bar field="pending" class="text-[var(--color-primary)]/30" />
            @if($hasTarget)
                <flux:chart.line field="target" class="text-[var(--color-text-secondary)] [stroke-dasharray:6_5]" />
            @endif
            @unless($dense)
                <flux:chart.axis axis="x" field="label">
                    <flux:chart.axis.tick />
                </flux:chart.axis>
            @endunless
            <flux:chart.axis axis="y">
                <flux:chart.axis.grid class="stroke-[var(--color-border-light)]" />
                <flux:chart.axis.tick />
            </flux:chart.axis>
        </flux:chart.svg>

        <flux:chart.tooltip>
            <flux:chart.tooltip.heading field="label" />
            <flux:chart.tooltip.value field="value" label="Aanmeldingen" />
            <flux:chart.tooltip.value field="pending" label="Aanmeldingen (lopend)" />
        </flux:chart.tooltip>
    </flux:chart>

    <p class="text-xs text-[var(--color-text-tertiary)] mt-2">
        Laatste balk = lopende {{ $periodWord }} (nog niet volledig).
        @if($dense)
            Periode: {{ $rows->first()['label'] }} &ndash; {{ $rows->last()['label'] }} (beweeg over een balk voor de {{ $periodWord }}).
        @endif
        @if($hasTarget)
            Stippellijn = doel ({{ $kr->target_value }}{{ $kr->target_unit }}).
        @endif
    </p>

    @if($signupStats['totalMembers'] > 0)
        <p class="text-xs text-[var(--color-text-tertiary)] mt-1 tabular-nums">
            Sinds de start: {{ $signupStats['totalMembers'] }} leden in totaal.
        </p>
    @endif
@endif
