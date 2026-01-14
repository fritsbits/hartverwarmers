@props(['activity'])

<article class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
    @if($activity->fiche['image'] ?? null)
        <figure>
            <img src="{{ $activity->fiche['image'] }}" alt="{{ $activity->title }}" class="w-full aspect-[16/10] object-cover">
        </figure>
    @else
        <figure class="bg-base-200 aspect-[16/10] flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </figure>
    @endif

    <div class="card-body">
        <h3 class="card-title text-lg">{{ $activity->title }}</h3>

        @if($activity->description)
            <p class="text-base-content/70 line-clamp-2">
                {{ Str::limit(strip_tags($activity->description), 120) }}
            </p>
        @endif

        @if($activity->interests->isNotEmpty())
            <div class="flex flex-wrap gap-1 mt-2">
                @foreach($activity->interests->take(3) as $interest)
                    <span class="badge badge-ghost badge-sm">{{ $interest->name }}</span>
                @endforeach
            </div>
        @endif

        <div class="card-actions justify-end mt-4">
            <a href="{{ route('activities.show', $activity) }}" class="cta-link">
                Bekijk activiteit
            </a>
        </div>
    </div>
</article>
