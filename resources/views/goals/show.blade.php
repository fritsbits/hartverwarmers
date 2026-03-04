<x-layout :title="$facet['keyword'] . ' — DIAMANT-kompas'" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-12">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('goals.index') }}">DIAMANT-kompas</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $facet['keyword'] }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-start">
                {{-- Left column --}}
                <div class="lg:col-span-3">
                    <span class="section-label section-label-hero">Doelstelling</span>
                    <h1 class="text-5xl mt-1 mb-6">{{ $facet['keyword'] }}</h1>

                    <div class="text-xl leading-relaxed text-[var(--color-text-secondary)]">
                        <p>{{ $facet['description'] }}</p>
                    </div>
                </div>

                {{-- Right column: Quote paper --}}
                <div class="lg:col-span-2 pt-2">
                    <div class="relative">
                        <div class="quote-paper-mark">&ldquo;</div>
                        <div class="quote-paper">
                            <p class="quote-ik-wil">{{ $facet['ik_wil'] }}</p>
                            <p>{{ $facet['quote'] }}</p>
                        </div>
                    </div>

                    {{-- Author below the paper --}}
                    <div class="mt-4 flex items-center gap-3">
                        @if(!empty($facet['author_image']))
                            <img src="{{ $facet['author_image'] }}" alt="{{ $facet['author_name'] }}" class="w-10 h-10 rounded-full object-cover">
                        @endif
                        <div class="text-sm">
                            <span class="font-semibold text-[var(--color-text-primary)]">{{ $facet['author_name'] }}</span>
                            @if(!empty($facet['author_role']))
                                <span class="text-[var(--color-text-secondary)]"> &middot; {{ $facet['author_role'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- In de praktijk --}}
    @if(!empty($facet['practice_examples']))
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <h2 class="mb-2">In de praktijk</h2>
                @if(!empty($facet['practice_subtitle']))
                    <p class="text-[var(--color-text-secondary)] mb-8">{{ $facet['practice_subtitle'] }}</p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($facet['practice_examples'] as $example)
                        <div class="practice-card">
                            {{-- Image area --}}
                            <div class="aspect-[16/10]">
                                @if(!empty($example['image']))
                                    <img src="{{ $example['image'] }}" alt="{{ $example['name'] }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-[var(--color-bg-subtle)] flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Card body --}}
                            <div class="p-5">
                                <h3 class="text-lg mb-2">{{ $example['role'] }} {{ $example['name'] }}</h3>
                                <p class="text-sm text-[var(--color-text-secondary)]">{{ $example['story'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Vragen voor jezelf --}}
    @if(!empty($facet['reflection_questions']))
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <h2 class="mb-2">Vragen voor jezelf</h2>
                @if(!empty($facet['reflection_subtitle']))
                    <p class="text-[var(--color-text-secondary)] mb-8">{{ $facet['reflection_subtitle'] }}</p>
                @endif

                <div class="max-w-4xl space-y-3">
                    @foreach($facet['reflection_questions'] as $question)
                        <div class="question-row">
                            <span class="question-badge">?</span>
                            <p class="text-[var(--color-text-primary)]">{{ $question }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Tip box --}}
                @if(!empty($facet['tip_title']))
                    <div class="max-w-4xl mt-8 bg-[var(--color-bg-cream)] rounded-xl p-6 border border-[var(--color-border-light)]">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 mt-0.5 text-[var(--color-accent)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                            </svg>
                            <div>
                                <h3 class="text-lg mb-1">{{ $facet['tip_title'] }}</h3>
                                @if(!empty($facet['tip_text']))
                                    <p class="text-[var(--color-text-secondary)]">{{ $facet['tip_text'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <hr class="border-[var(--color-border-light)]">

    {{-- Initiatieven --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="mb-2">{{ $facet['initiatives_heading'] ?? 'Initiatieven bij deze doelstelling' }}</h2>
            <p class="text-[var(--color-text-secondary)] mb-8">Deze initiatieven scoren sterk op het facet {{ $facet['keyword'] }}.</p>

            @if($initiatives->isEmpty())
                <p class="text-[var(--color-text-secondary)]">Nog geen initiatieven gekoppeld aan deze doelstelling.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($initiatives as $initiative)
                        <x-initiative-card :initiative="$initiative" />
                    @endforeach
                </div>
            @endif

            <div class="mt-8 text-center">
                <flux:button variant="primary" href="{{ route('initiatives.index') }}">
                    Alle {{ $facetInitiativeCount > 0 ? $facetInitiativeCount : $totalInitiativeCount }} initiatieven{{ $facetInitiativeCount > 0 ? ' voor ' . $facet['keyword'] : '' }} bekijken
                </flux:button>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Ontdek de andere doelstellingen --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="mb-6">Ontdek de andere doelstellingen</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($allFacets as $slug => $item)
                    @if($slug !== $facet['slug'])
                        <a href="{{ route('goals.show', $slug) }}"
                           class="flex items-center gap-4 p-4 rounded-xl hover:bg-[var(--color-bg-subtle)] transition-colors">
                            <x-diamant-gem :letter="$item['letter']" size="md" />
                            <div class="flex-1 min-w-0">
                                <span class="font-semibold text-[var(--color-text-primary)]">{{ $item['keyword'] }}</span>
                                <span class="text-[var(--color-text-secondary)]"> &middot; {{ $item['tagline'] }}</span>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('initiatives.index') }}" class="cta-link">
                    Alle {{ $totalInitiativeCount }} initiatieven bekijken
                </a>
            </div>
        </div>
    </section>
</x-layout>
