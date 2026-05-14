<flux:card>
    <flux:heading size="lg" class="font-heading font-bold mb-1">Laatste 5 fiches</flux:heading>
    <p class="text-sm text-[var(--color-text-secondary)] mb-4">Recentst toegevoegde fiches</p>

    @if($lastFiches->isEmpty())
        <p class="text-sm text-[var(--color-text-secondary)]">Nog geen fiches.</p>
    @else
        <div class="divide-y divide-[var(--color-border-light)]">
            @foreach($lastFiches as $fiche)
                @php
                    $score = $fiche->presentation_score;
                    $scoreColor = $score !== null
                        ? ($score >= 70 ? 'text-green-700' : ($score >= 40 ? 'text-amber-600' : 'text-red-600'))
                        : '';
                @endphp
                <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}"
                   class="flex items-center gap-3 py-2 -mx-1 px-1 rounded hover:bg-[var(--color-surface)] transition-colors group">
                    <span class="flex-1 text-sm text-[var(--color-text-secondary)] truncate group-hover:text-[var(--color-text-primary)]">{{ $fiche->title }}</span>
                    @if($score !== null)
                        <span class="text-sm font-bold {{ $scoreColor }} shrink-0 tabular-nums">{{ $score }}</span>
                    @else
                        <span class="text-sm text-[var(--color-text-secondary)] opacity-40 shrink-0">—</span>
                    @endif
                    <span class="text-xs text-[var(--color-text-secondary)] shrink-0 w-28 text-right">{{ $fiche->created_at->diffForHumans() }}</span>
                </a>
            @endforeach
        </div>
        @if($lastFiveAvg !== null)
            <p class="text-xs text-[var(--color-text-secondary)] mt-3">
                Gem. score laatste 5: <strong>{{ $lastFiveAvg }}</strong>
                @if($globalAvg !== null)
                    &nbsp;·&nbsp; Globaal: <strong>{{ $globalAvg }}</strong>
                @endif
            </p>
        @endif
    @endif
</flux:card>
