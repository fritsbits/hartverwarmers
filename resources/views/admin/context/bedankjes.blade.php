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
