<div class="bg-[var(--color-bg-subtle)] rounded-lg p-5">
    @isset($author['image'])
        <img src="{{ is_array($author['image']) ? $author['image']['src'] : $author['image'] }}" alt="{{ $author['name'] }}" class="w-16 h-16 rounded-full object-cover mb-3">
    @endisset
    <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $author['name'] }}</h3>
    @isset($author['text'])
        <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $author['text'] }}</p>
    @endisset

    @if(!empty($context))
        <div class="mt-3 space-y-2">
            @foreach($context as $paragraph)
                <p class="text-sm text-[var(--color-text-secondary)]">{{ $paragraph }}</p>
            @endforeach
        </div>
    @endif

    @if(!empty($author['links']))
        <div class="mt-4 space-y-2">
            @foreach($author['links'] as $link)
                @php
                    if (isset($link['url'])) {
                        $href = $link['url'];
                    } elseif (($link['type'] ?? '') === 'route') {
                        $href = route($link['params'][0], $link['params'][1] ?? []);
                    } elseif (($link['type'] ?? '') === 'path') {
                        $href = url($link['params'][0]);
                    } else {
                        $href = '#';
                    }
                @endphp
                <a href="{{ $href }}" class="flex items-center gap-2 text-sm text-[var(--color-primary)] hover:underline" @if(isset($link['url'])) target="_blank" @endif>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 shrink-0">
                        @if(($link['icon'] ?? '') === 'file_copy')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        @elseif(($link['icon'] ?? '') === 'info')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        @elseif(($link['icon'] ?? '') === 'book')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        @endif
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    @endif

    @if(!empty($author['social']))
        <div class="flex gap-3 mt-3">
            @foreach($author['social'] as $social)
                @isset($social['url'])
                    <a href="{{ $social['url'] }}" class="text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-primary)]" target="_blank">
                        {{ $social['label'] ?? ucfirst($social['type'] ?? $social['platform'] ?? 'Link') }}
                    </a>
                @endisset
            @endforeach
        </div>
    @endif
</div>
