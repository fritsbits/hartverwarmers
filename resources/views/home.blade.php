<x-layout title="Laat je bewoners schitteren">
    <!-- Hero Section -->
    <section class="bg-[var(--color-bg-cream)] text-center">
        <div class="py-20 max-w-3xl mx-auto px-6">
            <h1 class="text-5xl mb-4">Laat je bewoners schitteren</h1>
            <p class="text-[var(--color-text-secondary)] text-2xl font-light mb-8">
                Hartverwarmers helpt begeleiders in woonzorgcentra om elke dag iets betekenisvols mogelijk te maken. Met {{ $stats['initiatives'] }} initiatieven gedeeld door {{ $stats['contributors'] }} collega's.
            </p>

            <!-- Search bar -->
            <form action="{{ route('initiatives.index') }}" method="GET" class="max-w-xl mx-auto">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input type="text" name="search" placeholder="Zoek een initiatief, thema of materiaal..." class="w-full pl-12 pr-4 py-3.5 rounded-full border border-[var(--color-border-light)] bg-white text-base focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent shadow-sm placeholder:text-[var(--color-text-secondary)]">
                </div>
            </form>
        </div>
    </section>

    <!-- Featured Initiatives -->
    @if($initiatives->isNotEmpty())
        <section class="bg-[var(--color-bg-base)]">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="mb-10">
                    <span class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                        </svg>
                        Ontdek initiatieven
                    </span>
                    <h2 class="text-3xl">Wat ga je vandaag doen?</h2>
                    <p class="text-[var(--color-text-secondary)] mt-2">Laat je inspireren door deze  initiatieven en gebruik eventueel een al uitgewerkte fiche</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($initiatives->take(3) as $initiative)
                        <x-initiative-card :initiative="$initiative" :show-fiche-count="true" />
                    @endforeach
                </div>

                <div class="text-center mt-10">
                    <a href="{{ route('initiatives.index') }}" class="cta-link">
                        Bekijk alle initiatieven
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- Recent Fiches -->
    @if($recentFiches->isNotEmpty())
        <section class="bg-[var(--color-bg-base)]">
            <div class="max-w-6xl mx-auto px-6 pb-16">
                <h3 class="text-2xl mb-6">Recent gedeeld</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($recentFiches->take(3) as $fiche)
                        <x-fiche-card :fiche="$fiche" :show-tags="false" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- DIAMANT Kompas -->
    <section class="bg-[var(--color-bg-cream)] py-16">
        <div class="max-w-6xl mx-auto px-6">
            <span class="section-label">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                </svg>
                Het DIAMANT-kompas
            </span>
            <h2 class="text-2xl mb-3">Zeven doelen om bewoners te laten schitteren</h2>
            <p class="text-[var(--color-text-secondary)] max-w-2xl mb-6">
                Het DIAMANT-model biedt zeven doelstellingen die je helpen om van een gewone dag een waardevolle dag te maken. Elke letter staat voor een manier van kijken: wat maakt deze dag betekenisvol voor deze bewoner?
            </p>

            <a href="{{ route('goals.index') }}" class="cta-link text-sm mb-8 inline-flex">Meer over het DIAMANT-model</a>

            <!-- DIAMANT Letter Buttons -->
            <div x-data="{ activeGoal: '{{ $firstFacetSlug }}' }" class="mt-8">
                <div class="flex gap-2 mb-6">
                    @foreach($facets as $slug => $facet)
                        <button
                            @click="activeGoal = '{{ $slug }}'"
                            :class="activeGoal === '{{ $slug }}' ? 'bg-[var(--color-primary)] text-white' : 'bg-white text-[var(--color-primary)] border border-[var(--color-border-light)]'"
                            class="w-10 h-10 rounded-full font-bold text-sm font-[var(--font-heading)] transition-colors"
                        >
                            {{ $facet['letter'] }}
                        </button>
                    @endforeach
                </div>

                <!-- Goal Details -->
                @foreach($facets as $slug => $facet)
                    <div x-show="activeGoal === '{{ $slug }}'" x-cloak class="bg-white rounded-xl border border-[var(--color-border-light)] p-6">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="diamant-badge-sm">{{ $facet['letter'] }}</span>
                            <h3 class="text-lg">{{ $facet['keyword'] }}</h3>
                        </div>
                        <p class="font-semibold text-[var(--color-text-primary)] mb-2">{{ $facet['ik_wil'] }}</p>
                        <p class="text-sm text-[var(--color-text-secondary)] mb-4">{{ Str::limit($facet['description'], 200) }}</p>

                        @if(!empty($facet['practice_examples']))
                            <ul class="space-y-1.5 mb-4">
                                @foreach(array_slice($facet['practice_examples'], 0, 3) as $example)
                                    <li class="flex items-start gap-2 text-sm text-[var(--color-text-primary)]">
                                        <span class="text-[var(--color-primary)] mt-0.5">&rarr;</span>
                                        <span class="font-semibold">{{ $example['name'] }}</span> — {{ $example['story'] }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if(($goalInitiativeCounts[$slug] ?? 0) > 0)
                            <a href="{{ route('goals.show', $slug) }}" class="cta-link text-sm">
                                Bekijk alle {{ $goalInitiativeCounts[$slug] }} initiatieven voor {{ $facet['keyword'] }}
                            </a>
                        @else
                            <a href="{{ route('goals.show', $slug) }}" class="cta-link text-sm">
                                Bekijk initiatieven voor {{ $facet['keyword'] }}
                            </a>
                        @endif
                    </div>
                @endforeach

                <div class="text-center mt-8">
                    <a href="{{ route('goals.index') }}" class="cta-link">Alle 7 doelstellingen bekijken</a>
                </div>
            </div>
        </div>
    </section>
</x-layout>
