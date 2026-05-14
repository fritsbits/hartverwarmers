@php
    $maxCount = collect($newsletterTrend)->max('count') ?: 1;
    $firstLabel = $newsletterTrend[0]['label'] ?? null;
    $lastLabel = collect($newsletterTrend)->last()['label'] ?? null;
    $isAlltime = $range === 'alltime';
    $yearMarkers = [];
    if ($isAlltime) {
        foreach ($newsletterTrend as $i => $b) {
            if (str_ends_with($b['key'], '-01')) {
                $yearMarkers[] = ['year' => substr($b['key'], 0, 4), 'index' => $i];
            }
        }
        $maxMarkers = 8;
        if (count($yearMarkers) > $maxMarkers) {
            $step = (int) ceil(count($yearMarkers) / $maxMarkers);
            $yearMarkers = array_values(array_filter(
                $yearMarkers,
                fn ($_, $i) => $i % $step === 0,
                ARRAY_FILTER_USE_BOTH
            ));
        }
    }
@endphp

{{-- Verstuurd & abonnees --}}
<flux:card class="mb-6">
    <flux:heading size="lg" class="font-heading font-bold mb-1">Verstuurd</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-4">
        Maandelijkse cohort-digest · {{ $rangeLabel }}
    </p>

    @if(empty($newsletterTrend) || collect($newsletterTrend)->sum('count') === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen nieuwsbrieven verstuurd in deze periode.</p>
    @else
        <x-chart-tooltip guide>
            <div class="flex items-end {{ $isAlltime ? 'gap-px' : 'gap-1.5' }} h-16 mb-1">
                @foreach($newsletterTrend as $bucket)
                    @if($bucket['count'] > 0)
                        <div
                            class="flex-1 rounded-t bg-[var(--color-primary)] opacity-80 hover:opacity-100 transition-opacity"
                            style="height: {{ max(4, (int) round($bucket['count'] / $maxCount * 100)) }}%"
                            data-tip-label="{{ $bucket['label'] }}"
                            data-tip-value="{{ $bucket['count'] }} verstuurd"
                        ></div>
                    @else
                        <div class="flex-1 rounded-t bg-[var(--color-border-light)] opacity-40 hover:opacity-70 transition-opacity"
                             style="height: 4px"
                             data-tip-label="{{ $bucket['label'] }}"
                             data-tip-value="0 verstuurd"></div>
                    @endif
                @endforeach
            </div>
        </x-chart-tooltip>
        @if($isAlltime && count($yearMarkers) > 0)
            <div class="relative h-4 mb-4 text-xs text-[var(--color-text-secondary)]">
                @foreach($yearMarkers as $marker)
                    <span class="absolute -translate-x-1/2 tabular-nums"
                          style="left: {{ ($marker['index'] / max(1, count($newsletterTrend) - 1)) * 100 }}%;">
                        {{ $marker['year'] }}
                    </span>
                @endforeach
            </div>
        @elseif($firstLabel && $lastLabel && $firstLabel !== $lastLabel)
            <div class="flex justify-between text-xs text-[var(--color-text-secondary)] mb-4">
                <span>{{ $firstLabel }}</span>
                <span>{{ $lastLabel }}</span>
            </div>
        @else
            <div class="mb-4"></div>
        @endif
    @endif

    <div class="flex gap-6">
        @if($newsletterStats['rangeLabel'] !== 'sinds start')
            <div>
                <div class="text-2xl font-bold text-[var(--color-primary)] tabular-nums">{{ $newsletterStats['currentSent'] }}</div>
                <div class="text-xs text-[var(--color-text-secondary)]">
                    {{ $newsletterStats['rangeLabel'] }}
                    @if($newsletterStats['delta'] !== null)
                        <span class="font-semibold {{ $newsletterStats['delta'] >= 0 ? 'text-green-600' : 'text-red-500' }}">
                            &nbsp;{{ $newsletterStats['delta'] >= 0 ? '+' : '' }}{{ $newsletterStats['delta'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
        <div>
            <div class="text-2xl font-bold tabular-nums">{{ $newsletterStats['totalSubscribers'] }}</div>
            <div class="text-xs text-[var(--color-text-secondary)]">actieve abonnees</div>
        </div>
    </div>
</flux:card>

{{-- Uitschrijfratio per cyclus --}}
<flux:card class="mb-6">
    <flux:heading size="lg" class="font-heading font-bold mb-1">Uitschrijfratio per cyclus</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-5">
        Aandeel ontvangers dat zich uitschrijft binnen 7 dagen na ontvangst · {{ $rangeLabel }}
    </p>

    @php $totalSent = collect($unsubscribeByCycle)->sum('sent'); @endphp

    @if($totalSent === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen nieuwsbrieven verstuurd in deze periode.</p>
    @else
        <x-chart-tooltip>
            <div class="space-y-3">
                @foreach($unsubscribeByCycle as $bucket)
                    <div class="flex items-center gap-4 {{ $bucket['sent'] === 0 ? 'opacity-50' : '' }}"
                         data-tip-label="{{ $bucket['label'] }}"
                         data-tip-value="{{ $bucket['unsubscribed'] }} van {{ $bucket['sent'] }} ({{ $bucket['rate'] }}%)">
                        <span class="text-sm font-medium text-[var(--color-text-primary)] w-24 shrink-0">{{ $bucket['label'] }}</span>
                        <div class="flex-1 h-2 bg-[var(--color-border-light)] rounded-full overflow-hidden">
                            @if($bucket['sent'] > 0)
                                <div class="h-full bg-[var(--color-primary)] rounded-full" style="width: {{ $bucket['rate'] }}%"></div>
                            @endif
                        </div>
                        <span class="text-xs font-semibold tabular-nums text-[var(--color-text-secondary)] w-10 text-right shrink-0">
                            @if($bucket['sent'] === 0)
                                —
                            @else
                                {{ $bucket['rate'] }}%
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </x-chart-tooltip>
        @if(collect($unsubscribeByCycle)->contains(fn ($b) => $b['lowData']))
            <p class="text-xs text-[var(--color-text-tertiary)] mt-3">Buckets met &lt; 5 sends bevatten te weinig data voor betrouwbare conclusies.</p>
        @endif
    @endif
</flux:card>

{{-- Activatie na nieuwsbrief --}}
<flux:card class="mb-6">
    <flux:heading size="lg" class="font-heading font-bold mb-1">Activatie na nieuwsbrief</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-5">
        Aandeel ontvangers met sitebezoek binnen 7 dagen na ontvangst · {{ $activationStats['rangeLabel'] }}
    </p>

    @if($activationStats['sent'] === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen nieuwsbrieven verstuurd in deze periode.</p>
    @else
        <div class="flex items-center gap-4 mb-2">
            <div class="flex-1 h-2 bg-[var(--color-border-light)] rounded-full overflow-hidden"
                 role="progressbar"
                 aria-valuenow="{{ $activationStats['rate'] }}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-label="Activatiegraad na nieuwsbrief">
                <div class="h-full bg-[var(--color-primary)] rounded-full"
                     style="width: {{ $activationStats['rate'] }}%"></div>
            </div>
            <span class="text-2xl font-bold tabular-nums text-[var(--color-primary)] shrink-0">
                {{ $activationStats['rate'] }}%
            </span>
        </div>
        <p class="text-xs text-[var(--color-text-secondary)]">
            {{ $activationStats['activated'] }} van {{ $activationStats['sent'] }} ontvangers kwam terug naar de site
        </p>
        <p class="text-xs text-[var(--color-text-tertiary)] mt-2">
            Meet sitebezoek (al dan niet via klik in de nieuwsbrief) — niet specifiek de klik zelf.
        </p>
        @if($activationStats['lowData'])
            <p class="text-xs text-[var(--color-text-tertiary)] mt-1">Te weinig data voor betrouwbare conclusies.</p>
        @endif
    @endif
</flux:card>

{{-- Aankomende sends --}}
<flux:card>
    <flux:heading size="lg" class="font-heading font-bold mb-1">Aankomende sends</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-5">
        Verwachte verzendingen in de komende {{ $upcomingNewsletterSends['windowDays'] }} dagen
    </p>

    @if($upcomingNewsletterSends['total'] === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Geen verzendingen verwacht in de komende {{ $upcomingNewsletterSends['windowDays'] }} dagen.</p>
    @else
        <div class="flex items-baseline gap-3 mb-5">
            <span class="text-3xl font-bold text-[var(--color-primary)] tabular-nums">{{ $upcomingNewsletterSends['total'] }}</span>
            <span class="text-sm text-[var(--color-text-secondary)]">
                verwachte {{ $upcomingNewsletterSends['total'] === 1 ? 'verzending' : 'verzendingen' }}
            </span>
        </div>

        <div class="divide-y divide-[var(--color-border-light)]">
            @foreach($upcomingNewsletterSends['buckets'] as $bucket)
                <div class="flex items-center gap-3 py-2">
                    <span class="flex-1 text-sm text-[var(--color-text-secondary)]">{{ $bucket['label'] }}</span>
                    <span class="text-sm font-semibold tabular-nums {{ $bucket['count'] > 0 ? 'text-[var(--color-text-primary)]' : 'text-[var(--color-text-tertiary)]' }}">
                        {{ $bucket['count'] }}
                    </span>
                </div>
            @endforeach
        </div>

        <p class="text-xs text-[var(--color-text-tertiary)] mt-3">
            Cyclus 4+ wordt enkel verstuurd aan gebruikers met activiteit in de laatste 6 maanden.
        </p>
    @endif
</flux:card>
