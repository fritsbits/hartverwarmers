<x-layout :title="$content['title']" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('tools.videolessen') }}">Videolessen</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $content['title'] }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Lessenreeks</span>
            <h1 class="text-5xl mt-1">{{ $content['title'] }}</h1>

            <p class="text-xl text-[var(--color-text-secondary)] mt-4 max-w-2xl">{{ $content['description'][0] }}</p>

            @foreach(array_slice($content['description'], 1) as $paragraph)
                <p class="text-base text-[var(--color-text-secondary)] mt-2 max-w-2xl">{{ $paragraph }}</p>
            @endforeach

            @isset($content['credits'])
                <p class="text-sm text-[var(--color-text-secondary)] mt-3 font-medium">{{ $content['credits'] }}</p>
            @endisset

            <div class="meta-group mt-5">
                @foreach($content['facts'] as $fact)
                    <span class="meta-item">
                        @if($fact['icon'] === 'video_library')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        @elseif($fact['icon'] === 'access_time')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @endif
                        {{ $fact['text'] }}
                    </span>
                @endforeach
            </div>

        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Video + Author + Materials --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="lg:w-2/3">
                    <div class="aspect-video rounded-lg overflow-hidden shadow-lg">
                        <iframe src="https://www.youtube.com/embed/{{ $content['video'] }}?rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full" title="{{ $content['title'] }} — introductievideo"></iframe>
                    </div>

                    {{-- Materials Callout --}}
                    @if(!empty($content['author']['links']))
                        @php
                            $fileLinks = collect($content['author']['links'])->filter(fn($link) => ($link['icon'] ?? '') === 'file_copy');
                        @endphp
                        @if($fileLinks->isNotEmpty())
                            <div class="materials-callout mt-6">
                                <div class="flex items-start gap-4">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full shrink-0 bg-white" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-[var(--color-primary)]">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-heading font-bold">Downloadbaar materiaal</h4>
                                        <p class="text-sm text-[var(--color-text-secondary)] mt-1">Werkbladen en documenten bij deze lessenreeks</p>
                                        <div class="flex flex-wrap gap-3 mt-3">
                                            @foreach($fileLinks as $link)
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
                                                <flux:button variant="primary" size="sm" href="{{ $href }}" :target="isset($link['url']) ? '_blank' : null">
                                                    {{ $link['label'] }}
                                                </flux:button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
                @isset($content['author'])
                    @php
                        $authorForSidebar = $content['author'];
                        $authorForSidebar['links'] = collect($content['author']['links'] ?? [])->reject(fn($link) => ($link['icon'] ?? '') === 'file_copy')->values()->all();
                    @endphp
                    <div class="lg:w-1/3">
                        @include('content.templates.courses._author', [
                            'author' => $authorForSidebar,
                            'context' => [],
                        ])
                    </div>
                @endisset
            </div>
        </div>
    </section>

    {{-- Syllabus Lesson List --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Lessen</span>
            <h2 class="mb-6">Stap voor stap aan de slag</h2>

            <div class="max-w-3xl">
                @foreach($content['_pages']->sortBy('_page.part') as $page)
                    <div class="syllabus-step">
                        <span class="syllabus-number" aria-hidden="true">{{ $page['_page']['part'] }}</span>
                        <a href="{{ $page['url'] }}" class="syllabus-card flex-1 flex items-center gap-4 rounded-xl border border-[var(--color-border-light)] bg-white hover:shadow-md hover:border-[var(--color-border-hover)] no-underline group px-5 py-4" aria-label="Les {{ $page['_page']['part'] }}: {{ $page['label'] }}">
                            <div class="flex-1 min-w-0">
                                @if($loop->first)
                                    <span class="text-xs font-semibold text-[var(--color-primary)] uppercase tracking-wide">Begin hier</span>
                                @endif
                                <h4 class="font-heading font-bold text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors">{{ $page['label'] }}</h4>
                                <span class="meta-item mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $page['_page']['length'] }}
                                </span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 shrink-0 text-[var(--color-text-secondary)] group-hover:text-[var(--color-primary)] transition-colors" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layout>
