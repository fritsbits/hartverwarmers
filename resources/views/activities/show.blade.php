<x-layout :title="$activity->title">
    <article class="max-w-6txl mx-auto px-6 py-12">
        <!-- Breadcrumb -->
        <flux:breadcrumbs class="mb-6">
            <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('activities.index') }}">Activiteiten</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $activity->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <!-- Title -->
        <header class="mb-8">
            <h1 class="text-3xl mb-4">{{ $activity->title }}</h1>

            <div class="flex flex-wrap gap-2">
                {{-- Interests --}}
                @foreach($activity->interests as $interest)
                    <flux:badge variant="outline">{{ $interest->name }}</flux:badge>
                @endforeach

                {{-- Sense of Home Dimensions --}}
                @if($activity->dimensions)
                    @foreach($activity->dimensions as $dimensionValue)
                        @php $dimension = \App\Enums\ActivityDimension::tryFrom($dimensionValue); @endphp
                        @if($dimension)
                            <flux:badge color="red">{{ $dimension->title() }}</flux:badge>
                        @endif
                    @endforeach
                @endif

                {{-- Zorgprofielen (Guidances) --}}
                @if($activity->guidances)
                    @foreach($activity->guidances as $guidanceValue)
                        @php $guidance = \App\Enums\Guidance::tryFrom($guidanceValue); @endphp
                        @if($guidance)
                            <flux:badge color="cyan" title="{{ $guidance->description() }}">{{ $guidance->title() }}</flux:badge>
                        @endif
                    @endforeach
                @endif
            </div>
        </header>

        <!-- Actions -->
        <div class="flex gap-4 mb-8">
            <flux:button variant="primary" href="{{ route('activities.print', $activity) }}" target="_blank" icon="printer">
                Print
            </flux:button>
            @auth
                <form action="{{ route('activities.bookmark', $activity) }}" method="POST">
                    @csrf
                    @php $isBookmarked = auth()->user()->hasBookmarked($activity); @endphp
                    <flux:button type="submit" :variant="$isBookmarked ? 'primary' : 'ghost'" icon="bookmark">
                        {{ $isBookmarked ? 'Gebookmarkt' : 'Bookmark' }}
                    </flux:button>
                </form>
            @endauth
        </div>

        <!-- Content -->
        <div class="prose prose-lg max-w-none">
            {!! $activity->description !!}
        </div>

        <!-- Fiche Details -->
        @if($activity->fiche)
            <flux:card class="mt-12">
                <flux:heading size="lg" class="mb-4">Praktische info</flux:heading>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @if($activity->fiche['duration'] ?? null)
                        <div>
                            <flux:text class="text-sm text-[var(--color-text-secondary)]">Duur</flux:text>
                            <p class="font-medium">{{ $activity->fiche['duration'] }}</p>
                        </div>
                    @endif

                    @if($activity->fiche['group_size'] ?? null)
                        <div>
                            <flux:text class="text-sm text-[var(--color-text-secondary)]">Groepsgrootte</flux:text>
                            <p class="font-medium">{{ $activity->fiche['group_size'] }}</p>
                        </div>
                    @endif

                    @if($activity->fiche['materials'] ?? null)
                        <div class="col-span-2">
                            <flux:text class="text-sm text-[var(--color-text-secondary)]">Materiaal</flux:text>
                            <p class="font-medium">{{ $activity->fiche['materials'] }}</p>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endif

        <!-- Comments Section -->
        <section class="mt-12 border-t border-[var(--color-border-light)] pt-8">
            <flux:heading size="lg" class="mb-6">Reacties ({{ $activity->comments->count() }})</flux:heading>

            @auth
                <form action="{{ route('activities.comment', $activity) }}" method="POST" class="mb-8">
                    @csrf
                    <flux:textarea
                        name="comment"
                        placeholder="Deel je ervaring of tip..."
                        rows="3"
                        required
                    >{{ old('comment') }}</flux:textarea>
                    @error('comment')
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

            @forelse($activity->comments as $comment)
                <div class="flex gap-4 mb-6">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center font-semibold shrink-0">
                        {{ substr($comment->user->name ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <div class="font-medium">{{ $comment->user->name ?? 'Anoniem' }}</div>
                        <flux:text class="text-sm text-[var(--color-text-secondary)]">{{ $comment->created_at->diffForHumans() }}</flux:text>
                        <p class="mt-2">{{ $comment->comment }}</p>
                    </div>
                </div>
            @empty
                <flux:text class="text-[var(--color-text-secondary)]">Nog geen reacties. Wees de eerste!</flux:text>
            @endforelse
        </section>
    </article>
</x-layout>
