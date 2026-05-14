@php
    $allSlots = collect($weeklyTrend);
    $trimmedTrend = $allSlots->skipWhile(fn($w) => $w['avg_score'] === null)->values();
    $firstLabel = $trimmedTrend->first()['week_label'] ?? null;
    $lastLabel = $trimmedTrend->last()['week_label'] ?? null;
    $scored = $trimmedTrend->filter(fn($w) => $w['avg_score'] !== null);
    $currentScore = $scored->last()['avg_score'] ?? null;
    $isAlltime = $range === 'alltime';
    $yearMarkers = [];
    if ($isAlltime) {
        $total = $trimmedTrend->count();
        foreach ($trimmedTrend as $i => $slot) {
            if (is_string($slot['week_key']) && str_ends_with($slot['week_key'], '-01')) {
                $yearMarkers[] = ['year' => substr($slot['week_key'], 0, 4), 'index' => $i];
            }
        }
        $maxMarkers = 8;
        if (count($yearMarkers) > $maxMarkers) {
            $step = (int) ceil(count($yearMarkers) / $maxMarkers);
            $yearMarkers = array_values(array_filter(
                $yearMarkers,
                fn($_, $i) => $i % $step === 0,
                ARRAY_FILTER_USE_BOTH
            ));
        }
    }
@endphp

@if($scored->isEmpty())
    <p class="text-sm text-[var(--color-text-secondary)]">Nog geen beoordeelde fiches.</p>
@else
    {{-- Sparkline — only slots from first data point onwards --}}
    <x-chart-tooltip guide>
        <div class="flex items-end {{ $isAlltime ? 'gap-px' : 'gap-1.5' }} h-16 mb-1">
            @foreach($trimmedTrend as $week)
                @if($week['avg_score'] !== null)
                    <div
                        class="flex-1 rounded-t bg-[var(--color-primary)] opacity-80 hover:opacity-100 transition-opacity"
                        style="height: {{ $week['avg_score'] }}%"
                        data-tip-label="{{ $week['week_label'] }}"
                        data-tip-value="score {{ $week['avg_score'] }}"
                    ></div>
                @else
                    <div class="flex-1 rounded-t bg-[var(--color-border-light)] opacity-40 hover:opacity-70 transition-opacity"
                         style="height: 4px"
                         data-tip-label="{{ $week['week_label'] }}"
                         data-tip-value="geen data"></div>
                @endif
            @endforeach
        </div>
    </x-chart-tooltip>
    @if($isAlltime && count($yearMarkers) > 0)
        <div class="relative h-4 mb-4 text-xs text-[var(--color-text-secondary)]">
            @foreach($yearMarkers as $marker)
                <span class="absolute -translate-x-1/2 tabular-nums"
                      style="left: {{ ($marker['index'] / max(1, $trimmedTrend->count() - 1)) * 100 }}%;">
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

    {{-- Stats row --}}
    <div class="flex gap-6">
        @if($currentScore !== null)
            <div>
                <div class="text-2xl font-bold text-[var(--color-primary)] tabular-nums">{{ $currentScore }}</div>
                <div class="text-xs text-[var(--color-text-secondary)]">
                    {{ $range === 'week' ? 'meest recent' : ($range === 'alltime' ? 'huidige maand' : 'huidige week') }}
                    @if($trendDelta !== null)
                        <span class="font-semibold {{ $trendDelta >= 0 ? 'text-green-600' : 'text-red-500' }}">
                            &nbsp;{{ $trendDelta >= 0 ? '+' : '' }}{{ $trendDelta }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
        @if($globalAvg !== null)
            <div>
                <div class="text-2xl font-bold tabular-nums">{{ $globalAvg }}</div>
                <div class="text-xs text-[var(--color-text-secondary)]">globaal gemiddelde</div>
            </div>
        @endif
    </div>
@endif
