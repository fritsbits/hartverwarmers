@props(['impact'])

@php
    /** @var \App\Services\Okr\InitiativeKrImpact $impact */
    $hasNumbers = $impact->baselineValue !== null && $impact->currentValue !== null;
    $unit = $impact->unit;
    $isPercent = $unit === '%';

    $delta = $impact->delta;
    $hasChange = $delta !== null && $delta != 0;
    $up = $delta !== null && $delta > 0;

    $changeColor = ! $hasChange
        ? 'text-[var(--color-text-secondary)]'
        : ($up ? 'text-green-700' : 'text-red-600');

    // A change in a percentage is expressed in "procentpunt", not "%": going
    // from 0% to 8% is +8 procentpunt. Counts just go up or down by a number.
    $changeNoun = $isPercent ? ' procentpunt' : ($unit !== '' ? $unit : '');
    $changeWord = $isPercent ? ($up ? 'hoger' : 'lager') : ($up ? 'meer' : 'minder');

    // The sparkline holds the weekly metric value from 4 weeks before launch
    // through now; $markerIndex is the launch week. Split it into two series so
    // the "before" context (grey) reads differently from the period the
    // initiative has been live (orange), with the colour change marking launch.
    $spark = $impact->sparkline;
    $markerIndex = $impact->markerIndex;
    $hasBaselineLine = $impact->baselineValue !== null && (float) $impact->baselineValue > 0;
    $points = collect($spark)->map(fn ($p, $i) => [
        'label' => $p['label'],
        'before' => $i < $markerIndex ? $p['value'] : null,
        'after' => $i >= $markerIndex ? $p['value'] : null,
        'baseline' => $hasBaselineLine ? $impact->baselineValue : null,
    ])->values()->all();
    // A long history turns per-week x-labels into an unreadable diagonal smear;
    // past ~14 weeks we drop them and state the span in the caption instead.
    $dense = count($spark) > 14;
@endphp

<div class="space-y-3 rounded-lg border border-[var(--color-border-light)] bg-white p-4">
    <p class="text-sm font-semibold text-[var(--color-text-primary)]">{{ $impact->krLabel }}</p>

    @if($hasNumbers)
        <p class="text-sm text-[var(--color-text-secondary)]">
            Van <span class="font-semibold tabular-nums text-[var(--color-text-primary)]">{{ $impact->baselineValue }}{{ $unit }}</span> bij de start
            naar <span class="font-semibold tabular-nums text-[var(--color-text-primary)]">{{ $impact->currentValue }}{{ $unit }}</span> nu
            @if($hasChange)
                &mdash; <span class="font-bold tabular-nums {{ $changeColor }}">{!! $up ? '&uarr;' : '&darr;' !!} {{ abs($delta) }}{{ $changeNoun }} {{ $changeWord }}</span>.
            @else
                &mdash; nog geen verschil sinds de start.
            @endif
        </p>
    @else
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen meting beschikbaar.</p>
    @endif

    @if(! empty($points))
        <div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-[var(--color-text-secondary)]">
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-sm bg-[var(--color-text-tertiary)]"></span>
                    Vóór de start
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-sm bg-[var(--color-primary)]"></span>
                    Sinds de start
                </span>
                @if($hasBaselineLine)
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-block w-3 border-t-2 border-dashed border-[var(--color-text-secondary)]"></span>
                        Waarde bij de start
                    </span>
                @endif
            </div>

            <flux:chart :value="$points" class="aspect-[3/1] mt-2">
                <flux:chart.svg>
                    <flux:chart.bar field="before" class="text-[var(--color-text-tertiary)]" />
                    <flux:chart.bar field="after" class="text-[var(--color-primary)]" />
                    @if($hasBaselineLine)
                        <flux:chart.line field="baseline" class="text-[var(--color-text-secondary)] [stroke-dasharray:6_5]" />
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
                    <flux:chart.tooltip.value field="before" label="Vóór de start" suffix="{{ $unit }}" />
                    <flux:chart.tooltip.value field="after" label="Sinds de start" suffix="{{ $unit }}" />
                </flux:chart.tooltip>
            </flux:chart>

            <p class="mt-2 text-[11px] text-[var(--color-text-tertiary)]">
                Wekelijkse waarde van deze metriek. De grijze balken tonen de weken vóór het initiatief live ging, ter vergelijking.
                @if($dense)
                    Periode: {{ $spark[0]['label'] ?? '' }} &ndash; {{ $spark[count($spark) - 1]['label'] ?? '' }} (beweeg over een balk voor de week).
                @endif
            </p>
        </div>
    @endif

    @if($impact->baselineLowData || $impact->currentLowData)
        <p class="text-xs text-[var(--color-text-tertiary)]">Te weinig data voor betrouwbare conclusies.</p>
    @endif
</div>
