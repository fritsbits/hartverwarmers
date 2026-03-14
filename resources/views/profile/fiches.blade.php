<x-sidebar-layout title="Fiches" section-label="Profiel" description="Bekijk en beheer je fiches en hun statistieken.">

    @if($newCommentsCount > 0)
        <div class="bg-[var(--color-bg-accent-light)] border border-[var(--color-border-light)] rounded-lg p-4 mb-6 flex items-center gap-3">
            <flux:icon name="chat-bubble-oval-left-ellipsis" variant="mini" class="size-5 text-[var(--color-primary)] shrink-0" />
            <span class="text-sm text-[var(--color-text-primary)]">
                Je hebt <strong>{{ $newCommentsCount }}</strong> nieuwe {{ $newCommentsCount === 1 ? 'reactie' : 'reacties' }} op je fiches.
            </span>
        </div>
    @endif

    @if($fiches->isNotEmpty())
        {{-- Stats strip --}}
        <div class="text-sm text-[var(--color-text-secondary)] mb-6">
            <p class="mb-1">
                <strong class="text-[var(--color-text-primary)]">{{ $stats['total'] }}</strong> fiches
                <span class="text-xs">({{ $stats['published'] }} gepubliceerd, {{ $stats['drafts'] }} {{ Str::plural('concept', $stats['drafts']) }})</span>
            </p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <span class="flex items-center gap-1">
                    <flux:icon name="arrow-down-tray" variant="mini" class="size-4" />
                    <strong class="text-[var(--color-text-primary)]">{{ $stats['downloads'] }}</strong> downloads
                </span>
                <span class="flex items-center gap-1">
                    <flux:icon name="heart" variant="mini" class="size-4" />
                    <strong class="text-[var(--color-text-primary)]">{{ $stats['kudos'] }}</strong> kudos
                </span>
                <span class="flex items-center gap-1">
                    <flux:icon name="chat-bubble-oval-left-ellipsis" variant="mini" class="size-4" />
                    <strong class="text-[var(--color-text-primary)]">{{ $stats['comments'] }}</strong> reacties
                </span>
            </div>
        </div>

        {{-- Fiche list --}}
        <div class="space-y-2">
            @foreach($fiches as $fiche)
                @php
                    $viewed = isset($ficheInteractions[$fiche->id]) && in_array('view', $ficheInteractions[$fiche->id]);
                    $downloaded = isset($ficheInteractions[$fiche->id]) && in_array('download', $ficheInteractions[$fiche->id]);
                @endphp
                <div class="fiche-list-item group {{ $viewed ? 'fiche-list-item-viewed' : '' }}">
                    <div class="fiche-list-icon">
                        <flux:icon :name="$fiche->published ? 'document-text' : 'pencil-square'" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="font-heading font-bold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors truncate">{{ $fiche->title }}</a>
                            @if($fiche->has_diamond)
                                <x-diamond-badge class="shrink-0" />
                            @endif
                            @if(!$fiche->published)
                                <flux:badge size="sm" color="yellow" inset="top bottom" class="shrink-0">Concept</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 text-xs text-[var(--color-text-secondary)]">
                            @if($fiche->initiative)
                                <span class="truncate">{{ $fiche->initiative->title }}</span>
                                <span class="text-[var(--color-border-light)]">&middot;</span>
                            @endif
                            <span class="whitespace-nowrap">{{ $fiche->created_at->format('d-m-Y') }}</span>
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-3 text-xs text-[var(--color-text-secondary)] shrink-0">
                        <span class="flex items-center gap-1" title="Downloads">
                            <flux:icon name="arrow-down-tray" variant="micro" class="size-3.5" />
                            {{ $fiche->download_count }}
                        </span>
                        <span class="flex items-center gap-1" title="Kudos">
                            <flux:icon name="heart" variant="micro" class="size-3.5" />
                            {{ $fiche->kudos_count }}
                        </span>
                        <span class="flex items-center gap-1" title="Reacties">
                            <flux:icon name="chat-bubble-oval-left-ellipsis" variant="micro" class="size-3.5" />
                            {{ $fiche->comments_count }}
                        </span>
                    </div>
                    @if($downloaded)
                        <span class="fiche-list-downloaded" title="Gedownload">
                            <flux:icon name="arrow-down-tray" class="size-3.5" />
                        </span>
                    @endif
                    <flux:button variant="ghost" href="{{ route('fiches.edit', $fiche) }}" icon="pencil-square" class="shrink-0" />
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <flux:icon name="document-text" class="size-16 mx-auto text-[var(--color-border-light)] mb-4" />
            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches geschreven.</flux:text>
            <flux:button variant="primary" href="{{ route('fiches.create') }}">
                Schrijf je eerste fiche
            </flux:button>
        </div>
    @endif
</x-sidebar-layout>
