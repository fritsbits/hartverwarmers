@props(['kr', 'range', 'step' => null])

@php
    $value = $kr->metric_key
        ? app(\App\Services\Okr\MetricRegistry::class)->compute($kr->metric_key, $range)
        : new \App\Services\Okr\MetricValue;
    $progress = $kr->target_value && $value->current !== null
        ? min(100, ($value->current / $kr->target_value) * 100)
        : null;
    $valueColor = \App\Services\Okr\KrHealth::colorClass($value->current, $kr->target_value, $kr->metric_key);
    $barColor = \App\Services\Okr\KrHealth::barClass($value->current, $kr->target_value, $kr->metric_key);
    $description = match ($kr->metric_key) {
        'presentation_score_avg' => 'Gemiddelde presentatiescore van gepubliceerde fiches, op een schaal van 0 tot 100.',
        'onboarding_signup_count' => 'Aantal nieuwe leden dat zich registreerde.',
        'onboarding_verification_rate' => 'Aandeel nieuwe leden dat zijn e-mailadres bevestigde.',
        'onboarding_return_7d_rate' => 'Aandeel nieuwe leden dat binnen 7 dagen terugkeerde naar de site.',
        'onboarding_interaction_30d_rate' => 'Aandeel nieuwe leden dat binnen 30 dagen iets deed: een hartje, een fiche bewaard of een reactie.',
        'onboarding_followup_response_rate' => 'Aandeel downloaders dat reageerde op de automatische follow-up e-mail.',
        'thank_rate' => 'Aandeel van de downloads dat een bedankje terugstuurde (0–100%).',
        'newsletter_activation_rate' => 'Aandeel nieuwsbrief-ontvangers dat daarna opnieuw actief werd op de site.',
        default => null,
    };
@endphp

<div class="relative p-4 {{ $step !== null ? 'pl-14' : '' }}">
    @if($step !== null)
        <span class="absolute left-4 top-4 inline-flex items-center justify-center w-7 h-7 rounded-full bg-[var(--color-bg-cream)] text-[var(--color-text-secondary)] text-xs font-bold tabular-nums">{{ $step }}</span>
    @endif
    <div class="flex items-start justify-between mb-1.5 gap-4">
        <span class="text-sm font-semibold pt-0.5">{{ $kr->label }}</span>
        <div class="text-right whitespace-nowrap shrink-0">
            <div class="flex items-baseline justify-end gap-1.5">
                <span class="text-[11px] uppercase tracking-wide text-[var(--color-text-tertiary)]">Huidig</span>
                <span class="text-base font-bold {{ $valueColor }} tabular-nums">{{ $value->display() }}</span>
                @if($value->delta() !== null && $value->delta() !== 0)
                    <span class="text-xs font-semibold tabular-nums {{ $value->delta() > 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ $value->delta() > 0 ? '+' : '' }}{{ $value->delta() }}{{ $value->unit }}
                    </span>
                @endif
            </div>
            <div class="text-[11px] tabular-nums text-[var(--color-text-tertiary)] mt-0.5">
                @if($kr->target_value !== null)
                    <span class="uppercase tracking-wide">Doel</span> {{ $kr->target_value }}{{ $kr->target_unit }}
                @else
                    Nog geen doel ingesteld
                @endif
            </div>
        </div>
    </div>
    @if($description)
        <p class="text-xs text-[var(--color-text-secondary)] mb-2 -mt-0.5">{{ $description }}</p>
    @endif
    @if($kr->metric_key !== null && $value->current === null)
        <p class="text-xs text-[var(--color-text-tertiary)] mb-1">Nog niet gemeten — er is nog geen meting voor deze metriek.</p>
    @endif
    @if($progress !== null)
        <div class="h-1.5 bg-[var(--color-border-light)] rounded-full mb-1" role="img" aria-label="{{ round($progress) }}% van het doel">
            <div class="h-1.5 {{ $barColor }} rounded-full" style="width: {{ $progress }}%"></div>
        </div>
    @endif
    {{ $slot }}
    @if($value->lowData && $value->current !== null)
        <p class="text-xs text-[var(--color-text-tertiary)] mt-1">Te weinig data voor betrouwbare conclusies.</p>
    @endif
</div>
