<x-sidebar-layout title="Favorieten" section-label="Profiel" description="Bekijk je opgeslagen fiches.">
    @if($fiches->isEmpty())
        <div class="text-center py-12">
            <flux:icon name="bookmark" class="size-16 mx-auto text-[var(--color-border-light)] mb-4" />
            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches als favoriet gemarkeerd.</flux:text>
            <flux:button variant="primary" href="{{ route('initiatives.index') }}">
                Ontdek initiatieven
            </flux:button>
        </div>
    @else
        <p class="text-sm text-[var(--color-text-secondary)] mb-6">
            <strong class="text-[var(--color-text-primary)]">{{ $fiches->count() }}</strong> {{ $fiches->count() === 1 ? 'favoriet' : 'favorieten' }}
        </p>

        <div class="space-y-2">
            @foreach($fiches as $fiche)
                <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="fiche-list-item group">
                    <div class="fiche-list-icon">
                        <flux:icon name="bookmark" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start gap-2">
                            <span class="font-heading font-bold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors line-clamp-2">{{ $fiche->title }}</span>
                            @if($fiche->has_diamond)
                                <x-diamond-badge class="shrink-0 mt-0.5" />
                            @endif
                        </div>
                        <div class="flex items-center gap-2 text-xs text-[var(--color-text-secondary)] truncate">
                            @if($fiche->initiative)
                                <span>{{ $fiche->initiative->title }}</span>
                                <span class="text-[var(--color-border-light)]">&middot;</span>
                            @endif
                            @if($fiche->user)
                                <span>{{ $fiche->user->first_name }} {{ $fiche->user->last_name }}</span>
                            @endif
                        </div>
                    </div>
                    <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-[var(--color-border-hover)] group-hover:text-[var(--color-primary)] transition-colors" />
                </a>
            @endforeach
        </div>
    @endif
</x-sidebar-layout>
