@php
    $trimmedTrend = collect($signupTrend)
        ->skipWhile(fn ($b) => $b['count'] === 0)
        ->values()
        ->all();
    $maxCount = collect($trimmedTrend)->max('count') ?: 1;
    $firstLabel = $trimmedTrend[0]['label'] ?? null;
    $lastLabel = end($trimmedTrend)['label'] ?? null;
    $isAlltime = $range === 'alltime';
    $yearMarkers = [];
    if ($isAlltime) {
        foreach ($trimmedTrend as $i => $b) {
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

@if(! empty($trimmedTrend))
    {{-- Sparkline with shared tooltip --}}
    <x-chart-tooltip guide>
        <div class="flex items-end {{ $isAlltime ? 'gap-px' : 'gap-1.5' }} h-16 mb-1 mt-2">
            @foreach($trimmedTrend as $bucket)
                @if($bucket['count'] > 0)
                    <div
                        class="flex-1 rounded-t bg-[var(--color-primary)] opacity-80 hover:opacity-100 transition-opacity"
                        style="height: {{ max(4, (int) round($bucket['count'] / $maxCount * 100)) }}%"
                        data-tip-label="{{ $bucket['label'] }}"
                        data-tip-value="{{ $bucket['count'] }} {{ $bucket['count'] === 1 ? 'aanmelding' : 'aanmeldingen' }}"
                    ></div>
                @else
                    <div class="flex-1 rounded-t bg-[var(--color-border-light)] opacity-40 hover:opacity-70 transition-opacity"
                         style="height: 4px"
                         data-tip-label="{{ $bucket['label'] }}"
                         data-tip-value="0 aanmeldingen"></div>
                @endif
            @endforeach
        </div>
    </x-chart-tooltip>
    @if($isAlltime && count($yearMarkers) > 0)
        <div class="relative h-4 mb-2 text-xs text-[var(--color-text-secondary)]">
            @foreach($yearMarkers as $marker)
                <span class="absolute -translate-x-1/2 tabular-nums"
                      style="left: {{ ($marker['index'] / max(1, count($trimmedTrend) - 1)) * 100 }}%;">
                    {{ $marker['year'] }}
                </span>
            @endforeach
        </div>
    @elseif($firstLabel && $lastLabel && $firstLabel !== $lastLabel)
        <div class="flex justify-between text-xs text-[var(--color-text-secondary)] mb-2">
            <span>{{ $firstLabel }}</span>
            <span>{{ $lastLabel }}</span>
        </div>
    @else
        <div class="mb-2"></div>
    @endif

    {{-- Stats row --}}
    <div class="flex gap-6">
        @if($signupStats['rangeLabel'] !== 'sinds start')
            <div>
                <div class="text-2xl font-bold text-[var(--color-primary)] tabular-nums">{{ $signupStats['currentCount'] }}</div>
                <div class="text-xs text-[var(--color-text-secondary)]">
                    {{ $signupStats['rangeLabel'] }}
                    @if($signupStats['delta'] !== null)
                        <span class="font-semibold {{ $signupStats['delta'] >= 0 ? 'text-green-600' : 'text-red-500' }}">
                            &nbsp;{{ $signupStats['delta'] >= 0 ? '+' : '' }}{{ $signupStats['delta'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
        <div>
            <div class="text-2xl font-bold tabular-nums">{{ $signupStats['totalMembers'] }}</div>
            <div class="text-xs text-[var(--color-text-secondary)]">totaal leden</div>
        </div>
    </div>
@endif
