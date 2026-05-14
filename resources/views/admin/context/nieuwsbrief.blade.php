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
