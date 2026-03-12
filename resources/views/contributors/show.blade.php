<x-layout :title="$contributor->full_name" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-12">
            <flux:breadcrumbs class="mb-8">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('contributors.index') }}">Bijdragers</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $contributor->full_name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex flex-col md:flex-row gap-8 items-start">
                <x-user-avatar :user="$contributor" size="2xl" class="ring-4 ring-white shadow-md" />

                <div class="flex-1 min-w-0">
                    <span class="section-label">Bijdrager</span>
                    <h1 class="text-3xl md:text-5xl mt-1">{{ $contributor->full_name }}</h1>

                    @php $metaParts = collect([$contributor->function_title, $contributor->organisation])->filter(); @endphp
                    <p class="text-lg text-[var(--color-text-secondary)] font-light mt-2">
                        @if($metaParts->isNotEmpty())
                            {{ $metaParts->join(' · ') }} · Lid sinds {{ $contributor->created_at->format('Y') }}
                        @else
                            Lid sinds {{ $contributor->created_at->format('Y') }}
                        @endif
                    </p>

                    <p class="mt-4 text-lg">
                        <span class="font-heading font-bold text-2xl text-[var(--color-text-primary)]">{{ $stats['fiches_count'] }}</span>
                        <span class="text-[var(--color-text-secondary)]">{{ $stats['fiches_count'] === 1 ? 'fiche' : 'fiches' }}</span>
                        @if($fichesByInitiative->count() > 1)
                            <span class="text-[var(--color-text-secondary)]">in</span>
                            <span class="font-heading font-bold text-2xl text-[var(--color-text-primary)]">{{ $fichesByInitiative->count() }}</span>
                            <span class="text-[var(--color-text-secondary)]">initiatieven</span>
                        @endif
                        @if($stats['kudos_total'] > 0)
                            <span class="text-[var(--color-border-light)] mx-1">·</span>
                            <x-icon-heart class="w-5 h-5 text-[var(--color-primary)] inline -mt-0.5" />
                            <span class="font-heading font-bold text-2xl text-[var(--color-text-primary)]">{{ $stats['kudos_total'] }}</span>
                            <span class="text-[var(--color-text-secondary)]">kudos</span>
                        @endif
                    </p>

                    @if($contributor->bio)
                        <div class="mt-4 text-[var(--color-text-secondary)] font-light leading-relaxed line-clamp-3 max-w-2xl">
                            {!! $contributor->bio !!}
                        </div>
                    @endif

                    {{-- Specialization pills + social links --}}
                    @php $topInitiatives = $fichesByInitiative->map->count()->sortDesc()->take(3); @endphp
                    @if($topInitiatives->count() > 1 || $contributor->website || $contributor->linkedin)
                        <div class="flex flex-wrap items-center gap-3 mt-5">
                            @if($topInitiatives->count() > 1)
                                @foreach($topInitiatives as $title => $count)
                                    @php $colorIndex = $initiativeColors[$title] ?? 0; @endphp
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium initiative-color-{{ $colorIndex }}" style="background: var(--initiative-bg); color: var(--initiative-color);">
                                        <span class="w-2 h-2 rounded-full" style="background: var(--initiative-color);"></span>
                                        {{ $title }}
                                    </span>
                                @endforeach
                                @if($fichesByInitiative->count() > 3)
                                    <span class="text-sm text-[var(--color-text-secondary)]">+{{ $fichesByInitiative->count() - 3 }} meer</span>
                                @endif
                            @endif

                            @if($contributor->website)
                                <a href="{{ $contributor->website }}" target="_blank" rel="noopener" class="meta-item hover:text-[var(--color-primary)] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                                    Website
                                </a>
                            @endif
                            @if($contributor->linkedin)
                                <a href="{{ $contributor->linkedin }}" target="_blank" rel="noopener" class="meta-item hover:text-[var(--color-primary)] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                    LinkedIn
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Fiches grouped by initiative --}}
    @if($fichesByInitiative->isNotEmpty())
        <section>
            <div class="max-w-6xl mx-auto px-6 py-12">
                {{-- Mobile: initiative pill nav --}}
                @if($fichesByInitiative->count() > 2)
                    <div class="lg:hidden relative mb-8">
                        <div class="flex gap-2 overflow-x-auto pb-3 -mx-6 px-6 pr-12 scrollbar-hide">
                            @foreach($fichesByInitiative as $initiativeTitle => $fiches)
                                @php $colorIndex = $initiativeColors[$initiativeTitle] ?? 0; @endphp
                                <a href="#initiative-{{ Str::slug($initiativeTitle) }}" class="initiative-color-{{ $colorIndex }} inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap no-underline shrink-0" style="background: var(--initiative-bg); color: var(--initiative-color);">
                                    <span class="w-2 h-2 rounded-full" style="background: var(--initiative-color);"></span>
                                    {{ $initiativeTitle }}
                                </a>
                            @endforeach
                        </div>
                        <div class="absolute top-0 right-0 bottom-3 w-12 bg-gradient-to-l from-white to-transparent pointer-events-none"></div>
                    </div>
                @endif

                <div class="lg:flex gap-8">
                    {{-- Sidebar nav (desktop, only when 3+ initiatives) --}}
                    @if($fichesByInitiative->count() > 2)
                        <nav class="hidden lg:block w-52 shrink-0">
                            <div class="sticky top-8 space-y-1">
                                @foreach($fichesByInitiative as $initiativeTitle => $fiches)
                                    @php $colorIndex = $initiativeColors[$initiativeTitle] ?? 0; @endphp
                                    <a href="#initiative-{{ Str::slug($initiativeTitle) }}" class="initiative-color-{{ $colorIndex }} flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm no-underline text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle)] transition-colors">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: var(--initiative-color);"></span>
                                        <span class="flex-1 truncate">{{ $initiativeTitle }}</span>
                                        <span class="text-xs opacity-50">{{ $fiches->count() }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </nav>
                    @endif

                    {{-- Initiative sections --}}
                    <div class="flex-1 min-w-0 space-y-6">
                        @foreach($fichesByInitiative as $initiativeTitle => $fiches)
                            @php
                                $colorIndex = $initiativeColors[$initiativeTitle] ?? 0;
                                $initiative = $fiches->first()?->initiative;
                            @endphp

                            <div id="initiative-{{ Str::slug($initiativeTitle) }}" class="initiative-color-{{ $colorIndex }} initiative-section">
                                <div class="initiative-section-header">
                                    @if($initiative)
                                        <a href="{{ route('initiatives.show', $initiative) }}" class="text-lg font-heading font-bold no-underline truncate hover:underline" style="color: var(--initiative-color);">{{ $initiativeTitle }}</a>
                                    @else
                                        <h3 class="text-lg truncate" style="color: var(--initiative-color);">{{ $initiativeTitle }}</h3>
                                    @endif
                                    @if($fiches->count() > 1)
                                        <span class="text-sm font-body font-light text-[var(--color-text-secondary)]">{{ $fiches->count() }}</span>
                                    @endif
                                </div>

                                <div class="divide-y divide-[var(--color-border-light)]">
                                    @foreach($fiches->sortByDesc('created_at') as $fiche)
                                        <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="group flex items-center gap-4 px-5 py-3 no-underline text-inherit hover:bg-[var(--color-bg-cream)]/50 transition-colors">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-heading font-bold text-[15px] group-hover:text-[var(--color-primary)] transition-colors">{{ $fiche->title }}</span>
                                                    @if($fiche->featured_month)
                                                        <span class="featured-badge">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" /></svg>
                                                            Fiche van de maand
                                                        </span>
                                                    @endif
                                                    <span class="text-xs text-[var(--color-text-secondary)]">{{ $fiche->created_at->translatedFormat('M Y') }}</span>
                                                </div>
                                            </div>
                                            @if($fiche->kudos_count > 0)
                                                <span class="flex items-center gap-1 text-xs text-[var(--color-primary)] shrink-0">
                                                    <x-icon-heart class="w-3.5 h-3.5" />
                                                    {{ $fiche->kudos_count }}
                                                </span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Related Contributors --}}
    @if($relatedContributors->isNotEmpty())
        <section class="bg-[var(--color-bg-cream)] border-t border-[var(--color-border-light)]">
            <div class="max-w-6xl mx-auto px-6 py-12">
                <span class="section-label">Community</span>
                <h2 class="mb-6">Collega-bijdragers</h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 max-w-3xl">
                    @foreach($relatedContributors as $related)
                        <a href="{{ route('contributors.show', $related) }}" class="group flex flex-col items-center text-center p-5 rounded-2xl bg-white border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all no-underline text-inherit">
                            <x-user-avatar :user="$related" size="xl" class="mb-3" />
                            <h3 class="font-heading font-bold text-sm leading-snug group-hover:text-[var(--color-primary)] transition-colors">{{ $related->full_name }}</h3>
                            @if($related->shared_initiatives?->isNotEmpty())
                                <p class="text-xs text-[var(--color-text-secondary)] font-light mt-1.5">
                                    Deelt ook over {{ $related->shared_initiatives->take(2)->join(' & ') }}
                                </p>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    <a href="{{ route('contributors.index') }}" class="cta-link">Bekijk alle bijdragers</a>
                </div>
            </div>
        </section>
    @else
        <section class="border-t border-[var(--color-border-light)]">
            <div class="max-w-6xl mx-auto px-6 py-12">
                <a href="{{ route('contributors.index') }}" class="cta-link">Bekijk alle bijdragers</a>
            </div>
        </section>
    @endif
</x-layout>
