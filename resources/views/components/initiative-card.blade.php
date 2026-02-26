@props(['initiative', 'showFicheCount' => false])

<a href="{{ route('initiatives.show', $initiative) }}" class="block cursor-pointer">
<flux:card class="overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
    @if($initiative->image)
        <div class="-mx-6 -mt-6 mb-4">
            <img src="{{ $initiative->image }}" alt="{{ $initiative->title }}" class="w-full aspect-[16/10] object-cover">
        </div>
    @else
        <div class="-mx-6 -mt-6 mb-4 bg-[var(--color-bg-cream)] aspect-[16/10] flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
    @endif

    <flux:heading size="lg">{{ $initiative->title }}</flux:heading>

    @if($initiative->description)
        <flux:text class="mt-2 line-clamp-2">
            {{ Str::limit(strip_tags($initiative->description), 120) }}
        </flux:text>
    @endif

    @if(!$showFicheCount && $initiative->tags->isNotEmpty())
        <div class="flex flex-wrap gap-1 mt-3">
            @foreach($initiative->tags->take(3) as $tag)
                <flux:badge size="sm" color="zinc">{{ $tag->name }}</flux:badge>
            @endforeach
        </div>
    @endif

    <div class="flex items-center justify-between mt-4 pt-3 border-t border-[var(--color-border-light)]">
        <div class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
            @if($showFicheCount)
                <span>{{ $initiative->fiches_count }} {{ $initiative->fiches_count === 1 ? 'fiche' : 'fiches' }}</span>
            @endif
        </div>
        <span class="cta-link text-sm">
            Bekijk
        </span>
    </div>
</flux:card>
</a>
