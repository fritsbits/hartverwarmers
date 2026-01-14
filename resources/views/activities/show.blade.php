<x-layout :title="$activity->title">
    <article class="max-w-4xl mx-auto px-6 py-12">
        <!-- Breadcrumb -->
        <nav class="text-sm breadcrumbs mb-6">
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('activities.index') }}">Activiteiten</a></li>
                <li>{{ $activity->title }}</li>
            </ul>
        </nav>

        <!-- Title -->
        <header class="mb-8">
            <h1 class="text-3xl mb-4">{{ $activity->title }}</h1>

            @if($activity->interests->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @foreach($activity->interests as $interest)
                        <span class="badge badge-outline">{{ $interest->name }}</span>
                    @endforeach
                </div>
            @endif
        </header>

        <!-- Actions -->
        <div class="flex gap-4 mb-8">
            <a href="{{ route('activities.print', $activity) }}" target="_blank" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print
            </a>
            @auth
                <form action="{{ route('activities.bookmark', $activity) }}" method="POST">
                    @csrf
                    @php $isBookmarked = auth()->user()->hasBookmarked($activity); @endphp
                    <button type="submit" class="btn {{ $isBookmarked ? 'btn-primary' : 'btn-outline' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="{{ $isBookmarked ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                        {{ $isBookmarked ? 'Gebookmarkt' : 'Bookmark' }}
                    </button>
                </form>
            @endauth
        </div>

        <!-- Content -->
        <div class="prose prose-lg max-w-none">
            {!! $activity->description !!}
        </div>

        <!-- Fiche Details -->
        @if($activity->fiche)
            <div class="mt-12 bg-base-200 rounded-lg p-6">
                <h2 class="text-xl mb-4">Praktische info</h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @if($activity->fiche['duration'] ?? null)
                        <div>
                            <span class="text-base-content/60 text-sm">Duur</span>
                            <p class="font-medium">{{ $activity->fiche['duration'] }}</p>
                        </div>
                    @endif

                    @if($activity->fiche['group_size'] ?? null)
                        <div>
                            <span class="text-base-content/60 text-sm">Groepsgrootte</span>
                            <p class="font-medium">{{ $activity->fiche['group_size'] }}</p>
                        </div>
                    @endif

                    @if($activity->fiche['materials'] ?? null)
                        <div class="col-span-2">
                            <span class="text-base-content/60 text-sm">Materiaal</span>
                            <p class="font-medium">{{ $activity->fiche['materials'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Comments Section -->
        <section class="mt-12 border-t pt-8">
            <h2 class="text-xl mb-6">Reacties ({{ $activity->comments->count() }})</h2>

            @auth
                <form action="{{ route('activities.comment', $activity) }}" method="POST" class="mb-8">
                    @csrf
                    <textarea
                        name="comment"
                        class="textarea textarea-bordered w-full @error('comment') textarea-error @enderror"
                        placeholder="Deel je ervaring of tip..."
                        rows="3"
                        required
                    >{{ old('comment') }}</textarea>
                    @error('comment')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary btn-sm">Plaats reactie</button>
                    </div>
                </form>
            @else
                @if(Route::has('login'))
                    <div class="alert mb-8">
                        <span><a href="{{ route('login') }}" class="link">Log in</a> om een reactie te plaatsen.</span>
                    </div>
                @endif
            @endauth

            @forelse($activity->comments as $comment)
                <div class="flex gap-4 mb-6">
                    <div class="avatar placeholder">
                        <div class="bg-neutral text-neutral-content rounded-full w-10">
                            <span>{{ substr($comment->user->name ?? 'A', 0, 1) }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="font-medium">{{ $comment->user->name ?? 'Anoniem' }}</div>
                        <div class="text-sm text-base-content/60">{{ $comment->created_at->diffForHumans() }}</div>
                        <p class="mt-2">{{ $comment->comment }}</p>
                    </div>
                </div>
            @empty
                <p class="text-base-content/60">Nog geen reacties. Wees de eerste!</p>
            @endforelse
        </section>
    </article>
</x-layout>
