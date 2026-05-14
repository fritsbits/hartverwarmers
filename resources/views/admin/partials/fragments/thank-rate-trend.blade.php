@php
    $firstLabel = $thankTrend[0]['label'] ?? null;
    $lastLabel = collect($thankTrend)->last()['label'] ?? null;
    $isAlltime = $range === 'alltime';
    $yearMarkers = [];
    if ($isAlltime) {
        foreach ($thankTrend as $i => $b) {
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

<p class="text-xs text-[var(--color-text-secondary)] mb-3">Aandeel downloads door leden dat bedankt werd · {{ $rangeLabel }}</p>

@if(empty($thankTrend) || collect($thankTrend)->sum('downloads') === 0)
    <p class="text-sm text-[var(--color-text-secondary)]">Nog geen downloads in deze periode.</p>
@else
    {{-- Sparkline: bar height = thank rate % per bucket --}}
    <x-chart-tooltip guide>
        {{-- Bar height = absolute thank rate percentage (not normalised). Charts may render short on low rates — this is intentional for data honesty. --}}
        {{-- mb-4 (not mb-1) so the tooltip's shadow over short/empty bars doesn't bleed into the axis labels row. --}}
        <div class="flex items-end {{ $isAlltime ? 'gap-px' : 'gap-1.5' }} h-16 mb-4">
            @foreach($thankTrend as $bucket)
                @if($bucket['downloads'] > 0)
                    <div
                        class="flex-1 rounded-t bg-[var(--color-primary)] opacity-80 hover:opacity-100 transition-opacity"
                        style="height: {{ max(4, $bucket['rate']) }}%"
                        data-tip-label="{{ $bucket['label'] }}"
                        data-tip-value="{{ $bucket['thanked'] }} van {{ $bucket['downloads'] }} bedankt ({{ $bucket['rate'] }}%)"
                    ></div>
                @else
                    <div class="flex-1 rounded-t bg-[var(--color-border-light)] opacity-40 hover:opacity-70 transition-opacity"
                         style="height: 4px"
                         data-tip-label="{{ $bucket['label'] }}"
                         data-tip-value="0 downloads"></div>
                @endif
            @endforeach
        </div>
    </x-chart-tooltip>
    @if($isAlltime && count($yearMarkers) > 0)
        <div class="relative h-4 mb-4 text-xs text-[var(--color-text-secondary)]">
            @foreach($yearMarkers as $marker)
                <span class="absolute -translate-x-1/2 tabular-nums"
                      style="left: {{ ($marker['index'] / max(1, count($thankTrend) - 1)) * 100 }}%;">
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
        @if($thankStats['rangeLabel'] !== 'sinds start')
            <div>
                <div class="text-2xl font-bold text-[var(--color-primary)] tabular-nums">{{ $thankStats['currentRate'] }}%</div>
                <div class="text-xs text-[var(--color-text-secondary)]">
                    {{ $thankStats['rangeLabel'] }}
                    @if($thankStats['delta'] !== null)
                        <span class="font-semibold {{ $thankStats['delta'] >= 0 ? 'text-green-600' : 'text-red-500' }}">
                            &nbsp;{{ $thankStats['delta'] >= 0 ? '+' : '' }}{{ $thankStats['delta'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
        <div>
            <div class="text-2xl font-bold tabular-nums">{{ $thankStats['totalThankedAllTime'] }}</div>
            <div class="text-xs text-[var(--color-text-secondary)]">totaal bedankt sinds start</div>
        </div>
    </div>

    @if($thankStats['lowData'])
        <p class="text-xs text-[var(--color-text-tertiary)] mt-2">Te weinig data voor betrouwbare conclusies.</p>
    @endif
@endif
