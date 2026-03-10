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

    {{-- Search + Grid --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            {{-- Search bar --}}
            <div class="max-w-md mb-10">
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

            {{-- Contributors grid --}}
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($contributors as $contributor)
                        <a href="{{ route('contributors.show', $contributor) }}"
                           class="group content-card p-5 flex items-start gap-4"
                           wire:key="contributor-{{ $contributor->id }}">

                            {{-- Avatar --}}
                            @if($contributor->avatar_path)
                                <img src="{{ $contributor->avatarUrl() }}"
                                     alt=""
                                     class="w-11 h-11 rounded-full object-cover shrink-0 ring-2 ring-white shadow-sm"
                                     loading="lazy">
                            @else
                                <div class="w-11 h-11 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center text-base font-bold shrink-0 ring-2 ring-white shadow-sm">
                                    {{ mb_substr($contributor->first_name, 0, 1) }}
                                </div>
                            @endif

                            {{-- Info --}}
                            <div class="min-w-0 flex-1">
                                <h4 class="text-[15px] font-heading font-bold leading-snug truncate group-hover:text-[var(--color-primary)] transition-colors">
                                    {{ $contributor->full_name }}
                                </h4>

                                @if($contributor->function_title || $contributor->organisation)
                                    <p class="text-[13px] text-[var(--color-text-secondary)] font-light mt-0.5 truncate">
                                        {{ collect([$contributor->function_title, $contributor->organisation])->filter()->join(' · ') }}
                                    </p>
                                @endif

                                {{-- Fiche count + initiative context --}}
                                @php
                                    $initiativeTitles = $contributor->fiches->pluck('initiative.title')->filter()->unique()->values();
                                @endphp
                                <div class="meta-group mt-2.5" style="gap: 0.5rem; font-size: var(--text-small);">
                                    <span class="meta-item whitespace-nowrap shrink-0 !font-semibold" style="color: var(--color-primary);">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                        {{ $contributor->fiches_count }}&nbsp;{{ $contributor->fiches_count === 1 ? 'fiche' : 'fiches' }}
                                    </span>
                                    @if($initiativeTitles->isNotEmpty())
                                        <span class="text-meta truncate" style="font-size: var(--text-small);">
                                            {{ $initiativeTitles->take(2)->join(', ') }}{{ $initiativeTitles->count() > 2 ? ' ...' : '' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-10">
                    {{ $contributors->links() }}
                </div>
            @endif
        </div>
    </section>

    {{-- CTA for guests --}}
    @guest
        <section class="relative bg-[var(--color-bg-cream)] border-t border-[var(--color-border-light)] overflow-hidden">
            {{-- Decorative diamond shape --}}
            <div class="absolute -right-8 top-1/2 -translate-y-1/2 opacity-[0.04] pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-64 h-64" viewBox="0 0 100 100">
                    <path d="M20,5 L2,36 L50,97 L98,36 L80,5 L50,22 Z" fill="var(--color-primary)"/>
                </svg>
            </div>

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
