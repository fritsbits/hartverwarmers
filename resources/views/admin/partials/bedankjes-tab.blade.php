@php
    $maxRate = max(1, collect($thankTrend)->max('rate') ?: 0);
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

{{-- Bedankratio --}}
<flux:card class="mb-6">
    <flux:heading size="lg" class="font-heading font-bold mb-1">Bedankratio</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-4">
        Aandeel downloads door leden dat bedankt werd · {{ $rangeLabel }}
    </p>

    @if(empty($thankTrend) || collect($thankTrend)->sum('downloads') === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen downloads in deze periode.</p>
    @else
        {{-- Sparkline: bar height = thank rate % per bucket --}}
        <x-chart-tooltip guide>
            <div class="flex items-end {{ $isAlltime ? 'gap-px' : 'gap-1.5' }} h-16 mb-1">
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
</flux:card>

{{-- Verdeling per vorm --}}
<flux:card>
    <flux:heading size="lg" class="font-heading font-bold mb-1">Hoe bedanken mensen</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-5">Verdeling per vorm · {{ $rangeLabel }}</p>

    @if($thankStats['currentThanked'] === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Nog niemand bedankt in deze periode.</p>
    @else
        @php
            $totalThanked = $thankStats['currentThanked'];
            $splits = [
                ['label' => 'Hartjes', 'count' => $thankStats['kudosThankCount']],
                ['label' => 'Reacties', 'count' => $thankStats['commentThankCount']],
            ];
        @endphp
        <x-chart-tooltip>
            <div class="space-y-3">
                @foreach($splits as $split)
                    @php $rate = $totalThanked > 0 ? (int) round($split['count'] / $totalThanked * 100) : 0; @endphp
                    <div class="flex items-center gap-4"
                         data-tip-label="{{ $split['label'] }}"
                         data-tip-value="{{ $split['count'] }} van {{ $totalThanked }} ({{ $rate }}%)">
                        <span class="text-sm font-medium text-[var(--color-text-primary)] w-24 shrink-0">{{ $split['label'] }}</span>
                        <div class="flex-1 h-2 bg-[var(--color-border-light)] rounded-full overflow-hidden">
                            <div class="h-full bg-[var(--color-primary)] rounded-full" style="width: {{ $rate }}%"></div>
                        </div>
                        <span class="text-xs font-semibold tabular-nums text-[var(--color-text-secondary)] w-10 text-right shrink-0">{{ $rate }}%</span>
                    </div>
                @endforeach
            </div>
        </x-chart-tooltip>
    @endif
</flux:card>
