@props(['initiative', 'variant' => 'compact', 'showFicheCount' => false, 'showNewBadge' => false, 'eager' => false])

@php
    $lastVisit = auth()->user()?->last_visited_at;
    $newSince = $lastVisit ?? now()->subDays(7);
    $isNew = $showNewBadge && $initiative->latest_fiche_at && \Carbon\Carbon::parse($initiative->latest_fiche_at)->gt($newSince);
    $isLowFiche = $initiative->fiches_count <= 2;
@endphp

<a href="{{ route('initiatives.show', $initiative) }}" class="block cursor-pointer">
<flux:card class="group overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-[transform,box-shadow,border-color] duration-200 !p-0">
    <div class="relative">
        @if($initiative->image)
            <img src="{{ $initiative->thumbnailUrl() ?? $initiative->image }}" alt="{{ $initiative->title }}" class="w-full aspect-[16/10] object-cover" loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" @if($eager) fetchpriority="high" @endif>
        @else
            <div class="bg-[var(--color-bg-cream)] aspect-[16/10] flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
        @endif

        {{-- "Nieuw" badge (top-right) --}}
        @if($isNew)
            <span class="absolute top-3 right-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold text-white" style="background-color: var(--color-primary);">
                Nieuw
            </span>
        @endif

        {{-- Title + fiche count (bottom, aligned) --}}
        <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between gap-2">
            <span class="inline font-heading font-bold text-lg leading-tight bg-white px-3 py-1.5 rounded box-decoration-clone text-[var(--color-text-primary)]">
                {{ $initiative->title }}<span class="text-[var(--color-primary)] ml-1">&rarr;</span>
            </span>

            @if($showFicheCount)
                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full text-xs font-semibold text-white shrink-0" style="background-color: rgba(35, 30, 26, 0.65);">
                    {{ $initiative->fiches_count }} {{ $initiative->fiches_count === 1 ? 'fiche' : 'fiches' }}
                </span>
            @endif
        </div>
    </div>
</flux:card>
</a>
