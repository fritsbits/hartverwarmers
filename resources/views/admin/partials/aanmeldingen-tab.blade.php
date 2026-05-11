@php
    $maxCount = collect($signupTrend)->max('count') ?: 1;
    $firstLabel = $signupTrend[0]['label'] ?? null;
    $lastLabel = collect($signupTrend)->last()['label'] ?? null;
    $isAlltime = $range === 'alltime';
    $yearMarkers = [];
    if ($isAlltime) {
        $total = count($signupTrend);
        foreach ($signupTrend as $i => $b) {
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

{{-- Signup trend --}}
<flux:card class="mb-6">
    <flux:heading size="lg" class="font-heading font-bold mb-1">Aanmeldingen</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-4">
        Bezoekers maken een account · {{ $rangeLabel }}
    </p>

    @if(empty($signupTrend))
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen aanmeldingen in deze periode.</p>
    @else
        {{-- Sparkline with shared tooltip --}}
        <x-chart-tooltip guide>
            <div class="flex items-end {{ $isAlltime ? 'gap-px' : 'gap-1.5' }} h-16 mb-1">
                @foreach($signupTrend as $bucket)
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
            <div class="relative h-4 mb-4 text-xs text-[var(--color-text-secondary)]">
                @foreach($yearMarkers as $marker)
                    <span class="absolute -translate-x-1/2 tabular-nums"
                          style="left: {{ ($marker['index'] / max(1, count($signupTrend) - 1)) * 100 }}%;">
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
</flux:card>

{{-- Verification rate --}}
<flux:card>
    <flux:heading size="lg" class="font-heading font-bold mb-1">E-mailverificatie</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-5">
        Aandeel geverifieerde accounts · {{ $rangeLabel }}
    </p>

    @if($signupStats['cohortCount'] === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen aanmeldingen om te verifiëren.</p>
    @else
        <div class="flex items-center gap-4 mb-2">
            <div class="flex-1 h-2 bg-[var(--color-border-light)] rounded-full overflow-hidden"
                 role="progressbar"
                 aria-valuenow="{{ $signupStats['verificationRate'] }}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-label="E-mailverificatiegraad">
                <div class="h-full bg-[var(--color-primary)] rounded-full"
                     style="width: {{ $signupStats['verificationRate'] }}%"></div>
            </div>
            <span class="text-2xl font-bold tabular-nums text-[var(--color-primary)] shrink-0">
                {{ $signupStats['verificationRate'] }}%
            </span>
        </div>
        <p class="text-xs text-[var(--color-text-secondary)]">
            {{ $signupStats['verifiedCount'] }} van {{ $signupStats['cohortCount'] }} nieuwe gebruikers verifieerden hun e-mail
        </p>
        @if($signupStats['verificationLowData'])
            <p class="text-xs text-[var(--color-text-tertiary)] mt-1">Te weinig data voor betrouwbare conclusies.</p>
        @endif
    @endif
</flux:card>
