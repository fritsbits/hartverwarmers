<x-layout title="Doelstellingen — DIAMANT-model">
    <div class="intro-block">
        <span class="section-label">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
            </svg>
            DIAMANT-model
        </span>
        <h1 class="text-5xl">Doelstellingen</h1>
        <p class="text-2xl text-[var(--color-text-secondary)]">Het DIAMANT-model beschrijft zeven doelstellingen die bijdragen aan het welzijn van bewoners in de ouderenzorg. Elke letter staat voor een essentieel aspect van een deugddoende activiteit.</p>
    </div>

    <section class="max-w-6xl mx-auto px-6 pb-16">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($facets as $slug => $facet)
                @php
                    $tagSlug = 'doel-' . $slug;
                    $tag = $goalTags->get($tagSlug);
                    $count = $tag ? $tag->initiatives_count : 0;
                @endphp
                <a href="{{ route('goals.show', $slug) }}"
                   class="content-card p-6 flex gap-5 items-start group {{ $loop->last && $loop->count % 2 !== 0 ? 'md:col-span-2' : '' }}">
                    <div class="diamant-badge">
                        {{ $facet['letter'] }}
                    </div>
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
    </section>
</x-layout>
