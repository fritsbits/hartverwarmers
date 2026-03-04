<x-layout title="Doelstellingen — DIAMANT-model" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Doelstellingen</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">DIAMANT-model</span>
            <h1 class="text-5xl mt-1">Doelstellingen</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4">Het DIAMANT-model beschrijft zeven doelstellingen die bijdragen aan het welzijn van bewoners in de ouderenzorg. Elke letter staat voor een essentieel aspect van een deugddoende activiteit.</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- DIAMANT Grid --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($facets as $slug => $facet)
                    @php
                        $tagSlug = 'doel-' . $slug;
                        $tag = $goalTags->get($tagSlug);
                        $count = $tag ? $tag->initiatives_count : 0;
                    @endphp
                    <a href="{{ route('goals.show', $slug) }}"
                       class="content-card p-6 flex gap-5 items-start group {{ $loop->last && $loop->count % 2 !== 0 ? 'md:col-span-2' : '' }}">
                        <x-diamant-gem :letter="$facet['letter']" size="lg" />
                        <div class="flex-1 min-w-0">
                            <h3 class="text-xl mb-1">{{ $facet['keyword'] }}</h3>
                            <p class="text-[var(--color-text-secondary)] mb-3">{{ $facet['ik_wil'] }}</p>
                            <span class="text-sm text-[var(--color-text-secondary)]">
                                {{ $count }} {{ $count === 1 ? 'initiatief' : 'initiatieven' }}
                            </span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-text-secondary)] group-hover:text-[var(--color-primary)] transition-colors shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endforeach
            </div>

            <div class="mt-12 text-center text-sm text-[var(--color-text-secondary)]">
                <p>Het DIAMANT-model is ontwikkeld door Maite Mallentjer.</p>
            </div>
        </div>
    </section>
</x-layout>
