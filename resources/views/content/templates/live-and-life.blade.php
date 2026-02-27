<x-layout :title="$content['title']" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('content', 'wonen-en-leven') }}">Wonen en leven</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $content['title'] }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex flex-col md:flex-row gap-8">
                {{-- Images --}}
                <div class="md:w-1/3">
                    @if(isset($content['images']) && count($content['images']) > 0)
                        <img src="{{ $content['images'][0]['src'] }}" alt="{{ $content['images'][0]['alt'] ?? '' }}" class="w-full rounded-lg shadow-lg">
                        @if(count($content['images']) > 1)
                            <div class="grid grid-cols-{{ min(count($content['images']) - 1, 3) }} gap-2 mt-2">
                                @foreach(array_slice($content['images'], 1, 3) as $image)
                                    <img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?? '' }}" class="w-full rounded-lg">
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Content --}}
                <div class="md:w-2/3">
                    <a href="{{ route('content', ['slug' => $overviewSlug]) }}" class="section-label section-label-hero hover:underline">Wonen en leven in woonzorgcentra</a>
                    <h1 class="text-5xl mt-1 mb-2">{{ $content['title'] }}</h1>
                    @isset($content['subtitle'])
                        <p class="text-2xl text-[var(--color-text-secondary)]">{{ $content['subtitle'] }}</p>
                    @endisset
                    <p class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)] mt-2">
                        {{ $content['date'] ?? '' }}
                        @isset($content['author'])
                            <span>&middot;</span> {{ $content['author'] }}
                        @endisset
                    </p>

                    <hr class="my-6 border-[var(--color-border-light)]">

                    <div class="flex flex-col lg:flex-row gap-6">
                        <div class="lg:w-3/5">
                            @isset($content['intro'])
                                @foreach($content['intro'] as $intro)
                                    <p class="text-[var(--color-text-secondary)] mb-3">{!! $intro !!}</p>
                                @endforeach
                            @endisset
                        </div>
                        <div class="lg:w-2/5">
                            @isset($content['intro_facts'])
                                @foreach($content['intro_facts'] as $fact)
                                    <div class="mb-4">
                                        <h4 class="text-xs font-semibold text-[var(--color-primary)] uppercase tracking-wide">{{ $fact['title'] }}</h4>
                                        <p class="text-sm text-[var(--color-text-secondary)] mt-1">{{ $fact['text'] }}</p>
                                    </div>
                                @endforeach
                            @endisset
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Courses --}}
    @isset($content['courses'])
        @foreach($content['courses'] as $course)
            @php $courseContent = \App\Services\JsonContent::getContent($course['content'] ?? '') @endphp
            @if($courseContent)
                <hr class="border-[var(--color-border-light)]">

                <section>
                    <div class="max-w-6xl mx-auto px-6 py-16">
                        <div class="flex flex-col md:flex-row gap-8">
                            <div class="md:w-1/3">
                                @if($course['highlight'] ?? false)
                                    <span class="inline-block text-xs font-semibold bg-[var(--color-primary)] text-white px-2 py-0.5 rounded mb-2">nieuw</span>
                                @endif
                                <h2 class="text-xl font-bold text-[var(--color-text-primary)]">{{ $course['title'] }}</h2>
                                <p class="text-[var(--color-text-secondary)] mt-1">{{ $courseContent['title'] }}</p>
                                <p class="text-sm text-[var(--color-text-secondary)] mt-2">{{ $course['description'] ?? '' }}</p>
                            </div>
                            <div class="md:w-2/3">
                                @isset($course['image']['src'])
                                    <img src="{{ $course['image']['src'] }}" alt="lessenreeks" class="w-full rounded-lg mb-4">
                                @endisset
                                @isset($courseContent['facts'])
                                    <div class="flex flex-wrap gap-4 text-sm text-[var(--color-text-secondary)] mb-4">
                                        @foreach($courseContent['facts'] as $fact)
                                            <span>{{ $fact['text'] }}</span>
                                        @endforeach
                                    </div>
                                @endisset
                                @isset($course['cta'])
                                    @php
                                        $ctaUrl = '#';
                                        if (is_array($course['cta']) && isset($course['cta']['type'])) {
                                            if ($course['cta']['type'] === 'route') {
                                                $ctaUrl = route(...$course['cta']['params']);
                                            } elseif ($course['cta']['type'] === 'path') {
                                                $ctaUrl = url(...$course['cta']['params']);
                                            }
                                        } elseif (is_string($course['cta'])) {
                                            $ctaUrl = '/' . $course['cta'];
                                        }
                                    @endphp
                                    <flux:button variant="primary" href="{{ $ctaUrl }}" size="sm">
                                        Alle videolessen &rarr;
                                    </flux:button>
                                @endisset
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        @endforeach
    @endisset

    {{-- Quote --}}
    @isset($content['quote'])
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="bg-[var(--color-bg-subtle)] rounded-xl p-8">
                    <div class="flex flex-col md:flex-row items-center gap-8">
                        <div class="md:w-1/4 text-center">
                            @isset($content['quote']['image']['src'])
                                <img src="{{ $content['quote']['image']['src'] }}" alt="{{ $content['quote']['image']['alt'] ?? '' }}" class="w-24 h-24 rounded-full mx-auto mb-3">
                            @endisset
                            <p class="text-sm font-semibold text-[var(--color-primary)]">{{ $content['quote']['author']['name'] ?? '' }}</p>
                            <p class="text-xs text-[var(--color-text-secondary)]">{{ $content['quote']['author']['relation'] ?? '' }}</p>
                        </div>
                        <div class="md:w-3/4">
                            <p class="text-lg text-[var(--color-text-secondary)] italic">&ldquo;{{ $content['quote']['text'] }}&rdquo;</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endisset

    {{-- Video --}}
    @if(isset($content['more-info']['video_id']))
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="aspect-video rounded-lg overflow-hidden shadow-lg">
                    <iframe class="w-full h-full" src="https://www.youtube.com/embed/{{ $content['more-info']['video_id'] }}?rel=0" allowfullscreen></iframe>
                </div>
            </div>
        </section>
    @endif

    {{-- Expectations --}}
    @isset($content['expectations'])
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/3">
                        <span class="section-label">Verwachtingen</span>
                        <h2>Wat kan je verwachten?</h2>
                    </div>
                    <div class="md:w-2/3">
                        @isset($content['expectations']['checklist'])
                            <div class="bg-white border border-[var(--color-border-light)] rounded-lg p-5 mb-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    @foreach($content['expectations']['checklist'] as $item)
                                        <div class="flex items-center gap-2 py-1 {{ $item['checked'] ? '' : 'opacity-50' }}">
                                            @if($item['checked'])
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-text-secondary)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @endif
                                            <span class="text-sm text-[var(--color-text-primary)]">{{ $item['name'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endisset

                        @isset($content['expectations']['results'])
                            <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide mb-4 mt-8">Positieve effecten</p>
                            <ul class="space-y-3">
                                @foreach($content['expectations']['results'] as $item)
                                    <li class="flex items-start gap-3 text-[var(--color-text-secondary)]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                        @endisset
                    </div>
                </div>
            </div>
        </section>
    @endisset

    {{-- Core concepts --}}
    @isset($content['core-concepts'])
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/3">
                        <span class="section-label">Theorie</span>
                        <h2>Kernconcepten</h2>
                    </div>
                    <div class="md:w-2/3" x-data="{ open: null }">
                        @foreach($content['core-concepts'] as $key => $item)
                            <div class="border-b border-[var(--color-border-light)]">
                                <button @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}" class="flex items-center gap-2 w-full py-4 text-left font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-primary)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="{ 'rotate-90': open === {{ $loop->index }} }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    {{ $item['title'] }}
                                </button>
                                <div x-show="open === {{ $loop->index }}" x-transition class="pb-4 text-[var(--color-text-secondary)]">
                                    <p>{{ $item['text'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endisset

    {{-- More info --}}
    @isset($content['more-info'])
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/3">
                        <span class="section-label">Bronnen</span>
                        <h2>Meer info</h2>
                    </div>
                    <div class="md:w-2/3">
                        <p class="text-[var(--color-text-secondary)] mb-6">{{ $content['more-info']['text'] ?? '' }}</p>
                        <div class="flex gap-4 items-start">
                            @isset($content['more-info']['image']['src'])
                                <img src="{{ $content['more-info']['image']['src'] }}" alt="{{ $content['more-info']['image']['alt'] ?? '' }}" class="w-24 rounded shadow-sm flex-shrink-0">
                            @endisset
                            <div>
                                <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">{{ $content['more-info']['title'] ?? '' }}</p>
                                @isset($content['more-info']['links'])
                                    <ul class="mt-2 space-y-1">
                                        @foreach($content['more-info']['links'] as $link)
                                            <li>
                                                <a href="{{ $link['url'] }}" class="text-sm text-[var(--color-primary)] hover:underline inline-flex items-center gap-1" target="_blank">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                                    {{ $link['label'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endisset
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endisset

    <hr class="border-[var(--color-border-light)]">

    {{-- Related models --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span class="section-label">Wonen en leven</span>
                    <h2>Andere woon- en leefmodellen</h2>
                    @isset($content['related']['title'])
                        <p class="text-[var(--color-text-secondary)] mt-1">{{ $content['related']['title'] }}</p>
                    @endisset
                </div>
                <flux:button variant="ghost" href="{{ route('content', 'wonen-en-leven') }}" size="sm">
                    Overzicht modellen
                </flux:button>
            </div>
            @include('content.templates._live-and-life-models-list')
        </div>
    </section>
</x-layout>
