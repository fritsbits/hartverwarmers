<x-layout :title="$initiative->title">

    {{-- Zone 1 — Header --}}
    <div class="max-w-6xl mx-auto px-6 pt-8">
        <div class="flex items-center justify-between">
            <flux:breadcrumbs class="mb-0">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('initiatives.index') }}">Alle initiatieven</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $initiative->title }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            @auth
                @if(auth()->user()->isAdmin())
                    <flux:modal.trigger name="delete-initiative">
                        <flux:button variant="danger" size="sm" icon="trash">Verwijderen</flux:button>
                    </flux:modal.trigger>
                @endif
            @endauth
        </div>
    </div>

    @auth
        @if(auth()->user()->isAdmin())
            <flux:modal name="delete-initiative" class="max-w-md">
                <div class="space-y-4">
                    <flux:heading size="lg">Initiatief verwijderen?</flux:heading>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        Weet je zeker dat je <strong>{{ $initiative->title }}</strong> wilt verwijderen? Dit initiatief en alle bijbehorende fiches worden verborgen.
                    </p>
                    <div class="flex gap-3 justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost">Annuleren</flux:button>
                        </flux:modal.close>
                        <form action="{{ route('initiatives.destroy', $initiative) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <flux:button type="submit" variant="danger">Verwijderen</flux:button>
                        </form>
                    </div>
                </div>
            </flux:modal>
        @endif
    @endauth

    <div class="max-w-6xl mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            {{-- Left column --}}
            <div class="lg:col-span-3">
                <span class="section-label section-label-hero">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12l-8.954 8.955a1.126 1.126 0 01-1.591 0L2.25 12z" />
                    </svg>
                    Initiatief
                </span>

                <h1 class="text-5xl sm:text-6xl mt-1 mb-4">{{ $initiative->title }}</h1>

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

                @if($initiative->fiches->isNotEmpty() || $initiative->comments->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[var(--color-text-secondary)]">
                        @if($initiative->fiches->isNotEmpty())
                            <span class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                {{ $initiative->fiches->count() }} {{ $initiative->fiches->count() === 1 ? 'fiche' : 'fiches' }}
                            </span>
                        @endif
                        @if($initiative->comments->isNotEmpty())
                            <span class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                </svg>
                                {{ $initiative->comments->count() }} keer {{ $initiative->comments->count() === 1 ? 'ervaring' : 'ervaringen' }} gedeeld
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right column: Image --}}
            <div class="lg:col-span-2">
                @if($initiative->image)
                    <img src="{{ $initiative->image }}" alt="{{ $initiative->title }}" class="w-full rounded-xl object-cover aspect-video">
                @else
                    <div class="w-full rounded-xl bg-[var(--color-bg-cream)] aspect-video flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Zone 2 — Fiches --}}
    <div class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Fiches
            </span>
            <h2 class="text-3xl mt-1 mb-2">
                {{ $initiative->fiches->count() }} {{ $initiative->fiches->count() === 1 ? 'fiche' : 'fiches' }} door collega's
            </h2>

            @if($initiative->fiches->isEmpty())
                <p class="text-[var(--color-text-secondary)]">Nog geen fiches voor dit initiatief.</p>
            @else
                @php
                    $diamondFiche = $initiative->fiches->firstWhere('has_diamond', true);
                    $otherFiches = $initiative->fiches->reject(fn ($e) => $diamondFiche && $e->id === $diamondFiche->id);
                    $compactLimit = $diamondFiche ? 4 : 5;
                    $visibleOtherFiches = $otherFiches->take($compactLimit);
                    $totalFiches = $initiative->fiches->count();
                @endphp

                {{-- Featured diamond fiche --}}
                @if($diamondFiche)
                    <div class="mb-8 mt-6">
                        <flux:card class="overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
                            <div class="flex items-center gap-2 mb-2">
                                <x-diamond-badge />
                            </div>
                            <a href="{{ route('fiches.show', [$initiative, $diamondFiche]) }}" class="block">
                                <flux:heading size="lg">{{ $diamondFiche->title }}</flux:heading>
                                @if($diamondFiche->description)
                                    <flux:text class="mt-2 line-clamp-2">
                                        {{ Str::limit(strip_tags($diamondFiche->description), 150) }}
                                    </flux:text>
                                @endif
                                @if($diamondFiche->tags->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-3">
                                        @foreach($diamondFiche->tags->take(3) as $tag)
                                            <flux:badge size="sm" color="zinc">{{ $tag->name }}</flux:badge>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="flex items-center justify-between mt-4 pt-3 border-t border-[var(--color-border-light)]">
                                    <div class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                                        @if($diamondFiche->user)
                                            <div class="w-6 h-6 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-xs font-semibold shrink-0">
                                                {{ substr($diamondFiche->user->first_name, 0, 1) }}
                                            </div>
                                            <span>{{ $diamondFiche->user->full_name }}</span>
                                            @if($diamondFiche->user->organisation)
                                                <span class="text-[var(--color-text-secondary)]">&middot; {{ $diamondFiche->user->organisation }}</span>
                                            @endif
                                        @endif
                                    </div>
                                    <span class="cta-link text-sm">Bekijk</span>
                                </div>
                            </a>
                        </flux:card>
                    </div>
                @endif

                {{-- Other fiches as compact list rows --}}
                @if($visibleOtherFiches->isNotEmpty())
                    <div class="mt-4">
                        @foreach($visibleOtherFiches as $fiche)
                            <a href="{{ route('fiches.show', [$initiative, $fiche]) }}" class="flex items-center gap-4 py-4 border-b border-[var(--color-border-light)] hover:bg-white/50 transition-colors -mx-2 px-2 rounded group">
                                <div class="flex-1 min-w-0">
                                    <span class="font-semibold text-[var(--color-primary)]">{{ $fiche->title }}</span>
                                </div>
                                <div class="text-sm text-[var(--color-text-secondary)] shrink-0 hidden sm:flex items-center gap-1">
                                    @if($fiche->user)
                                        {{ $fiche->user->full_name }}
                                    @endif
                                </div>
                                @if($fiche->bookmarks_count > 0)
                                    <span class="flex items-center gap-1 text-sm text-[var(--color-primary)] shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                                        </svg>
                                        {{ $fiche->bookmarks_count }}
                                    </span>
                                @endif
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[var(--color-text-secondary)] group-hover:text-[var(--color-primary)] transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        @endforeach
                    </div>

                    @if($totalFiches > 5)
                        <div class="mt-6 text-center">
                            <a href="{{ route('initiatives.show', $initiative) }}#fiches" class="cta-link">
                                Alle {{ $totalFiches }} fiches tonen
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
        <h2 class="text-3xl mt-1 mb-4">Vertel, hoe ging het bij jullie?</h2>

        {{-- Existing comments --}}
        @if($initiative->comments->isNotEmpty())
            @foreach($initiative->comments as $comment)
                <div class="flex gap-4 py-4 {{ !$loop->last ? 'border-b border-[var(--color-border-light)]' : '' }}">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center font-semibold shrink-0">
                        {{ substr($comment->user->first_name ?? 'A', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="text-sm">
                                <span class="font-semibold">{{ $comment->user->full_name ?? 'Anoniem' }}</span>
                                @if($comment->user?->organisation)
                                    <span class="text-[var(--color-text-secondary)]"> &middot; {{ $comment->user->organisation }}</span>
                                @endif
                            </div>
                            <span class="text-sm text-[var(--color-text-secondary)] shrink-0">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-2">{{ $comment->body }}</p>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Comment form --}}
        @auth
            <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 {{ $initiative->comments->isNotEmpty() ? 'mt-8' : '' }}">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                        {{ substr(auth()->user()->first_name, 0, 1) }}
                    </div>
                    <span class="text-sm font-medium">{{ auth()->user()->full_name }}</span>
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
                    <div class="mt-3 flex items-center {{ $initiative->comments->isEmpty() ? 'justify-between' : 'justify-end' }}">
                        @if($initiative->comments->isEmpty())
                            <span class="text-sm text-[var(--color-text-secondary)]">Wees de eerste die een ervaring deelt.</span>
                        @endif
                        <flux:button type="submit" variant="primary" size="sm">Deel je ervaring</flux:button>
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

    {{-- Zone 4 — Related Initiatives --}}
    @if($relatedInitiatives->isNotEmpty())
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-3xl mb-8">Gerelateerde initiatieven</h2>

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
