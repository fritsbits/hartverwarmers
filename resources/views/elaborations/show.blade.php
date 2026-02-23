<x-layout :title="$elaboration->title">
    <article class="max-w-6xl mx-auto px-6 py-12">
        <!-- Breadcrumb -->
        <flux:breadcrumbs class="mb-6">
            <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('initiatives.index') }}">Initiatieven</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('initiatives.show', $initiative) }}">{{ $initiative->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $elaboration->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <!-- Title -->
        <header class="mb-8">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <h1 class="text-5xl">{{ $elaboration->title }}</h1>
                @if($elaboration->has_diamond)
                    <span class="diamond-indicator">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12l-8.954 8.955a1.126 1.126 0 01-1.591 0L2.25 12z" />
                        </svg>
                        Diamantje
                    </span>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach($elaboration->tags as $tag)
                    <flux:badge variant="outline">{{ $tag->name }}</flux:badge>
                @endforeach
            </div>

            {{-- Likes count --}}
            @if($elaboration->likes_count > 0)
                <flux:text class="mt-3 text-sm text-[var(--color-text-secondary)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                    {{ $elaboration->likes_count }} {{ $elaboration->likes_count === 1 ? 'like' : 'likes' }}
                </flux:text>
            @endif
        </header>

        <!-- Contributor block -->
        @if($elaboration->user)
            <flux:card class="mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-lg font-semibold shrink-0">
                        {{ substr($elaboration->user->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold">{{ $elaboration->user->name }}</p>
                        @if($elaboration->user->function_title)
                            <flux:text class="text-sm text-[var(--color-text-secondary)]">{{ $elaboration->user->function_title }}</flux:text>
                        @endif
                        @if($elaboration->user->organisation)
                            <flux:text class="text-sm text-[var(--color-text-secondary)]">{{ $elaboration->user->organisation->name }}</flux:text>
                        @endif
                    </div>
                </div>
            </flux:card>
        @endif

        <!-- Actions -->
        <div class="flex gap-4 mb-8">
            <flux:button variant="primary" href="{{ route('elaborations.print', [$initiative, $elaboration]) }}" target="_blank" icon="printer">
                Print
            </flux:button>
            @auth
                <form action="{{ route('elaborations.bookmark', $elaboration) }}" method="POST">
                    @csrf
                    @php $isBookmarked = auth()->user()->hasBookmarked($elaboration); @endphp
                    <flux:button type="submit" :variant="$isBookmarked ? 'primary' : 'ghost'" icon="bookmark">
                        {{ $isBookmarked ? 'Gebookmarkt' : 'Bookmark' }}
                    </flux:button>
                </form>
            @endauth
        </div>

        <!-- Description -->
        @if($elaboration->description)
            <div class="prose prose-lg max-w-none">
                {!! $elaboration->description !!}
            </div>
        @endif

        <!-- Practical Tips -->
        @if($elaboration->practical_tips)
            <div class="mt-8">
                <flux:heading size="lg" class="mb-4">Praktische tips</flux:heading>
                <div class="prose max-w-none">
                    {!! $elaboration->practical_tips !!}
                </div>
            </div>
        @endif

        <!-- Fiche Details -->
        @if($elaboration->fiche)
            <flux:card class="mt-12">
                <flux:heading size="lg" class="mb-4">Praktische info</flux:heading>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @if($elaboration->fiche['duration'] ?? null)
                        <div>
                            <flux:text class="text-sm text-[var(--color-text-secondary)]">Duur</flux:text>
                            <p class="font-medium">{{ $elaboration->fiche['duration'] }}</p>
                        </div>
                    @endif

                    @if($elaboration->fiche['group_size'] ?? null)
                        <div>
                            <flux:text class="text-sm text-[var(--color-text-secondary)]">Groepsgrootte</flux:text>
                            <p class="font-medium">{{ $elaboration->fiche['group_size'] }}</p>
                        </div>
                    @endif

                    @if($elaboration->fiche['materials'] ?? null)
                        <div class="col-span-2">
                            <flux:text class="text-sm text-[var(--color-text-secondary)]">Materiaal</flux:text>
                            <p class="font-medium">{{ $elaboration->fiche['materials'] }}</p>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endif

        <!-- Files -->
        @if($elaboration->files->isNotEmpty())
            <section class="mt-12">
                <flux:heading size="lg" class="mb-4">Bestanden</flux:heading>
                <div class="space-y-2">
                    @foreach($elaboration->files as $file)
                        <flux:card class="flex items-center gap-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <div class="flex-1">
                                <p class="font-medium">{{ $file->original_name }}</p>
                                @if($file->size)
                                    <flux:text class="text-sm text-[var(--color-text-secondary)]">{{ number_format($file->size / 1024, 0) }} KB</flux:text>
                                @endif
                            </div>
                            <flux:button variant="ghost" href="{{ Storage::url($file->path) }}" target="_blank" icon="arrow-down-tray" size="sm">
                                Download
                            </flux:button>
                        </flux:card>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Related Elaborations -->
        @if($relatedElaborations->isNotEmpty())
            <section class="mt-12 border-t border-[var(--color-border-light)] pt-8">
                <flux:heading size="lg" class="mb-6">Gerelateerde uitwerkingen</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($relatedElaborations as $related)
                        <x-elaboration-card :elaboration="$related" />
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Comments Section -->
        <section class="mt-12 border-t border-[var(--color-border-light)] pt-8">
            <flux:heading size="lg" class="mb-6">Reacties ({{ $elaboration->comments->count() }})</flux:heading>

            @auth
                <form action="{{ route('elaborations.comment', $elaboration) }}" method="POST" class="mb-8">
                    @csrf
                    <flux:textarea
                        name="body"
                        placeholder="Deel je ervaring of tip..."
                        rows="3"
                        required
                    >{{ old('body') }}</flux:textarea>
                    @error('body')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <div class="mt-2">
                        <flux:button type="submit" variant="primary" size="sm">Plaats reactie</flux:button>
                    </div>
                </form>
            @else
                @if(Route::has('login'))
                    <flux:callout class="mb-8">
                        <flux:link href="{{ route('login') }}">Log in</flux:link> om een reactie te plaatsen.
                    </flux:callout>
                @endif
            @endauth

            @forelse($elaboration->comments as $comment)
                <div class="flex gap-4 mb-6">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center font-semibold shrink-0">
                        {{ substr($comment->user->name ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <div class="font-medium">{{ $comment->user->name ?? 'Anoniem' }}</div>
                        <flux:text class="text-sm text-[var(--color-text-secondary)]">{{ $comment->created_at->diffForHumans() }}</flux:text>
                        <p class="mt-2">{{ $comment->body }}</p>
                    </div>
                </div>
            @empty
                <flux:text class="text-[var(--color-text-secondary)]">Nog geen reacties. Wees de eerste!</flux:text>
            @endforelse
        </section>
    </article>
</x-layout>
