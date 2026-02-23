<x-layout :title="$initiative->title">

    {{-- Zone 1 — Header --}}
    <div class="max-w-6xl mx-auto px-6 pt-8">
        <flux:breadcrumbs class="mb-0">
            <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('initiatives.index') }}">Alle initiatieven</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $initiative->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="max-w-6xl mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            {{-- Left column --}}
            <div class="lg:col-span-3">
                <span class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12l-8.954 8.955a1.126 1.126 0 01-1.591 0L2.25 12z" />
                    </svg>
                    Initiatief
                </span>

                <h1 class="text-5xl sm:text-6xl mt-3 mb-4">{{ $initiative->title }}</h1>

                @if($initiative->description)
                    <div class="text-[var(--color-text-secondary)] text-2xl font-light mb-8">
                        {!! $initiative->description !!}
                    </div>
                @endif

                @if($initiative->content)
                    <div class="prose prose-lg max-w-none mb-6">
                        {!! $initiative->content !!}
                    </div>
                @endif

                @php
                    $nonGoalTags = $initiative->tags->filter(fn ($tag) => $tag->type !== 'goal');
                @endphp
                @if($nonGoalTags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach($nonGoalTags as $tag)
                            <flux:badge variant="outline">{{ $tag->name }}</flux:badge>
                        @endforeach
                    </div>
                @endif

                @if($contributorsCount > 0 || $initiative->elaborations->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[var(--color-text-secondary)]">
                        @if($contributorsCount > 0)
                            <span class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-1.053M18 8.625a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM6.75 8.625a2.625 2.625 0 115.25 0 2.625 2.625 0 01-5.25 0z" />
                                </svg>
                                Uitgevoerd door {{ $contributorsCount }} {{ $contributorsCount === 1 ? 'begeleider' : 'begeleiders' }}
                            </span>
                        @endif
                        @if($initiative->elaborations->isNotEmpty())
                            <span class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                {{ $initiative->elaborations->count() }} {{ $initiative->elaborations->count() === 1 ? 'uitwerking beschikbaar' : 'uitwerkingen beschikbaar' }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right column: Image --}}
            <div class="lg:col-span-2">
                @if($initiative->image)
                    <img src="{{ $initiative->image }}" alt="{{ $initiative->title }}" class="w-full rounded-xl object-cover aspect-square">
                    @if($initiative->creator)
                        <div class="flex items-center gap-2 mt-2 text-sm text-[var(--color-text-secondary)]">
                            <div class="w-5 h-5 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-[10px] font-semibold shrink-0">
                                {{ substr($initiative->creator->name, 0, 1) }}
                            </div>
                            <span>
                                Foto door {{ $initiative->creator->name }}
                                @if($initiative->creator->organisation)
                                    &middot; {{ $initiative->creator->organisation->name }}
                                @endif
                            </span>
                        </div>
                    @endif
                @else
                    <div class="w-full rounded-xl bg-[var(--color-bg-cream)] aspect-square flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Zone 2 — Elaborations --}}
    <div class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-2xl mb-2">
                {{ $initiative->elaborations->count() }} {{ $initiative->elaborations->count() === 1 ? 'uitwerking' : 'uitwerkingen' }} door collega's
            </h2>

            @if($initiative->elaborations->isEmpty())
                <p class="text-[var(--color-text-secondary)]">Nog geen uitwerkingen voor dit initiatief.</p>
            @else
                @php
                    $diamondElaboration = $initiative->elaborations->firstWhere('has_diamond', true);
                    $otherElaborations = $initiative->elaborations->reject(fn ($e) => $diamondElaboration && $e->id === $diamondElaboration->id);
                @endphp

                {{-- Featured diamond elaboration --}}
                @if($diamondElaboration)
                    <div class="mb-8 mt-6">
                        <a href="{{ route('elaborations.show', [$initiative, $diamondElaboration]) }}" class="block cursor-pointer">
                            <flux:card class="overflow-hidden border-2 hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200" style="border-color: var(--color-primary)">
                                <div class="flex justify-end mb-3">
                                    <span class="diamond-indicator">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12l-8.954 8.955a1.126 1.126 0 01-1.591 0L2.25 12z" />
                                        </svg>
                                        Diamantje
                                    </span>
                                </div>
                                @if($diamondElaboration->user)
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                                            {{ substr($diamondElaboration->user->name, 0, 1) }}
                                        </div>
                                        <div class="text-sm">
                                            <span class="font-medium">{{ $diamondElaboration->user->name }}</span>
                                            @if($diamondElaboration->user->organisation)
                                                <span class="text-[var(--color-text-secondary)]"> &middot; {{ $diamondElaboration->user->organisation->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <flux:heading size="lg">{{ $diamondElaboration->title }}</flux:heading>
                                @if($diamondElaboration->description)
                                    <flux:text class="mt-2 line-clamp-3">
                                        {{ Str::limit(strip_tags($diamondElaboration->description), 200) }}
                                    </flux:text>
                                @endif
                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex items-center gap-3 text-sm text-[var(--color-text-secondary)]">
                                        @if($diamondElaboration->tags->isNotEmpty())
                                            @foreach($diamondElaboration->tags->take(3) as $tag)
                                                <flux:badge size="sm" color="zinc">{{ $tag->name }}</flux:badge>
                                            @endforeach
                                        @endif
                                        @if($diamondElaboration->likes_count > 0)
                                            <span class="flex items-center gap-1 text-[var(--color-primary)]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                                                </svg>
                                                {{ $diamondElaboration->likes_count }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="cta-link">Bekijk</span>
                                </div>
                            </flux:card>
                        </a>
                    </div>
                @endif

                {{-- Other elaborations as compact list rows --}}
                @if($otherElaborations->isNotEmpty())
                    <div class="mt-4">
                        @foreach($otherElaborations as $elaboration)
                            <a href="{{ route('elaborations.show', [$initiative, $elaboration]) }}" class="flex items-center gap-4 py-4 border-b border-[var(--color-border-light)] hover:bg-white/50 transition-colors -mx-2 px-2 rounded">
                                <div class="flex-1 min-w-0">
                                    <span class="font-semibold text-[var(--color-text-primary)]">{{ $elaboration->title }}</span>
                                </div>
                                <div class="text-sm text-[var(--color-text-secondary)] shrink-0 hidden sm:flex items-center gap-1">
                                    @if($elaboration->user)
                                        {{ $elaboration->user->name }}
                                        @if($elaboration->user->organisation)
                                            &middot; {{ $elaboration->user->organisation->name }}
                                        @endif
                                    @endif
                                </div>
                                @if($elaboration->likes_count > 0)
                                    <span class="flex items-center gap-1 text-sm text-[var(--color-primary)] shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                                        </svg>
                                        {{ $elaboration->likes_count }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    @if($initiative->elaborations->count() > 1)
                        <div class="mt-6 text-center">
                            <a href="{{ route('initiatives.show', $initiative) }}#uitwerkingen" class="cta-link">
                                Alle {{ $initiative->elaborations->count() }} uitwerkingen tonen
                            </a>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </div>

    {{-- Zone 3 — Community Stories --}}
    <div class="max-w-6xl mx-auto px-6 py-12">
        <span class="section-label">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            Uit de praktijk
        </span>
        <h2 class="text-2xl mt-3 mb-2">Vertel, hoe ging het bij jullie?</h2>
        <p class="text-[var(--color-text-secondary)] mb-8">Collega-begeleiders delen hun ervaringen met {{ $initiative->title }}.</p>

        {{-- Existing comments --}}
        @forelse($initiative->comments as $comment)
            <div class="flex gap-4 py-4 {{ !$loop->last ? 'border-b border-[var(--color-border-light)]' : '' }}">
                <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center font-semibold shrink-0">
                    {{ substr($comment->user->name ?? 'A', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                        <div class="text-sm">
                            <span class="font-semibold">{{ $comment->user->name ?? 'Anoniem' }}</span>
                            @if($comment->user?->organisation)
                                <span class="text-[var(--color-text-secondary)]"> &middot; {{ $comment->user->organisation->name }}</span>
                            @endif
                        </div>
                        <span class="text-sm text-[var(--color-text-secondary)] shrink-0">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-2">{{ $comment->body }}</p>
                </div>
            </div>
        @empty
            <p class="text-[var(--color-text-secondary)] mb-6">Nog geen ervaringen gedeeld. Jouw verhaal kan de eerste zijn!</p>
        @endforelse

        {{-- Comment form --}}
        @auth
            <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 mt-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <span class="text-sm font-medium">{{ auth()->user()->name }}</span>
                </div>
                <form action="{{ route('initiatives.comment', $initiative) }}" method="POST">
                    @csrf
                    <textarea
                        name="body"
                        placeholder="Hoe ging het bij jullie? Wat viel op, wat werkte goed?"
                        rows="3"
                        required
                        class="w-full rounded-lg border border-[var(--color-border-light)] bg-white px-4 py-3 text-sm placeholder:text-[var(--color-text-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent resize-y"
                    >{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="btn-pill text-sm">Deel je ervaring</button>
                    </div>
                </form>
            </div>
        @else
            @if(Route::has('login'))
                <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 mt-8 text-center">
                    <a href="{{ route('login') }}" class="cta-link">Log in</a> om je ervaring te delen.
                </div>
            @endif
        @endauth
    </div>

    {{-- Zone 4 — DIAMANT profile --}}
    @if($diamantProfile->isNotEmpty())
        <div class="bg-[var(--color-bg-cream)]">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="flex items-center gap-3 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[var(--color-primary)] shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12l-8.954 8.955a1.126 1.126 0 01-1.591 0L2.25 12z" />
                    </svg>
                    <h2 class="text-2xl">Van {{ $initiative->title }} naar diamantje</h2>
                </div>

                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                        M
                    </div>
                    <div class="text-sm text-[var(--color-text-secondary)]">
                        Door <span class="font-medium text-[var(--color-text-primary)]">Maite Mallentjer</span>, pedagoog dagbesteding
                    </div>
                </div>

                @if($initiative->diamant_guidance && count($initiative->diamant_guidance) > 0)
                    <div class="space-y-4 max-w-3xl mt-8">
                        @foreach($diamantProfile as $facet)
                            <div class="flex items-start gap-4 p-4 rounded-xl {{ $facet['active'] ? 'bg-white' : '' }}">
                                <span class="{{ $facet['active'] ? 'diamant-badge-sm' : 'diamant-badge-sm-inactive' }}">
                                    {{ $facet['letter'] }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold">{{ $facet['keyword'] }}</span>
                                        @if($facet['active'])
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <span class="w-5 h-5 rounded-full border-2 border-[var(--color-border-light)] inline-block"></span>
                                        @endif
                                    </div>
                                    @if($facet['initiative_description'])
                                        <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $facet['initiative_description'] }}</p>
                                    @endif
                                    @if(! $facet['active'] && $facet['initiative_guidance'])
                                        <div class="mt-2 flex items-start gap-2 text-sm bg-[var(--color-bg-subtle)] rounded-lg p-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-accent)] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                                            </svg>
                                            <p class="text-[var(--color-text-secondary)]">{{ $facet['initiative_guidance'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-[var(--color-text-secondary)] max-w-2xl mt-2">
                        Het DIAMANT-kompas helpt je om activiteiten te bekijken vanuit zeven pedagogische doelen: Doen, Inclusief, Autonomie, Mensgericht, Anderen, Normalisatie en Talent.
                    </p>
                @endif

                <div class="border-t border-[var(--color-border-light)] mt-10 pt-6">
                    <a href="{{ route('goals.index') }}" class="cta-link">Meer over het DIAMANT-kompas</a>
                </div>
            </div>
        </div>
    @endif

    {{-- Zone 5 — Related Initiatives --}}
    @if($relatedInitiatives->isNotEmpty())
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-2xl mb-8">Gerelateerde initiatieven</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedInitiatives as $related)
                    <x-initiative-card :initiative="$related" />
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('initiatives.index') }}" class="cta-link">Alle initiatieven &rarr;</a>
            </div>
        </div>
    @endif

</x-layout>
