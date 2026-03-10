<x-layout :title="$contributor->full_name" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('contributors.index') }}">Bijdragers</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $contributor->full_name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex flex-col md:flex-row gap-6 items-start">
                {{-- Avatar --}}
                @if($contributor->avatar_path)
                    <img src="{{ $contributor->avatarUrl() }}" alt="" class="w-20 h-20 rounded-full object-cover ring-4 ring-white shadow-sm shrink-0">
                @else
                    <div class="w-20 h-20 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center text-3xl font-bold ring-4 ring-white shadow-sm shrink-0">
                        {{ mb_substr($contributor->first_name, 0, 1) }}
                    </div>
                @endif

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <span class="section-label section-label-hero">Bijdrager</span>
                    <h1 class="text-5xl mt-1">{{ $contributor->full_name }}</h1>

                    @if($contributor->function_title || $contributor->organisation)
                        <p class="text-lg text-[var(--color-text-secondary)] font-light mt-2">
                            {{ collect([$contributor->function_title, $contributor->organisation])->filter()->join(' · ') }}
                        </p>
                    @endif

                    {{-- Stats --}}
                    <div class="flex items-baseline gap-6 mt-5">
                        <div class="text-center">
                            <span class="block text-2xl font-heading font-bold text-[var(--color-primary)] leading-none">{{ $stats['fiches_count'] }}</span>
                            <span class="block text-xs text-[var(--color-text-secondary)] font-medium tracking-wide uppercase mt-1">{{ $stats['fiches_count'] === 1 ? 'fiche' : 'fiches' }}</span>
                        </div>
                        @if($stats['kudos_total'] > 0)
                            <div class="w-px h-6 bg-[var(--color-border-light)]"></div>
                            <div class="text-center">
                                <span class="block text-2xl font-heading font-bold text-[var(--color-primary)] leading-none">{{ $stats['kudos_total'] }}</span>
                                <span class="block text-xs text-[var(--color-text-secondary)] font-medium tracking-wide uppercase mt-1">kudos</span>
                            </div>
                        @endif
                        @if($stats['downloads_total'] > 0)
                            <div class="w-px h-6 bg-[var(--color-border-light)]"></div>
                            <div class="text-center">
                                <span class="block text-2xl font-heading font-bold text-[var(--color-primary)] leading-none">{{ $stats['downloads_total'] }}</span>
                                <span class="block text-xs text-[var(--color-text-secondary)] font-medium tracking-wide uppercase mt-1">downloads</span>
                            </div>
                        @endif
                    </div>

                    {{-- Social links --}}
                    @if($contributor->website || $contributor->linkedin)
                        <div class="flex items-center gap-4 mt-4">
                            @if($contributor->website)
                                <a href="{{ $contributor->website }}" target="_blank" rel="noopener" class="meta-item hover:text-[var(--color-primary)] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                    </svg>
                                    Website
                                </a>
                            @endif
                            @if($contributor->linkedin)
                                <a href="{{ $contributor->linkedin }}" target="_blank" rel="noopener" class="meta-item hover:text-[var(--color-primary)] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                    LinkedIn
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Bio --}}
            @if($contributor->bio)
                <div class="mt-8 max-w-3xl text-[var(--color-text-secondary)] font-light leading-relaxed">
                    {!! $contributor->bio !!}
                </div>
            @endif
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Fiches grouped by initiative --}}
    @if($fichesByInitiative->isNotEmpty())
        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <span class="section-label">Fiches</span>
                <h2 class="mb-8">Bijdragen van {{ $contributor->first_name }}</h2>

                <div class="space-y-12">
                    @foreach($fichesByInitiative as $initiativeTitle => $fiches)
                        <div>
                            <h3 class="text-xl mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[var(--color-primary)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                </svg>
                                {{ $initiativeTitle }}
                                <span class="text-sm font-body font-light text-[var(--color-text-secondary)]">({{ $fiches->count() }})</span>
                            </h3>

                            <div class="space-y-2">
                                @foreach($fiches as $fiche)
                                    <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="fiche-list-item">
                                        <div class="fiche-list-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <span class="font-heading font-bold text-[15px]">{{ $fiche->title }}</span>
                                            @if($fiche->tags->isNotEmpty())
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    @foreach($fiche->tags->take(3) as $tag)
                                                        <flux:badge size="sm" color="zinc">{{ $tag->name }}</flux:badge>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        @if($fiche->kudos_count > 0)
                                            <div class="fiche-list-kudos fiche-list-kudos-active">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                                                </svg>
                                                {{ $fiche->kudos_count }}
                                            </div>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Back to contributors --}}
                <div class="mt-12 pt-8 border-t border-[var(--color-border-light)]">
                    <a href="{{ route('contributors.index') }}" class="cta-link">Bekijk alle bijdragers</a>
                </div>
            </div>
        </section>
    @endif
</x-layout>
