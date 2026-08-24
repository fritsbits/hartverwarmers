@php
    $bands = collect($diamantDistribution['bands'] ?? []);
    $scored = $diamantDistribution['scored'] ?? 0;
    $strong = $diamantDistribution['strong'] ?? 0;
    $threshold = $diamantDistribution['threshold'] ?? 70;
    $oneStepBelow = $diamantDistribution['oneStepBelow'] ?? 0;
    $projectedShare = $diamantDistribution['projectedShare'] ?? null;
    $currentShare = $scored > 0 ? (int) round($strong / $scored * 100) : null;
    $widest = max(1, $bands->max('count') ?? 1);
@endphp

<flux:card>
    <flux:heading size="lg" class="font-heading font-bold mb-1">Waar de bibliotheek staat</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-5">Verdeling van de diamantscores over alle gepubliceerde fiches</p>

    @if($scored === 0)
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen beoordeelde fiches.</p>
    @else
        <x-chart-tooltip>
            <div class="space-y-2">
                @foreach($bands as $band)
                    @php $width = (int) round($band['count'] / $widest * 100); @endphp
                    <div class="flex items-center gap-4"
                         data-tip-label="Score {{ $band['label'] }}"
                         data-tip-value="{{ $band['count'] }} {{ $band['count'] === 1 ? 'fiche' : 'fiches' }}">
                        <span class="text-xs font-medium text-[var(--color-text-secondary)] w-14 shrink-0 tabular-nums">{{ $band['label'] }}</span>
                        <div class="flex-1 h-2 bg-[var(--color-border-light)] rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $band['strong'] ? 'bg-green-600' : 'bg-[var(--color-primary)]' }}" style="width: {{ $width }}%"></div>
                        </div>
                        <span class="text-xs font-semibold tabular-nums text-[var(--color-text-secondary)] w-10 text-right shrink-0">{{ $band['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-chart-tooltip>

        <div class="mt-5 pt-4 border-t border-[var(--color-border-light)] text-sm text-[var(--color-text-secondary)] space-y-2">
            <p>
                Groen telt mee voor de KR: {{ $strong }} van de {{ $scored }} fiches halen {{ $threshold }}+.
            </p>

            @if($oneStepBelow > 0 && $projectedShare !== null && $currentShare !== null)
                <p>
                    <span class="font-semibold text-[var(--color-text-primary)] tabular-nums">{{ $oneStepBelow }} {{ $oneStepBelow === 1 ? 'fiche zit' : 'fiches zitten' }}</span>
                    in de band eronder ({{ $threshold - 10 }}–{{ $threshold - 1 }}). Halen die de drempel, dan gaat de KR van {{ $currentShare }}% naar {{ $projectedShare }}%.
                </p>
            @endif

            <p class="text-xs text-[var(--color-text-tertiary)]">
                De AI scoort in sprongen, niet vloeiend: bijna alle fiches landen op een handvol waarden. Een fiche verbetert dus een hele band of blijft staan.
            </p>
        </div>
    @endif
</flux:card>
