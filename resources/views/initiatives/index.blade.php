<x-layout title="Initiatieven" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Initiatieven</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Initiatieven</span>
            <h1 class="text-5xl mt-1">Ontdek inspirerende activiteiten</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4">Laat je inspireren door initiatieven van activiteitenbegeleiders, gefilterd op thema.</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Content --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            @if($search)
                <div class="flex items-center gap-2 mb-6">
                    <span class="text-sm text-[var(--color-text-secondary)]">Zoekresultaten voor "<strong class="text-[var(--color-text-primary)]">{{ $search }}</strong>"</span>
                    <flux:button variant="ghost" href="{{ route('initiatives.index', $selectedTag ? ['tag' => $selectedTag] : []) }}" size="sm">
                        Wis zoekopdracht
                    </flux:button>
                </div>
            @endif

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Filters Sidebar -->
                <aside class="lg:w-48 shrink-0">
                    <div class="sticky top-24">
                        @if($filterTags->has('theme'))
                            <h3 class="font-semibold text-sm mb-2">Thema</h3>
                            <ul>
                                @foreach($filterTags['theme'] as $tag)
                                    <li>
                                        <a href="{{ route('initiatives.index', ['tag' => $tag->slug]) }}"
                                           class="block py-1 text-sm {{ $selectedTag === $tag->slug ? 'text-[var(--color-primary)] font-semibold' : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]' }}">
                                            {{ $tag->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($selectedTag)
                            <div class="mt-3">
                                <flux:button variant="ghost" href="{{ route('initiatives.index') }}" size="sm">
                                    Wis filter
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </aside>

                <!-- Initiatives Grid -->
                <div class="flex-1">
                    @if($initiatives->isEmpty())
                        <div class="text-center py-12">
                            <p class="text-[var(--color-text-secondary)]">Geen initiatieven gevonden.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($initiatives as $initiative)
                                <x-initiative-card :initiative="$initiative" :show-fiche-count="true" />
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {{ $initiatives->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layout>
