<div class="flex items-center gap-6 py-4 {{ !$loop->last ? 'border-b border-[var(--color-border-light)]' : '' }}">
    <div class="flex-1">
        <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $workshop['title'] }}</h3>
        @isset($workshop['teaser'])
            <p class="text-[var(--color-text-secondary)] mt-1">{{ $workshop['teaser'] }}</p>
        @endisset
        <div class="flex flex-wrap gap-4 mt-3 text-sm text-[var(--color-text-secondary)]">
            @isset($workshop['duration'])
                <span class="inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $workshop['duration'] }}
                </span>
            @endisset
            @isset($workshop['audience'])
                <span class="inline-flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Voor {{ lcfirst($workshop['audience']) }}
                </span>
            @endisset
        </div>
        <a href="{{ route('tools.workshops.show', ['uid' => $workshop['uid']]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-[var(--color-primary)] hover:underline mt-3">
            Meer &rarr;
        </a>
    </div>
    <div class="hidden md:block w-32 flex-shrink-0">
        <a href="{{ route('tools.workshops.show', ['uid' => $workshop['uid']]) }}">
            <img src="{{ $workshop['preview_image'] ?? $workshop['hero_image'] }}" alt="{{ $workshop['title'] }}" class="w-full rounded-lg">
        </a>
    </div>
</div>
