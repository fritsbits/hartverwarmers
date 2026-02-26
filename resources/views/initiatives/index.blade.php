<x-layout title="Initiatieven">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <!-- Header -->
        <div class="intro-block py-8">
            <h1 class="text-5xl">Initiatieven</h1>
            <p class="text-2xl text-[var(--color-text-secondary)]">Ontdek inspirerende initiatieven voor ouderen, gefilterd op thema.</p>
        </div>

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
                        {{ $initiatives->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout>
