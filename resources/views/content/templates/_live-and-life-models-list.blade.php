@foreach($content['_pages']->where('slug', '!=', $slug)->chunk(10) as $pages)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        @foreach($pages as $page)
            <flux:card class="flex flex-row overflow-hidden">
                <div class="w-1/3 flex-shrink-0 {{ !($page['_page']['_published'] ?? true) ? 'grayscale opacity-40' : '' }}">
                    <a href="{{ $page['url'] }}">
                        @if(isset($page['_page']['images'][0]))
                            <img src="{{ $page['_page']['images'][0]['src'] }}" alt="{{ $page['_page']['images'][0]['alt'] ?? '' }}" class="w-full h-full object-cover" loading="lazy">
                        @endif
                    </a>
                </div>
                <div class="p-4 flex-1">
                    @if($page['_page']['_published'] ?? true)
                        <h3 class="font-semibold text-[var(--color-text-primary)]">
                            <a href="{{ $page['url'] }}" class="hover:text-[var(--color-primary)]">{{ $page['label'] }}</a>
                        </h3>
                        @if(isset($page['_page']['description']))
                            <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $page['_page']['description'] }}</p>
                        @endif
                        @if(isset($page['_page']['author']))
                            <p class="text-xs text-[var(--color-text-secondary)] mt-1">{{ $page['_page']['author'] }}</p>
                        @endif
                    @else
                        <h3 class="font-semibold text-[var(--color-text-secondary)]">
                            {{ $page['label'] }}
                            <span class="inline-block ml-1 text-xs bg-[var(--color-bg-subtle)] px-2 py-0.5 rounded">Komt er aan</span>
                        </h3>
                        @if(isset($page['_page']['description']))
                            <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $page['_page']['description'] }}</p>
                        @endif
                    @endif
                </div>
            </flux:card>
        @endforeach
    </div>
@endforeach
