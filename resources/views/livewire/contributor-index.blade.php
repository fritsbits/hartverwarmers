<div>
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)] overflow-hidden">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Bijdragers</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8 lg:gap-12">
                {{-- Copy --}}
                <div class="max-w-2xl">
                    <span class="section-label section-label-hero">Bijdragers</span>
                    <h1 class="text-5xl mt-1">Samen maken we het verschil</h1>
                    <p class="text-xl text-[var(--color-text-secondary)] mt-4 font-light leading-relaxed">
                        Activiteitenbegeleiders uit heel Vlaanderen en Nederland delen hun praktijkervaring. Ontdek wie er bijdraagt en laat je inspireren.
                    </p>
                </div>

                {{-- Stats --}}
                <div class="flex items-baseline gap-6 lg:gap-8 shrink-0">
                    <div class="text-center">
                        <span class="block text-3xl lg:text-4xl font-heading font-bold text-[var(--color-primary)] leading-none">{{ $this->stats['contributors_count'] }}</span>
                        <span class="block text-xs text-[var(--color-text-secondary)] font-medium tracking-wide uppercase mt-1">bijdragers</span>
                    </div>
                    <div class="w-px h-8 bg-[var(--color-border-light)]"></div>
                    <div class="text-center">
                        <span class="block text-3xl lg:text-4xl font-heading font-bold text-[var(--color-primary)] leading-none">{{ $this->stats['organisations_count'] }}</span>
                        <span class="block text-xs text-[var(--color-text-secondary)] font-medium tracking-wide uppercase mt-1">organisaties</span>
                    </div>
                    <div class="w-px h-8 bg-[var(--color-border-light)]"></div>
                    <div class="text-center">
                        <span class="block text-3xl lg:text-4xl font-heading font-bold text-[var(--color-primary)] leading-none">{{ $this->stats['fiches_count'] }}</span>
                        <span class="block text-xs text-[var(--color-text-secondary)] font-medium tracking-wide uppercase mt-1">fiches</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Search + Contributors list with sidebar --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex flex-col lg:flex-row gap-12">

                {{-- Main column: search + list --}}
                <div class="flex-1 min-w-0">
                    {{-- Search bar --}}
                    <div class="max-w-md mb-8">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="Zoek op naam of organisatie..."
                            icon="magnifying-glass"
                            clearable
                        />
                    </div>

                    {{-- Results info when searching --}}
                    @if(strlen(trim($search)) >= 2)
                        <p class="text-sm text-[var(--color-text-secondary)] mb-6">
                            Resultaten voor "{{ $search }}"
                        </p>
                    @endif

                    {{-- Contributors list --}}
                    @if($contributors->isEmpty())
                        <div class="text-center py-16">
                            <div class="w-16 h-16 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center mx-auto mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
                                </svg>
                            </div>
                            <p class="text-[var(--color-text-secondary)] font-light">
                                @if(strlen(trim($search)) >= 2)
                                    Geen bijdragers gevonden voor "{{ $search }}"
                                @else
                                    Nog geen bijdragers.
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="divide-y divide-[var(--color-border-light)]">
                            @foreach($contributors as $contributor)
                                <a href="{{ route('contributors.show', $contributor) }}"
                                   class="group flex items-start gap-4 py-5 -mx-3 px-3 rounded-xl hover:bg-[var(--color-bg-cream)] transition-colors"
                                   wire:key="contributor-{{ $contributor->id }}">

                                    {{-- Avatar --}}
                                    @if($contributor->avatar_path)
                                        <img src="{{ $contributor->avatarUrl() }}"
                                             alt=""
                                             class="w-12 h-12 rounded-full object-cover shrink-0"
                                             loading="lazy">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center text-lg font-bold shrink-0">
                                            {{ mb_substr($contributor->first_name, 0, 1) }}
                                        </div>
                                    @endif

                                    {{-- Info --}}
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-lg font-heading font-bold leading-snug group-hover:text-[var(--color-primary)] transition-colors">
                                            {{ $contributor->full_name }}
                                        </h4>

                                        @if($contributor->function_title || $contributor->organisation)
                                            <p class="text-sm text-[var(--color-text-secondary)] font-light mt-0.5">
                                                {{ collect([$contributor->function_title, $contributor->organisation])->filter()->join(', ') }}
                                            </p>
                                        @endif

                                        {{-- Qualitative context --}}
                                        @php
                                            $initiativeTitles = $contributor->fiches->pluck('initiative.title')->filter()->unique()->values();
                                        @endphp
                                        @if($initiativeTitles->isNotEmpty())
                                            <p class="text-sm text-[var(--color-text-secondary)] font-light mt-1.5">
                                                Deelde fiches over {{ $initiativeTitles->take(3)->join(', ', ' en ') }}{{ $initiativeTitles->count() > 3 ? ' en meer' : '' }}
                                            </p>
                                        @endif
                                    </div>

                                    {{-- Fiche count (subtle, right-aligned) --}}
                                    <span class="text-sm text-[var(--color-text-secondary)] font-light shrink-0 hidden sm:block mt-1">
                                        {{ $contributor->fiches_count }}&nbsp;{{ $contributor->fiches_count === 1 ? 'fiche' : 'fiches' }}
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-8">
                            {{ $contributors->links() }}
                        </div>
                    @endif
                </div>

                {{-- Sidebar: sticky CTA --}}
                <div class="hidden lg:block w-72 shrink-0">
                    <div class="sticky top-8">
                        @guest
                            <div class="rounded-2xl bg-[var(--color-bg-cream)] border border-[var(--color-border-light)] p-6">
                                <h3 class="text-lg mb-2">Word ook bijdrager</h3>
                                <p class="text-sm text-[var(--color-text-secondary)] font-light leading-relaxed mb-5">
                                    Deel je praktijkfiches met collega's uit heel Vlaanderen en Nederland.
                                </p>
                                <a href="{{ route('register') }}" class="btn-pill w-full text-center text-sm">Registreer</a>
                            </div>
                        @endguest

                        @auth
                            <div class="rounded-2xl bg-[var(--color-bg-cream)] border border-[var(--color-border-light)] p-6">
                                <h3 class="text-lg mb-2">Deel je ervaring</h3>
                                <p class="text-sm text-[var(--color-text-secondary)] font-light leading-relaxed mb-5">
                                    Heb je een activiteit die goed werkt? Schrijf een fiche en inspireer collega's.
                                </p>
                                <a href="{{ route('fiches.create') }}" class="btn-pill w-full text-center text-sm">Schrijf een fiche</a>
                            </div>
                        @endauth

                        {{-- Community stats in sidebar --}}
                        <div class="mt-6 space-y-3 text-sm text-[var(--color-text-secondary)]">
                            <p><span class="font-semibold text-[var(--color-text-primary)]">{{ $this->stats['contributors_count'] }}</span> bijdragers</p>
                            <p><span class="font-semibold text-[var(--color-text-primary)]">{{ $this->stats['organisations_count'] }}</span> organisaties</p>
                            <p><span class="font-semibold text-[var(--color-text-primary)]">{{ $this->stats['fiches_count'] }}</span> fiches gedeeld</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Mobile CTA (hidden on desktop where sidebar shows) --}}
    @guest
        <section class="lg:hidden relative bg-[var(--color-bg-cream)] border-t border-[var(--color-border-light)] overflow-hidden">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="max-w-lg">
                    <span class="section-label mb-2">Word deel van de community</span>
                    <h2 class="text-3xl mb-3">Deel jouw ervaring</h2>
                    <p class="text-lg text-[var(--color-text-secondary)] font-light mb-8 leading-relaxed">
                        Ben je activiteitenbegeleider in een woonzorgcentrum? Deel je praktijkfiches met collega's uit heel Vlaanderen en Nederland.
                    </p>
                    <a href="{{ route('register') }}" class="btn-pill">Registreer</a>
                </div>
            </div>
        </section>
    @endguest
</div>
