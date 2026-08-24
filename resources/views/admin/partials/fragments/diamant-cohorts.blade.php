@php
    $cohorts = collect($diamantCohorts)->values();
    $threshold = $diamantDistribution['threshold'] ?? 70;

    $points = $cohorts->map(fn ($cohort) => [
        'label' => $cohort['label'],
        'score' => $cohort['thin'] ? null : $cohort['avg'],
        'thin' => $cohort['thin'] ? $cohort['avg'] : null,
        'drempel' => $threshold,
    ])->all();

    $filled = $cohorts->filter(fn ($cohort) => $cohort['avg'] !== null)->values();
    $newest = $filled->last();
    $hasThin = $filled->contains(fn ($cohort) => $cohort['thin']);
@endphp

@if($filled->isEmpty())
    <p class="text-sm text-[var(--color-text-secondary)] mt-3">Geen fiches gemaakt in deze periode.</p>
@else
    <flux:chart :value="$points" class="aspect-[6/1] mt-3">
        <flux:chart.svg>
            <flux:chart.bar field="score" class="text-[var(--color-primary)]" />
            <flux:chart.bar field="thin" class="text-[var(--color-primary)]/30" />
            <flux:chart.line field="drempel" class="text-[var(--color-text-secondary)] [stroke-dasharray:6_5]" />
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
            <flux:chart.tooltip.value field="score" label="Gemiddelde score" />
            <flux:chart.tooltip.value field="thin" label="Gemiddelde score (weinig fiches)" />
        </flux:chart.tooltip>
    </flux:chart>

    <div class="text-xs text-[var(--color-text-tertiary)] mt-3 space-y-1 tabular-nums">
        <p>
            <span class="font-semibold text-[var(--color-text-secondary)]">{{ $diamantDistribution['strong'] ?? 0 }}</span>
            van de {{ $diamantDistribution['scored'] ?? 0 }} beoordeelde fiches halen {{ $threshold }}+ op de diamantscore.
        </p>

        @if($newest)
            <p>Laatste lichting · {{ $newest['label'] }}: {{ $newest['fiches'] }} {{ $newest['fiches'] === 1 ? 'fiche' : 'fiches' }}, gemiddeld {{ $newest['avg'] }}.</p>
        @endif

        <p>
            Elke staaf is de lichting fiches die in die maand gemaakt is, met hun huidige score — zo zie je of nieuw werk beter wordt. De stippellijn is de drempel van {{ $threshold }}.
            @if($hasThin)
                Lichte staven zijn maanden met minder dan drie fiches.
            @endif
        </p>
    </div>
@endif
