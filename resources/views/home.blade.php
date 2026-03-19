<x-layout title="Laat je bewoners schitteren" description="Hartverwarmers is hét platform voor activiteitenbegeleiders in woonzorgcentra. Ontdek en deel praktijkfiches rond deugddoende activiteiten." :full-width="true">
    <!-- Hero Section -->
    <section class="bg-[var(--color-bg-cream)] overflow-hidden border-b border-[var(--color-border-light)]">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center">
            <!-- Copy -->
            <div class="flex-1 px-6 py-16 md:py-20 text-center md:text-left">
                <h1 class="text-5xl mb-4">Laat je bewoners schitteren</h1>
                <p class="text-[var(--color-text-secondary)] text-2xl font-light mb-8">
                    Hartverwarmers helpt begeleiders in woonzorgcentra om elke dag iets betekenisvols mogelijk te maken. Met {{ $stats['fiches'] }} fiches gedeeld door {{ $stats['contributors'] }} collega's.
                </p>

                <!-- Search bar (opens command palette modal) -->
                <div class="max-w-xl">
                    <flux:modal.trigger name="search">
                        <flux:input as="button" icon="magnifying-glass" placeholder="Zoek initiatieven en fiches..." class="!rounded-full !py-3.5 !text-base" />
                    </flux:modal.trigger>
                </div>
            </div>

            <!-- Hero image -->
            <div class="flex-1 hidden md:block">
                <img
                    src="{{ asset('images/hero-binder.webp') }}"
                    alt="Een map vol kleurrijke fiches en activiteitenkaarten voor bewoners"
                    class="w-full h-full object-cover"
                    width="1024"
                    height="1024"
                    loading="eager"
                />
            </div>
        </div>
    </section>

    <!-- Onboarding Banner (new users only) -->
    <livewire:onboarding-banner />

    <!-- Whats-new Banner (returning users only, white zone) -->
    <x-whats-new-banner />

    <!-- Featured Initiatives -->
    @if($initiatives->isNotEmpty())
        <section class="bg-[var(--color-bg-base)]" x-data="{
            selectedGoal: '{{ $defaultGoal }}',
            goals: @js($goals),
            initiatives: @js($initiatives->map(fn ($i) => [
                'id' => $i->id,
                'goalSlugs' => $i->tags->pluck('slug')->values(),
            ])),
            headingOpen: false,
            hoverTimeout: null,
            openHeading() {
                clearTimeout(this.hoverTimeout);
                this.headingOpen = true;
            },
            closeHeading() {
                this.hoverTimeout = setTimeout(() => this.headingOpen = false, 150);
            },
            get currentGoal() {
                return this.goals.find(g => g.tagSlug === this.selectedGoal);
            },
            get inspiratie() {
                return this.currentGoal?.inspiratie ?? '';
            },
            _shuffleCache: {},
            getShuffledIds(goalSlug) {
                if (this._shuffleCache[goalSlug]) return this._shuffleCache[goalSlug];
                const matched = this.initiatives.filter(i =>
                    !goalSlug || i.goalSlugs.includes(goalSlug)
                );
                const shuffled = [...matched];
                for (let i = shuffled.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
                }
                this._shuffleCache[goalSlug] = shuffled.slice(0, 3).map(i => i.id);
                return this._shuffleCache[goalSlug];
            },
            get filteredIds() {
                return this.getShuffledIds(this.selectedGoal);
            },
            isVisible(id) {
                return this.filteredIds.includes(id);
            }
        }">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="mb-6">
                    <span class="section-label">
                        Initiatieven
                    </span>
                    <div class="flex items-baseline justify-between gap-4">
                        <h2 class="text-3xl">
                            Inspiratie om <span class="relative inline" @mouseenter="openHeading()" @mouseleave="closeHeading()">
                                <span class="italic cursor-pointer transition-colors hover:text-[var(--color-primary)] border-b border-dotted border-[var(--color-border-light)]"
                                      x-text="inspiratie"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4 -mt-0.5 ml-0.5 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>

                                <div x-cloak x-show="headingOpen"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute left-0 top-full pt-2 z-50">
                                <div class="bg-white rounded-lg shadow-lg border border-[var(--color-border-light)] py-1 whitespace-nowrap">
                                    @foreach($goals as $goal)
                                        <button class="block w-full px-4 py-2 text-left text-base font-heading font-bold hover:bg-[var(--color-bg-cream)] hover:text-[var(--color-primary)] transition-colors cursor-pointer"
                                                :class="selectedGoal === '{{ $goal['tagSlug'] }}' ? 'text-[var(--color-primary)]' : 'text-[var(--color-text-primary)]'"
                                                @click="selectedGoal = '{{ $goal['tagSlug'] }}'; headingOpen = false">
                                            Inspiratie om {{ $goal['inspiratie'] }}
                                        </button>
                                    @endforeach
                                </div>
                                </div>
                            </span>
                        </h2>
                        <a href="{{ route('initiatives.index') }}" class="cta-link shrink-0">Alle initiatieven</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($initiatives as $initiative)
                        <div x-show="isVisible({{ $initiative->id }})" x-cloak>
                            <x-initiative-card :initiative="$initiative" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Recent Fiches -->
    @if($recentFiches->isNotEmpty())
        <section class="bg-[var(--color-bg-base)]">
            <div class="max-w-6xl mx-auto px-6 pb-16">
                <div class="mb-10">
                    <span class="section-label">
                        Uitgewerkte fiches
                    </span>
                    <h2 class="text-3xl">Activiteiten gedeeld door collega's</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Left: Recent --}}
                    <div>
                        <h3 class="text-lg font-heading font-bold mb-4">Nieuwste fiches</h3>
                        <div class="space-y-2">
                            @foreach($recentFiches->take(4) as $fiche)
                                <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="fiche-list-item">
                                    <x-fiche-icon :fiche="$fiche" class="fiche-list-icon" />
                                    <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                        <span class="font-body font-semibold text-lg text-[var(--color-text-primary)] truncate">{{ $fiche->title }}</span>
                                        <span class="text-xs text-[var(--color-text-secondary)]">{{ $fiche->user?->full_name }}</span>
                                    </div>
                                    <span class="flex items-center gap-2.5 shrink-0">
                                        <span class="fiche-list-kudos @if($fiche->kudos_count > 0) fiche-list-kudos-active @endif">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/></svg>
                                            {{ $fiche->kudos_count }}
                                        </span>
                                        <span class="fiche-list-kudos">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 0 0 6 21.75a6.721 6.721 0 0 0 3.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-8.25S17.322 4.5 12 4.5 2.25 8.47 2.25 12.75c0 2.534 1.221 4.745 3.065 6.232-.097.99-.616 2.048-1.395 2.795a.684.684 0 0 0 .884.867Z" clip-rule="evenodd"/></svg>
                                            {{ $fiche->comments_count }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Right: Recent diamantje --}}
                    @if($recentDiamond ?? null)
                        <div class="flex flex-col">
                            <div class="flex items-baseline justify-between mb-4">
                                <h3 class="text-lg font-heading font-bold">Uitgelichte fiche</h3>
                                <a href="{{ route('diamantjes.index') }}" class="cta-link text-sm">Alle</a>
                            </div>
                            @php $previews = $recentDiamond->cardPreviewImages(3); @endphp
                            <div class="group relative flex flex-row flex-1 rounded-[var(--radius-sm)] border border-[var(--color-border-light)] bg-[var(--color-bg-white)] hover:shadow-card-hover hover:-translate-y-0.5 hover:border-[var(--color-border-hover)] transition-all duration-200">
                                {{-- Diamantje ribbon — flush left, rounded right --}}
                                <span class="absolute top-5 left-0 z-20 inline-flex items-center gap-1.5 pl-3 pr-4 py-1.5 text-xs font-semibold text-white bg-[var(--color-primary)] rounded-r-full shadow-sm pointer-events-none">
                                    <x-diamant-gem size="xxs" :pronounced="false" :inverted="true" />
                                    Diamantje
                                </span>

                                {{-- Left: preview --}}
                                @if(count($previews) > 0)
                                    <a href="{{ route('fiches.show', [$recentDiamond->initiative, $recentDiamond]) }}" class="relative bg-[var(--color-bg-cream)] shrink-0 w-1/2 min-h-[220px] overflow-hidden block rounded-l-[var(--radius-sm)]">
                                        @foreach($previews as $i => $url)
                                            <div class="fiche-paper fiche-paper-{{ $i }}" style="z-index: {{ $i + 1 }}">
                                                <img src="{{ $url }}" alt="" loading="lazy" draggable="false">
                                            </div>
                                        @endforeach
                                    </a>
                                @endif

                                {{-- Right: info --}}
                                <div class="flex-1 px-5 flex flex-col min-w-0 rounded-r-[var(--radius-sm)] overflow-hidden {{ count($previews) === 0 ? 'pt-14 pb-5' : 'py-5' }}">
                                    <a href="{{ route('fiches.show', [$recentDiamond->initiative, $recentDiamond]) }}" class="no-underline text-inherit flex-1 overflow-hidden">
                                        <span class="font-heading font-bold text-lg text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors leading-snug">{{ $recentDiamond->title }}</span>

                                        @if($recentDiamond->description)
                                            <p class="text-sm text-[var(--color-text-secondary)] mt-2 leading-relaxed">{{ strip_tags($recentDiamond->description) }}</p>
                                        @endif
                                    </a>

                                    <div class="mt-auto pt-4 border-t border-[var(--color-border-light)] text-sm">
                                        @if($recentDiamond->user)
                                            <div class="flex items-center gap-2 min-w-0">
                                                @if($recentDiamond->user->avatar_path)
                                                    <img src="{{ $recentDiamond->user->avatarUrl() }}" alt="{{ $recentDiamond->user->first_name }}" class="w-6 h-6 rounded-full object-cover shrink-0">
                                                @else
                                                    <div class="w-6 h-6 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-[10px] font-semibold shrink-0">
                                                        {{ strtoupper(substr($recentDiamond->user->first_name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <span class="text-xs text-[var(--color-text-secondary)] truncate">{{ $recentDiamond->user->full_name }}</span>
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-3 mt-3 pt-3 border-t border-[var(--color-border-light)]">
                                            <span class="fiche-list-kudos @if($recentDiamond->kudos_count > 0) fiche-list-kudos-active @endif">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/></svg>
                                                {{ $recentDiamond->kudos_count }}
                                            </span>
                                            <span class="fiche-list-kudos">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 0 0 6 21.75a6.721 6.721 0 0 0 3.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-8.25S17.322 4.5 12 4.5 2.25 8.47 2.25 12.75c0 2.534 1.221 4.745 3.065 6.232-.097.99-.616 2.048-1.395 2.795a.684.684 0 0 0 .884.867Z" clip-rule="evenodd"/></svg>
                                                {{ $recentDiamond->comments_count }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif


    <!-- DIAMANT Kompas -->
    @feature('diamant-goals')
    <section class="bg-[var(--color-bg-cream)] py-16">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-12 items-start">
                <div>
                    <span class="section-label">
                        Het DIAMANT-kompas
                    </span>

                    <h2 class="text-3xl mb-3">Zeven doelen om bewoners te laten schitteren</h2>
                    <p class="text-[var(--color-text-secondary)] max-w-2xl mb-6">
                        Het DIAMANT-kompas is het onderliggende kwaliteitskader van Hartverwarmers. Het helpt je kijken naar wat een activiteit écht waardevol maakt — voor deze bewoner, op dit moment. Elke letter van DIAMANT staat voor een manier van kijken.
                    </p>

                    <a href="{{ route('goals.index') }}" class="cta-link">Meer over het DIAMANT-model</a>
                </div>

                <div class="flex flex-col items-center justify-center">
                    <div class="flex gap-4 items-start">
                        <figure class="photo-polaroid" style="transform: rotate(-3deg)">
                            <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-36 aspect-square object-cover">
                            <figcaption>Maite Mallentjer</figcaption>
                        </figure>
                        <figure class="photo-polaroid -mt-2" style="transform: rotate(2deg)">
                            <img src="/img/wonen-en-leven/nadinepraet.jpg" alt="Nadine Praet" class="w-36 aspect-square object-cover">
                            <figcaption>Nadine Praet</figcaption>
                        </figure>
                    </div>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-4 text-center max-w-80">
                        Ontwikkeld door Maite Mallentjer (AP Hogeschool) en Nadine Praet (Arteveldehogeschool Gent).
                    </p>
                </div>
            </div>
        </div>
    </section>
    @endfeature

    <script type="application/ld+json">
    @php
        echo json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Hartverwarmers',
            'url' => route('home'),
            'logo' => asset('img/favicon.svg'),
            'description' => 'Hartverwarmers is hét platform voor activiteitenbegeleiders in woonzorgcentra. Ontdek en deel praktijkfiches rond deugddoende activiteiten.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    @endphp
    </script>
</x-layout>
