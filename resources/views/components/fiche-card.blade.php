@props(['fiche', 'showTags' => true, 'compact' => false])

@php
    $previews = $compact ? [] : $fiche->cardPreviewImages(3);
    $hasPreviews = count($previews) > 0;
@endphp

<a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="block cursor-pointer">
<flux:card class="fiche-card overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">

    @if($hasPreviews)
        <div class="fiche-card-header">
            @foreach($previews as $i => $url)
                <div class="fiche-paper fiche-paper-{{ $i }}" style="z-index: {{ $i + 1 }}">
                    <img src="{{ $url }}" alt="" loading="lazy" draggable="false">
                </div>
            @endforeach
        </div>
    @endif

    <div class="fiche-card-body">
        @if($fiche->has_diamond)
            <div class="mb-2">
                <x-diamond-badge />
            </div>
        @endif
        <flux:heading size="lg" class="font-heading font-bold">{{ $fiche->title }}</flux:heading>

        @if($fiche->description)
            <flux:text class="mt-2 line-clamp-2">
                {{ Str::limit(strip_tags($fiche->description), 120) }}
            </flux:text>
        @endif

        @if($showTags && $fiche->tags->isNotEmpty())
            <div class="flex flex-wrap gap-1 mt-3">
                @foreach($fiche->tags->take(3) as $tag)
                    <flux:badge size="sm" color="zinc">{{ $tag->name }}</flux:badge>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between mt-4 pt-3 border-t border-[var(--color-border-light)]">
            <div class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                @if($fiche->user)
                    <span>Door {{ $fiche->user->full_name }}</span>
                @endif
                @if($fiche->kudos_count > 0)
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-[var(--color-primary)]">
                            <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z" />
                        </svg>
                        {{ $fiche->kudos_count }}
                    </span>
                @endif
                @if(($fiche->comments_count ?? 0) > 0)
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                        </svg>
                        {{ $fiche->comments_count }}
                    </span>
                @endif
            </div>
            <span class="cta-link text-sm">
                Bekijk
            </span>
        </div>
    </div>
</flux:card>
</a>
