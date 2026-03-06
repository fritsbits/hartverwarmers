<div>
    <flux:card class="flex flex-row overflow-hidden">
        <div class="w-2/5 flex-shrink-0">
            <a href="{{ $page['url'] }}">
                <img src="https://i.ytimg.com/vi/{{ $page['_page']['video']['id'] }}/maxresdefault.jpg" alt="{{ $page['label'] }}" class="w-full h-full object-cover" loading="lazy">
            </a>
        </div>
        <div class="p-4 flex-1">
            <h3 class="font-semibold text-[var(--color-text-primary)]">
                <a href="{{ $page['url'] }}" class="hover:text-[var(--color-primary)]">{{ $page['label'] }}</a>
            </h3>
            <div class="meta-group mt-2">
                <span class="text-xs font-semibold text-[var(--color-primary)] uppercase">Les {{ $page['_page']['part'] }}</span>
                <span class="meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $page['_page']['length'] }}
                </span>
            </div>
        </div>
    </flux:card>
</div>
