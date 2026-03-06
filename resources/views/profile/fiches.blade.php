<x-profile-layout title="Fiches" description="Bekijk en beheer je fiches en hun statistieken.">

    @if($newCommentsCount > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>
            <span class="text-sm text-blue-800">
                Je hebt <strong>{{ $newCommentsCount }}</strong> nieuwe {{ $newCommentsCount === 1 ? 'reactie' : 'reacties' }} op je fiches.
            </span>
        </div>
    @endif

    @if($fiches->isNotEmpty())
        {{-- Compact stats strip --}}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-[var(--color-text-secondary)] mb-6">
            <span><strong class="text-[var(--color-text-primary)]">{{ $stats['total'] }}</strong> fiches <span class="text-xs">({{ $stats['published'] }} gepubliceerd, {{ $stats['drafts'] }} {{ Str::plural('concept', $stats['drafts']) }})</span></span>
            <span class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                <strong class="text-[var(--color-text-primary)]">{{ $stats['downloads'] }}</strong> downloads
            </span>
            <span class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                <strong class="text-[var(--color-text-primary)]">{{ $stats['kudos'] }}</strong> kudos
            </span>
            <span class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                <strong class="text-[var(--color-text-primary)]">{{ $stats['comments'] }}</strong> reacties
            </span>
        </div>

        {{-- Fiche table --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Fiche</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Stats</flux:table.column>
                <flux:table.column align="end">Datum</flux:table.column>
                <flux:table.column align="end"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($fiches as $fiche)
                    <flux:table.row :key="$fiche->id">
                        <flux:table.cell>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="font-heading font-bold text-[var(--color-text-primary)] hover:text-[var(--color-primary)] transition-colors truncate">
                                        {{ $fiche->title }}
                                    </a>
                                    @if($fiche->has_diamond)
                                        <x-diamond-badge />
                                    @endif
                                </div>
                                @if($fiche->initiative)
                                    <p class="text-xs text-[var(--color-text-secondary)] truncate">{{ $fiche->initiative->title }}</p>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($fiche->published)
                                <flux:badge size="sm" color="green" inset="top bottom">Gepubliceerd</flux:badge>
                            @else
                                <flux:badge size="sm" color="yellow" inset="top bottom">Concept</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center justify-end gap-3 text-xs text-[var(--color-text-secondary)]">
                                <span class="flex items-center gap-1" title="Downloads">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    {{ $fiche->download_count }}
                                </span>
                                <span class="flex items-center gap-1" title="Kudos">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                                    {{ $fiche->kudos_count }}
                                </span>
                                <span class="flex items-center gap-1" title="Reacties">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                                    {{ $fiche->comments_count }}
                                </span>
                                <span class="flex items-center gap-1" title="Favorieten">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                                    {{ $fiche->bookmarks_count }}
                                </span>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap text-xs text-[var(--color-text-secondary)]">
                            {{ $fiche->created_at->format('d-m-Y') }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" href="{{ route('fiches.edit', $fiche) }}" icon="pencil-square" inset="top bottom" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <div class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-[var(--color-border-light)] mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches geschreven.</flux:text>
            <flux:button variant="primary" href="{{ route('fiches.create') }}">
                Schrijf je eerste fiche
            </flux:button>
        </div>
    @endif
</x-profile-layout>
