@props([
    'title',
    'subtitle' => null,
    'items' => [],
    'empty' => 'Geen data om te tonen.',
])

<flux:card>
    <flux:heading size="lg" class="font-heading font-bold mb-1">{{ $title }}</flux:heading>
    @if($subtitle)
        <p class="text-sm text-[var(--color-text-secondary)] mb-4">{{ $subtitle }}</p>
    @endif

    @if(empty($items))
        <p class="text-sm text-[var(--color-text-secondary)]">{{ $empty }}</p>
    @else
        <div class="divide-y divide-[var(--color-border-light)]">
            @foreach($items as $item)
                @php
                    $tag = isset($item['url']) ? 'a' : 'div';
                    $rowClasses = 'block py-2 -mx-1 px-1 rounded transition-colors group';
                    if (isset($item['url'])) {
                        $rowClasses .= ' hover:bg-[var(--color-surface)]';
                    }
                @endphp
                <{{ $tag }} @if(isset($item['url'])) href="{{ $item['url'] }}" @endif class="{{ $rowClasses }}">
                    <div class="flex items-baseline gap-3">
                        <span class="flex-1 text-sm text-[var(--color-text-secondary)] truncate group-hover:text-[var(--color-text-primary)]">{{ $item['title'] }}</span>
                        @if(isset($item['badge']))
                            <span class="text-sm font-bold shrink-0 tabular-nums {{ $item['badge_color'] ?? 'text-[var(--color-primary)]' }}">{{ $item['badge'] }}</span>
                        @endif
                        @if(isset($item['meta']))
                            <span class="text-xs text-[var(--color-text-secondary)] shrink-0 text-right">{{ $item['meta'] }}</span>
                        @endif
                    </div>
                    @if(isset($item['body']) && $item['body'] !== '')
                        <p class="text-sm italic text-[var(--color-text-secondary)] mt-1 line-clamp-2">&ldquo;{{ $item['body'] }}&rdquo;</p>
                    @endif
                </{{ $tag }}>
            @endforeach
        </div>
    @endif
</flux:card>
