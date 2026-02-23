<div class="bg-[var(--color-bg-subtle)] rounded-lg p-5">
    @isset($author['image'])
        <img src="{{ is_array($author['image']) ? $author['image']['src'] : $author['image'] }}" alt="{{ $author['name'] }}" class="w-16 h-16 rounded-full object-cover mb-3">
    @endisset
    <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $author['name'] }}</h3>
    @isset($author['text'])
        <p class="text-sm text-[var(--color-text-secondary)] mt-2">{{ $author['text'] }}</p>
    @endisset
    @if(!empty($author['social']))
        <div class="flex gap-2 mt-3">
            @foreach($author['social'] as $social)
                @isset($social['url'])
                    <a href="{{ $social['url'] }}" class="text-[var(--color-text-secondary)] hover:text-[var(--color-primary)]" target="_blank">{{ $social['label'] ?? $social['platform'] ?? 'Link' }}</a>
                @endisset
            @endforeach
        </div>
    @endif
</div>
