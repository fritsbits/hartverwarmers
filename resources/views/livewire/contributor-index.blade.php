@php
    // Fallback avatar color palette — deterministic by user ID
    $avatarColors = [
        ['bg' => 'bg-[#FDF3EE]', 'text' => 'text-[var(--color-primary)]'],      // orange
        ['bg' => 'bg-[#E8F6F8]', 'text' => 'text-[#3A9BA8]'],                   // teal
        ['bg' => 'bg-[#FEF6E0]', 'text' => 'text-[#B08A22]'],                   // yellow
        ['bg' => 'bg-[#F3E8F3]', 'text' => 'text-[#9A5E98]'],                   // purple
    ];
    $getAvatarColor = fn ($id) => $avatarColors[$id % count($avatarColors)];
    $getInitials = fn ($user) => mb_strtoupper(mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name, 0, 1));
@endphp

<div>
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)] overflow-hidden">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Bijdragers</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Bijdragers</span>
                <h1 class="text-5xl mt-1">Samen maken we het verschil</h1>
                <p class="text-xl text-[var(--color-text-secondary)] mt-4 font-light leading-relaxed">
                    Al <span class="font-semibold text-[var(--color-text-primary)]">{{ $this->stats['contributors_count'] }}</span> activiteitenbegeleiders uit <span class="font-semibold text-[var(--color-text-primary)]">{{ $this->stats['organisations_count'] }}</span> organisaties delen samen <span class="font-semibold text-[var(--color-text-primary)]">{{ $this->stats['fiches_count'] }}</span> praktijkfiches. Ontdek wie er bijdraagt en laat je inspireren.
                </p>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Curated sections (hidden when searching) --}}
    @if(! $this->isSearching())

        {{-- ── Section 1: Recently Active — show what they shared ── --}}
        @if($this->recentlyActive->isNotEmpty())
            <section>
                <div class="max-w-6xl mx-auto px-6 py-16">
                    <span class="section-label">Recent actief</span>
                    <h2 class="text-3xl mt-1 mb-8">Wie deelde er recent?</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($this->recentlyActive as $contributor)
                            @php
                                $color = $getAvatarColor($contributor->id);
                                $latestFiche = $contributor->fiches->first();
                                $ficheTitle = $latestFiche?->title;
                            @endphp
                            <a href="{{ route('contributors.show', $contributor) }}"
                               class="group flex items-center gap-4 p-4 rounded-xl hover:bg-[var(--color-bg-cream)] transition-colors">

                                @if($contributor->avatar_path)
                                    <img src="{{ $contributor->avatarUrl() }}"
                                         alt=""
                                         class="w-14 h-14 rounded-full object-cover shrink-0 group-hover:scale-105 transition-transform"
                                         @if(! $loop->first) loading="lazy" @endif>
                                @else
                                    <div class="w-14 h-14 rounded-full {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center text-base font-bold shrink-0 group-hover:scale-105 transition-transform">
                                        {{ $getInitials($contributor) }}
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <h3 class="font-heading font-bold leading-snug group-hover:text-[var(--color-primary)] transition-colors">
                                        {{ $contributor->full_name }}
                                    </h3>

                                    @if($ficheTitle)
                                        <p class="text-sm text-[var(--color-text-secondary)] font-light mt-0.5">
                                            Deelde <span class="font-medium text-[var(--color-text-primary)]">{{ $ficheTitle }}</span>
                                        </p>
                                    @endif

                                    @if($contributor->latest_fiche_at)
                                        <span class="inline-flex items-center gap-1 mt-1 text-xs font-medium text-[var(--color-primary)]">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                            {{ \Carbon\Carbon::parse($contributor->latest_fiche_at)->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ── Section 2: Newcomers — bulletin board feel ── --}}
        @if($this->newcomers->isNotEmpty())
            <section class="bg-[var(--color-bg-cream)]">
                <div class="max-w-6xl mx-auto px-6 py-16">
                    <span class="section-label">Nieuw</span>
                    <h2 class="text-3xl mt-1 mb-8">Welkom, nieuwe bijdragers</h2>
                    <div class="flex gap-6 overflow-x-auto pb-4 -mx-6 px-6 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-4 sm:overflow-visible sm:pb-0 sm:gap-8 lg:gap-10 snap-x snap-mandatory">
                        @foreach($this->newcomers as $i => $contributor)
                            @php
                                $rotations = ['-rotate-2', 'rotate-1', '-rotate-1', 'rotate-2'];
                                $rotation = $rotations[$i % 4];
                                $color = $getAvatarColor($contributor->id);
                                $newcomerFiche = $contributor->fiches->first();
                                $newcomerFicheTitle = $newcomerFiche?->title;
                            @endphp
                            <a href="{{ route('contributors.show', $contributor) }}"
                               class="group flex flex-col items-center text-center bg-white p-5 pb-6 min-w-[180px] snap-start sm:min-w-0 {{ $rotation }} hover:rotate-0 transition-all duration-300 hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-2"
                               style="box-shadow: 8px 10px 25px -5px rgba(120, 90, 60, 0.15);">

                                @if($contributor->avatar_path)
                                    <img src="{{ $contributor->avatarUrl() }}"
                                         alt=""
                                         class="w-16 h-16 rounded-full object-cover mb-3"
                                         loading="lazy">
                                @else
                                    <div class="w-16 h-16 rounded-full {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center text-lg font-bold mb-3">
                                        {{ $getInitials($contributor) }}
                                    </div>
                                @endif

                                <h3 class="font-heading font-bold leading-snug group-hover:text-[var(--color-primary)] transition-colors max-w-full text-center">
                                    {{ $contributor->full_name }}
                                </h3>

                                @if($newcomerFicheTitle)
                                    <p class="text-xs text-[var(--color-text-secondary)] font-light mt-1 max-w-full text-center">
                                        Deelde <span class="font-medium">{{ $newcomerFicheTitle }}</span>
                                    </p>
                                @endif

                                @if($contributor->latest_fiche_at)
                                    <span class="inline-flex items-center gap-1 mt-1.5 text-xs font-medium text-[var(--color-primary)]">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                        {{ \Carbon\Carbon::parse($contributor->latest_fiche_at)->diffForHumans() }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ── Section 3: Top Contributors — ranked list, column-first ── --}}
        @if($this->topContributors->isNotEmpty())
            <section>
                <div class="max-w-6xl mx-auto px-6 py-16">
                    <span class="section-label">Meest gedeeld</span>
                    <h2 class="text-3xl mt-1 mb-8">Onze topbijdragers</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_1px_1fr] sm:grid-rows-4 sm:grid-flow-col gap-y-3 gap-x-6">
                        @foreach($this->topContributors as $i => $contributor)
                            @php $color = $getAvatarColor($contributor->id); @endphp

                            {{-- Insert divider column after the 4th item --}}
                            @if($i === 4)
                                <div class="hidden sm:block row-span-4 bg-[var(--color-border-light)]"></div>
                            @endif

                            <a href="{{ route('contributors.show', $contributor) }}"
                               class="group flex items-center gap-4 p-4 rounded-xl hover:bg-[var(--color-bg-cream)] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-2">

                                <span class="text-3xl font-heading font-bold leading-none w-8 text-center shrink-0 {{ $i < 3 ? 'text-[var(--color-primary)]' : 'text-[var(--color-border-hover)]' }}">
                                    {{ $i + 1 }}
                                </span>

                                @if($contributor->avatar_path)
                                    <img src="{{ $contributor->avatarUrl() }}"
                                         alt=""
                                         class="w-12 h-12 rounded-full object-cover shrink-0 {{ $i < 3 ? 'ring-2 ring-[var(--color-primary)]/20' : '' }}"
                                         loading="lazy">
                                @else
                                    <div class="w-12 h-12 rounded-full {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center text-sm font-bold shrink-0 {{ $i < 3 ? 'ring-2 ring-[var(--color-primary)]/20' : '' }}">
                                        {{ $getInitials($contributor) }}
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <h3 class="font-heading font-bold leading-snug group-hover:text-[var(--color-primary)] transition-colors sm:truncate">
                                        {{ $contributor->full_name }}
                                    </h3>
                                    @if($contributor->organisation)
                                        <p class="text-sm text-[var(--color-text-secondary)] font-light truncate">
                                            {{ $contributor->organisation }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    @php
                                        $maxFiches = $this->topContributors->max('fiches_count') ?: 1;
                                        $barWidth = round(($contributor->fiches_count / $maxFiches) * 60);
                                    @endphp
                                    <div class="hidden sm:block h-2 rounded-full bg-[var(--color-bg-subtle)] overflow-hidden" style="width: 60px;">
                                        <div class="h-full rounded-full {{ $i < 3 ? 'bg-[var(--color-primary)]' : 'bg-[var(--color-border-hover)]' }}" style="width: {{ $barWidth }}px;"></div>
                                    </div>
                                    <span class="text-sm font-heading font-bold {{ $i < 3 ? 'text-[var(--color-primary)]' : 'text-[var(--color-text-secondary)]' }}">
                                        {{ $contributor->fiches_count }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ── Section 4: Community Engagers ── --}}
        @if($this->communityEngagers->isNotEmpty())
            <section class="bg-[var(--color-bg-cream)]">
                <div class="max-w-6xl mx-auto px-6 py-16">
                    <span class="section-label">Community</span>
                    <h2 class="text-3xl mt-1 mb-8">Betrokken collega's</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6">
                        @foreach($this->communityEngagers as $contributor)
                            @php $color = $getAvatarColor($contributor->id); @endphp
                            <a href="{{ route('contributors.show', $contributor) }}"
                               class="group flex flex-col items-center text-center p-4 rounded-2xl hover:bg-white/60 transition-colors">

                                @if($contributor->avatar_path)
                                    <img src="{{ $contributor->avatarUrl() }}"
                                         alt=""
                                         class="w-14 h-14 rounded-full object-cover mb-3"
                                         loading="lazy">
                                @else
                                    <div class="w-14 h-14 rounded-full {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center text-sm font-bold mb-3">
                                        {{ $getInitials($contributor) }}
                                    </div>
                                @endif

                                <h3 class="font-heading font-bold text-sm leading-snug group-hover:text-[var(--color-primary)] transition-colors truncate max-w-full">
                                    {{ $contributor->full_name }}
                                </h3>

                                <span class="inline-flex items-center gap-1 mt-2 text-xs text-[var(--color-text-secondary)]">
                                    <svg class="w-3.5 h-3.5 text-[var(--color-primary)]" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>
                                    {{ $contributor->kudos_given_count + $contributor->comments_count }} kudos & reacties
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

    @endif

    {{-- ── Full directory ── --}}
    <section id="directory" class="bg-[var(--color-bg-cream)] scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex flex-col lg:flex-row gap-12">

                {{-- Main column: search + list --}}
                <div class="flex-1 min-w-0">
                    <span class="section-label">Overzicht</span>
                    <h2 class="text-3xl mt-1 mb-6">Alle bijdragers</h2>

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
                    @if($this->isSearching())
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
                                @if($this->isSearching())
                                    Geen bijdragers gevonden voor "{{ $search }}"
                                @else
                                    Nog geen bijdragers.
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="divide-y divide-[var(--color-border-light)]">
                            @foreach($contributors as $contributor)
                                @php $color = $getAvatarColor($contributor->id); @endphp

                                <a href="{{ route('contributors.show', $contributor) }}"
                                   class="group flex items-start gap-4 py-4 -mx-3 px-3 rounded-xl hover:bg-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-2"
                                   wire:key="contributor-{{ $contributor->id }}">

                                    @if($contributor->avatar_path)
                                        <img src="{{ $contributor->avatarUrl() }}"
                                             alt=""
                                             class="w-12 h-12 rounded-full object-cover shrink-0"
                                             loading="lazy">
                                    @else
                                        <div class="w-12 h-12 rounded-full {{ $color['bg'] }} {{ $color['text'] }} flex items-center justify-center text-sm font-bold shrink-0">
                                            {{ $getInitials($contributor) }}
                                        </div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-lg font-heading font-bold leading-snug group-hover:text-[var(--color-primary)] transition-colors">
                                            {{ $contributor->full_name }}
                                        </h4>

                                        @if($contributor->function_title || $contributor->organisation)
                                            <p class="text-sm text-[var(--color-text-secondary)] font-light mt-0.5">
                                                {{ collect([$contributor->function_title, $contributor->organisation])->filter()->join(', ') }}
                                            </p>
                                        @endif

                                        @php
                                            $initiativeTitles = $contributor->fiches->pluck('initiative.title')->filter()->unique()->values();
                                        @endphp
                                        @if($initiativeTitles->isNotEmpty())
                                            <p class="text-sm text-[var(--color-text-secondary)] font-light mt-1.5">
                                                Deelde fiches over {{ $initiativeTitles->take(3)->join(', ', ' en ') }}{{ $initiativeTitles->count() > 3 ? ' en meer' : '' }}
                                            </p>
                                        @endif
                                    </div>

                                    <span class="text-sm text-[var(--color-text-secondary)] font-light shrink-0 hidden sm:block mt-1">
                                        {{ $contributor->fiches_count }}&nbsp;{{ $contributor->fiches_count === 1 ? 'fiche' : 'fiches' }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Sidebar: sticky CTA --}}
                <div class="hidden lg:block w-72 shrink-0">
                    <div class="sticky top-24">
                        @guest
                            <div class="rounded-2xl bg-white border border-[var(--color-border-light)] p-6 text-center">
                                <div class="w-14 h-14 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-7 h-7 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-heading font-bold mb-2">Word ook bijdrager</h3>
                                <p class="text-sm text-[var(--color-text-secondary)] font-light leading-relaxed mb-5">
                                    Doe zoals deze collega's en deel jouw praktijkervaring.
                                </p>
                                <a href="{{ route('register') }}" class="btn-pill w-full text-center text-sm">Registreer</a>
                            </div>
                        @endguest

                        @auth
                            <div class="rounded-2xl bg-white border border-[var(--color-border-light)] p-6 text-center">
                                <div class="w-14 h-14 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-7 h-7 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-heading font-bold mb-2">Deel je ervaring</h3>
                                <p class="text-sm text-[var(--color-text-secondary)] font-light leading-relaxed mb-5">
                                    Schrijf een fiche en inspireer collega's in heel Vlaanderen en Nederland.
                                </p>
                                <a href="{{ route('fiches.create') }}" class="btn-pill w-full text-center text-sm">Schrijf een fiche</a>
                            </div>
                        @endauth
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Mobile CTA (hidden on desktop where sidebar shows) --}}
    @guest
        <section class="lg:hidden relative bg-white border-t border-[var(--color-border-light)] overflow-hidden">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="max-w-lg">
                    <span class="section-label mb-2">Word deel van de community</span>
                    <h2 class="text-3xl mb-3">Word ook bijdrager</h2>
                    <p class="text-lg text-[var(--color-text-secondary)] font-light mb-8 leading-relaxed">
                        Doe zoals deze collega's en deel jouw praktijkervaring met activiteitenbegeleiders in heel Vlaanderen en Nederland.
                    </p>
                    <a href="{{ route('register') }}" class="btn-pill">Registreer</a>
                </div>
            </div>
        </section>
    @endguest
</div>
