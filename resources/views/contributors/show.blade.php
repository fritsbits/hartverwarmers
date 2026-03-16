<x-layout :title="$contributor->full_name" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-12">
            <flux:breadcrumbs class="mb-8">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('contributors.index') }}">Bijdragers</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $contributor->full_name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            @php $isOwnProfile = auth()->id() === $contributor->id; @endphp

            <div class="flex flex-col md:flex-row gap-8 items-start">
                <div class="relative">
                    <x-user-avatar :user="$contributor" size="2xl" class="ring-4 ring-white shadow-md" />
                    @if($isOwnProfile && ! $contributor->avatar_path)
                        <a href="{{ route('profile.show') }}" class="absolute bottom-1 right-1 w-8 h-8 rounded-full bg-white border border-[var(--color-border-light)] shadow-sm flex items-center justify-center text-[var(--color-text-secondary)] hover:text-[var(--color-primary)] hover:border-[var(--color-primary)] transition-colors" title="Voeg een foto toe">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                        </a>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <span class="section-label">Bijdrager</span>
                    <h1 class="text-3xl md:text-5xl mt-1">{{ $contributor->full_name }}</h1>

                    @php $metaParts = collect([$contributor->function_title, $contributor->organisation])->filter(); @endphp
                    <p class="text-lg text-[var(--color-text-secondary)] font-light mt-2">
                        @if($metaParts->isNotEmpty())
                            {{ $metaParts->join(' · ') }} · Lid sinds {{ $contributor->created_at->format('Y') }}
                        @elseif($isOwnProfile)
                            <a href="{{ route('profile.show') }}" class="text-[var(--color-primary)] hover:underline">Voeg je functie en organisatie toe</a> · Lid sinds {{ $contributor->created_at->format('Y') }}
                        @else
                            Lid sinds {{ $contributor->created_at->format('Y') }}
                        @endif
                    </p>

                    <p class="mt-4 text-lg">
                        <span class="font-heading font-bold text-2xl text-[var(--color-text-primary)]">{{ $stats['fiches_count'] }}</span>
                        <span class="text-[var(--color-text-secondary)]">{{ $stats['fiches_count'] === 1 ? 'fiche' : 'fiches' }}</span>
                        @if($stats['initiative_count'] > 1)
                            <span class="text-[var(--color-text-secondary)]">in</span>
                            <span class="font-heading font-bold text-2xl text-[var(--color-text-primary)]">{{ $stats['initiative_count'] }}</span>
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
                    @elseif($isOwnProfile)
                        <a href="{{ route('profile.show') }}" class="mt-4 block text-[var(--color-text-secondary)] font-light italic hover:text-[var(--color-primary)] transition-colors max-w-2xl">
                            Laat andere hartverwarmers weten wie jij bent — <span class="text-[var(--color-primary)] not-italic font-normal">voeg een bio toe</span>
                        </a>
                    @endif

                    {{-- Social links --}}
                    @if($contributor->website || $contributor->linkedin)
                        <div class="flex flex-wrap items-center gap-3 mt-5">
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

                    {{-- Admin: impersonate --}}
                    @auth
                        @if(auth()->user()->isAdmin() && !$isOwnProfile)
                            <form method="POST" action="{{ route('admin.impersonate.start', $contributor) }}" class="mt-5">
                                @csrf
                                <button type="submit" class="btn-pill text-xs">
                                    Bekijk als deze gebruiker
                                </button>
                            </form>
                        @endif
                    @endauth

                </div>
            </div>
        </div>
    </section>

    {{-- Fiches --}}
    @if($fiches->isNotEmpty())
        <section>
            <div class="max-w-3xl mx-auto px-6 py-12">
                <div class="space-y-2">
                    @foreach($fiches as $fiche)
                        <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="fiche-list-item">
                            <x-fiche-icon :fiche="$fiche" class="fiche-list-icon" />
                            <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-body font-semibold text-lg text-[var(--color-text-primary)] truncate">{{ $fiche->title }}</span>
                                    @if($fiche->featured_month)
                                        <span class="featured-badge">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd" /></svg>
                                            Fiche van de maand
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-[var(--color-text-secondary)]">{{ $fiche->initiative?->title }} · {{ $fiche->created_at->translatedFormat('M Y') }}</span>
                            </div>
                            <span class="flex items-center gap-2.5 shrink-0">
                                <span class="fiche-list-kudos {{ $fiche->kudos_count > 0 ? 'fiche-list-kudos-active' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/></svg>
                                    {{ $fiche->kudos_count }}
                                </span>
                                <span class="fiche-list-kudos {{ $fiche->comments_count > 0 ? 'fiche-list-kudos-active' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 0 0 6 21.75a6.721 6.721 0 0 0 3.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-8.25S17.322 4.5 12 4.5 2.25 8.47 2.25 12.75c0 2.534 1.221 4.745 3.065 6.232-.097.99-.616 2.048-1.395 2.795a.684.684 0 0 0 .884.867Z" clip-rule="evenodd"/></svg>
                                    {{ $fiche->comments_count }}
                                </span>
                                <span class="fiche-list-kudos {{ $fiche->bookmarks_count > 0 ? 'fiche-list-kudos-active' : '' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd"/></svg>
                                    {{ $fiche->bookmarks_count }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>

                {{-- Own profile: add fiche CTA --}}
                @if($isOwnProfile)
                    <a href="{{ route('fiches.create') }}" class="group flex items-center gap-3 mt-4 px-5 py-3.5 rounded-xl border border-dashed border-[var(--color-border-light)] hover:border-[var(--color-primary)] hover:bg-[var(--color-bg-cream)] transition-all no-underline">
                        <span class="w-12 h-12 rounded-full bg-[var(--color-bg-accent-light)] text-[var(--color-primary)] flex items-center justify-center group-hover:bg-[var(--color-primary)] group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </span>
                        <span class="text-sm text-[var(--color-text-secondary)] group-hover:text-[var(--color-primary)] transition-colors">Deel nog een activiteit</span>
                    </a>
                @endif
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
