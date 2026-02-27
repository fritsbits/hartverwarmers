<x-layout :title="$fiche->title" :full-width="true">
    <x-slot:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('initiatives.index') }}">Initiatieven</flux:breadcrumbs.item>
        <flux:breadcrumbs.item href="{{ route('initiatives.show', $initiative) }}">{{ $initiative->title }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $fiche->title }}</flux:breadcrumbs.item>
    </x-slot:breadcrumbs>

    <x-slot:headerActions>
        @auth
            @if(auth()->user()->isAdmin())
                <flux:modal.trigger name="delete-fiche">
                    <flux:button variant="danger" size="sm" icon="trash">Verwijderen</flux:button>
                </flux:modal.trigger>
            @endif
        @endauth
    </x-slot:headerActions>

    @auth
        @if(auth()->user()->isAdmin())
            <flux:modal name="delete-fiche" class="max-w-md">
                <div class="space-y-4">
                    <flux:heading size="lg">Fiche verwijderen?</flux:heading>
                    <p class="text-sm text-[var(--color-text-secondary)]">
                        Weet je zeker dat je <strong>{{ $fiche->title }}</strong> wilt verwijderen?
                    </p>
                    <div class="flex gap-3 justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost">Annuleren</flux:button>
                        </flux:modal.close>
                        <form action="{{ route('fiches.destroy', [$initiative, $fiche]) }}" method="POST">
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
        <span class="section-label section-label-hero">Fiche</span>

        <div class="flex flex-wrap items-center gap-3 mt-3 mb-4">
            <h1 class="text-5xl sm:text-6xl">{{ $fiche->title }}</h1>
            @if($fiche->has_diamond)
                <x-diamond-badge />
            @endif
        </div>

        @if($fiche->tags->isNotEmpty())
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach($fiche->tags as $tag)
                    <flux:badge variant="outline">{{ $tag->name }}</flux:badge>
                @endforeach
            </div>
        @endif

        @if($fiche->user)
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                    {{ substr($fiche->user->first_name, 0, 1) }}
                </div>
                <div class="text-sm">
                    <span class="font-semibold">{{ $fiche->user->full_name }}</span>
                    @if($fiche->user->function_title)
                        <span class="text-[var(--color-text-secondary)]"> &middot; {{ $fiche->user->function_title }}</span>
                    @endif
                    @if($fiche->user->organisation)
                        <span class="text-[var(--color-text-secondary)]"> &middot; {{ $fiche->user->organisation }}</span>
                    @endif
                </div>
            </div>
        @endif

        @if($fiche->bookmarks_count > 0 || $fiche->comments->isNotEmpty())
            <div class="meta-group mb-6">
                @if($fiche->bookmarks_count > 0)
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                        {{ $fiche->bookmarks_count }} keer als favoriet bewaard
                    </span>
                @endif
                @if($fiche->comments->isNotEmpty())
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                        {{ $fiche->comments->count() }} {{ $fiche->comments->count() === 1 ? 'reactie' : 'reacties' }}
                    </span>
                @endif
            </div>
        @endif

        <div class="flex flex-wrap gap-3">
            <flux:button variant="primary" href="{{ route('fiches.print', [$initiative, $fiche]) }}" target="_blank" icon="printer">
                Print
            </flux:button>
            @auth
                <form action="{{ route('fiches.bookmark', $fiche) }}" method="POST">
                    @csrf
                    @php $isBookmarked = auth()->user()->hasBookmarked($fiche); @endphp
                    <flux:button type="submit" :variant="$isBookmarked ? 'primary' : 'ghost'" icon="bookmark">
                        {{ $isBookmarked ? 'Favoriet' : 'Markeer als favoriet' }}
                    </flux:button>
                </form>
                @if(auth()->user()->isAdmin())
                    <form action="{{ route('fiches.toggleDiamond', [$initiative, $fiche]) }}" method="POST">
                        @csrf
                        <flux:button type="submit" :variant="$fiche->has_diamond ? 'primary' : 'ghost'" icon="sparkles">
                            {{ $fiche->has_diamond ? 'Diamantje' : 'Markeer als diamantje' }}
                        </flux:button>
                    </form>
                @endif
            @endauth
        </div>
    </div>

    {{-- Zone 2 — Content --}}
    <div class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                Inhoud
            </span>

            @if($fiche->description)
                <div class="prose prose-lg max-w-none mt-6">
                    {!! $fiche->description !!}
                </div>
            @endif

            @if($fiche->practical_tips)
                <div class="mt-10">
                    <h2 class="text-2xl mb-4">Praktische tips</h2>
                    <div class="prose max-w-none">
                        {!! $fiche->practical_tips !!}
                    </div>
                </div>
            @endif

            @if($fiche->materials)
                <div class="mt-10 bg-white rounded-xl p-6">
                    <h2 class="text-2xl mb-4">Praktische info</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @if($fiche->materials['duration'] ?? null)
                            <div>
                                <p class="text-sm text-[var(--color-text-secondary)]">Duur</p>
                                <p class="font-medium">{{ $fiche->materials['duration'] }}</p>
                            </div>
                        @endif

                        @if($fiche->materials['group_size'] ?? null)
                            <div>
                                <p class="text-sm text-[var(--color-text-secondary)]">Groepsgrootte</p>
                                <p class="font-medium">{{ $fiche->materials['group_size'] }}</p>
                            </div>
                        @endif

                        @if($fiche->materials['materials'] ?? null)
                            <div class="col-span-2">
                                <p class="text-sm text-[var(--color-text-secondary)]">Materiaal</p>
                                <p class="font-medium">{{ $fiche->materials['materials'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($fiche->files->isNotEmpty())
                <div class="mt-10">
                    <h2 class="text-2xl mb-4">Bestanden</h2>
                    <div class="space-y-2">
                        @foreach($fiche->files as $file)
                            <div class="flex items-center gap-4 bg-white rounded-xl p-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="font-medium">{{ $file->original_name }}</p>
                                    @if($file->size)
                                        <p class="text-sm text-[var(--color-text-secondary)]">{{ number_format($file->size / 1024, 0) }} KB</p>
                                    @endif
                                </div>
                                <flux:button variant="ghost" href="{{ Storage::url($file->path) }}" target="_blank" icon="arrow-down-tray" size="sm">
                                    Download
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Zone 3 — Comments --}}
    <div class="max-w-6xl mx-auto px-6 py-12">
        <span class="section-label">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
            Reacties
        </span>
        <h2 class="text-2xl mt-3 mb-2">{{ $fiche->comments->count() }} {{ $fiche->comments->count() === 1 ? 'reactie' : 'reacties' }}</h2>
        <p class="text-[var(--color-text-secondary)] mb-8">Collega's delen hun ervaringen met deze fiche.</p>

        @forelse($fiche->comments as $comment)
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
        @empty
            <p class="text-[var(--color-text-secondary)] mb-6">Nog geen reacties. Wees de eerste!</p>
        @endforelse

        @auth
            <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 mt-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-sm font-semibold shrink-0">
                        {{ substr(auth()->user()->first_name, 0, 1) }}
                    </div>
                    <span class="text-sm font-medium">{{ auth()->user()->full_name }}</span>
                </div>
                <form action="{{ route('fiches.comment', $fiche) }}" method="POST">
                    @csrf
                    <textarea
                        name="body"
                        placeholder="Deel je ervaring of tip..."
                        rows="3"
                        required
                        class="w-full rounded-lg border border-[var(--color-border-light)] bg-white px-4 py-3 text-sm placeholder:text-[var(--color-text-secondary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent resize-y"
                    >{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <div class="mt-3 flex justify-end">
                        <flux:button type="submit" variant="primary" size="sm">Plaats reactie</flux:button>
                    </div>
                </form>
            </div>
        @else
            @if(Route::has('login'))
                <div class="bg-[var(--color-bg-cream)] rounded-xl p-6 mt-8 text-center">
                    <a href="{{ route('login') }}" class="cta-link">Log in</a> om een reactie te plaatsen.
                </div>
            @endif
        @endauth
    </div>

    {{-- Zone 4 — Related Fiches --}}
    @if($relatedFiches->isNotEmpty())
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-2xl mb-8">Gerelateerde fiches</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($relatedFiches as $related)
                    <x-fiche-card :fiche="$related" />
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('initiatives.show', $initiative) }}" class="cta-link">Alle fiches van {{ $initiative->title }}</a>
            </div>
        </div>
    @endif

</x-layout>
