<x-layout title="Initiatieven">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <!-- Header -->
        <div class="intro-block py-8">
            <h1 class="text-5xl">Initiatieven</h1>
            <p class="text-2xl text-[var(--color-text-secondary)]">Ontdek inspirerende initiatieven voor ouderen, gefilterd op thema.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <aside class="lg:w-64 shrink-0">
                <div class="sticky top-24 space-y-6">
                    @foreach($filterTags as $type => $tags)
                        <div>
                            <h3 class="font-semibold mb-4">{{ match($type) {
                                'interest' => 'Interesse',
                                'guidance' => 'Begeleiding',
                                default => ucfirst($type),
                            } }}</h3>
                            <ul class="space-y-1">
                                @foreach($tags as $tag)
                                    <li>
                                        <a href="{{ route('initiatives.index', ['tag' => $tag->slug]) }}"
                                           class="block px-3 py-2 rounded-md text-sm {{ $selectedTag === $tag->slug ? 'bg-[var(--color-primary)] text-white' : 'hover:bg-[var(--color-bg-subtle)]' }}">
                                            {{ $tag->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach

                    @if($selectedTag)
                        <div>
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
                            <x-initiative-card :initiative="$initiative" />
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
