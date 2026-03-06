<a href="{{ route('tools.workshops.show', ['uid' => $workshop['uid']]) }}" class="block cursor-pointer">
<flux:card class="overflow-hidden border border-[var(--color-border-light)] hover:border-[var(--color-border-hover)] hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200">
    @if(!empty($workshop['preview_image'] ?? $workshop['hero_image'] ?? null))
        <div class="-mx-6 -mt-6 mb-4">
            <img src="{{ $workshop['preview_image'] ?? $workshop['hero_image'] }}" alt="{{ $workshop['title'] }}" class="w-full aspect-[16/10] object-cover" loading="lazy">
        </div>
    @else
        <div class="-mx-6 -mt-6 mb-4 bg-[var(--color-bg-cream)] aspect-[16/10] flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-[var(--color-border-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
    @endif

    <flux:heading size="lg" class="font-heading font-bold">{{ $workshop['title'] }}</flux:heading>

    @isset($workshop['teaser'])
        <flux:text class="mt-2 line-clamp-2">
            {{ Str::limit($workshop['teaser'], 120) }}
        </flux:text>
    @endisset

    <div class="flex flex-wrap gap-3 mt-3 text-sm text-[var(--color-text-secondary)]">
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

    <div class="flex items-center justify-between mt-4 pt-3 border-t border-[var(--color-border-light)]">
        <span class="cta-link text-sm">Bekijk workshop</span>
    </div>
</flux:card>
</a>
