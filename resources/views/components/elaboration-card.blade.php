@props(['elaboration'])

<a href="{{ route('elaborations.show', [$elaboration->initiative, $elaboration]) }}" class="block cursor-pointer">
<flux:card class="overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
    <flux:heading size="lg">{{ $elaboration->title }}</flux:heading>

    @if($elaboration->description)
        <flux:text class="mt-2 line-clamp-2">
            {{ Str::limit(strip_tags($elaboration->description), 120) }}
        </flux:text>
    @endif

    @if($elaboration->tags->isNotEmpty())
        <div class="flex flex-wrap gap-1 mt-3">
            @foreach($elaboration->tags->take(3) as $tag)
                <flux:badge size="sm" color="zinc">{{ $tag->name }}</flux:badge>
            @endforeach
        </div>
    @endif

    <div class="flex items-center justify-between mt-4 pt-3 border-t border-[var(--color-border-light)]">
        <div class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
            @if($elaboration->user)
                <span>Door {{ $elaboration->user->name }}</span>
            @endif
        </div>
        <span class="cta-link text-sm">
            Bekijk
        </span>
    </div>
</flux:card>
</a>
