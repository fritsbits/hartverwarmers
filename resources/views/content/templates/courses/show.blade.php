<x-layout :title="$content['title']" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('tools.videolessen') }}">Videolessen</flux:breadcrumbs.item>
                @if($parent)
                    <flux:breadcrumbs.item href="{{ url($overviewSlug) }}">{{ $parent['title'] }}</flux:breadcrumbs.item>
                @endif
                <flux:breadcrumbs.item>{{ $content['title'] }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            @php $totalLessons = $content['_pages']->count(); @endphp

            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                <div>
                    <span class="section-label section-label-hero">Les {{ $content['part'] }}</span>
                    <h1 class="text-5xl mt-1">{{ $content['title'] }}</h1>

                    @if($parent)
                        <p class="text-[var(--color-text-secondary)] text-lg mt-3">
                            In lessenreeks <a href="{{ url($overviewSlug) }}" class="text-[var(--color-primary)] hover:underline">{{ $parent['title'] }}</a>
                        </p>
                    @endif

                    @if($parent && isset($parent['credits']))
                        <p class="text-[var(--color-text-secondary)] mt-2">{{ $parent['credits'] }}</p>
                    @endif
                </div>

                {{-- Progress widget --}}
                @if($totalLessons > 0)
                    <div class="shrink-0 bg-white rounded-xl border border-[var(--color-border-light)] px-5 py-4 md:text-right">
                        <span class="text-sm text-[var(--color-text-secondary)]">Voortgang</span>
                        <span class="block font-heading font-bold text-lg mt-0.5">
                            <span class="text-[var(--color-primary)]">{{ $content['part'] }}</span>
                            <span class="text-[var(--color-text-secondary)] font-normal text-sm">van {{ $totalLessons }}</span>
                        </span>
                        <div class="flex gap-1 mt-2 md:justify-end">
                            @foreach($content['_pages']->sortBy('_page.part') as $dot)
                                <a href="{{ $dot['url'] }}"
                                   class="block w-6 h-1.5 rounded-full transition-colors {{ $dot['_page']['part'] == $content['part'] ? 'bg-[var(--color-primary)]' : ($dot['_page']['part'] < $content['part'] ? 'bg-[color-mix(in_srgb,var(--color-primary)_40%,transparent)]' : 'bg-[var(--color-border-light)]') }}"
                                   aria-label="Les {{ $dot['_page']['part'] }}: {{ $dot['label'] }}"
                                   @if($dot['_page']['part'] == $content['part']) aria-current="page" @endif
                                ></a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Video + Author --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="lg:w-2/3">
                    <div class="aspect-video rounded-lg overflow-hidden shadow-lg">
                        <iframe src="https://www.youtube.com/embed/{{ $content['video']['id'] }}?rel=0&modestbranding=1&autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full" title="{{ $content['title'] }}"></iframe>
                    </div>

                    {{-- Next Lesson CTA --}}
                    @php $nextPage = $content['_pages']->where('_page.part', $content['part'] + 1)->first(); @endphp
                    @if($nextPage)
                        <div class="mt-6 rounded-xl bg-[var(--color-bg-cream)] border border-[var(--color-border-light)] p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-semibold text-[var(--color-primary)] uppercase tracking-wide">Volgende les</span>
                                <h4 class="font-heading font-bold mt-0.5 truncate">{{ $nextPage['label'] }}</h4>
                                <span class="meta-item mt-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $nextPage['_page']['length'] }}
                                </span>
                            </div>
                            <flux:button variant="primary" href="{{ $nextPage['url'] }}" class="shrink-0">
                                Ga verder &rarr;
                            </flux:button>
                        </div>
                    @endif
                </div>
                @if($parent && isset($parent['author']))
                    <div class="lg:w-1/3">
                        @include('content.templates.courses._author', ['author' => $parent['author'], 'context' => []])
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Full Lesson List --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Reeks</span>
            <h2 class="mb-6">Jouw leertraject</h2>

            <div class="max-w-3xl">
                @foreach($content['_pages']->sortBy('_page.part') as $page)
                    @php
                        $isCurrent = $page['_page']['part'] == $content['part'];
                        $isDone = $page['_page']['part'] < $content['part'];
                    @endphp
                    <div class="syllabus-step">
                        @if($isDone)
                            <span class="syllabus-number syllabus-number--done" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            </span>
                        @else
                            <span class="syllabus-number {{ $isCurrent ? 'syllabus-number--current' : '' }}" aria-hidden="true">{{ $page['_page']['part'] }}</span>
                        @endif

                        @if($isCurrent)
                            <div class="flex-1 flex items-center gap-4 rounded-xl border-2 border-[var(--color-primary)] bg-white px-5 py-4" aria-current="page">
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs font-semibold text-[var(--color-primary)] uppercase tracking-wide">Je bent hier</span>
                                    <h4 class="font-heading font-bold text-[var(--color-primary)]">{{ $page['label'] }}</h4>
                                    <span class="meta-item mt-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ $page['_page']['length'] }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <a href="{{ $page['url'] }}" class="syllabus-card flex-1 flex items-center gap-4 rounded-xl border border-[var(--color-border-light)] bg-white hover:shadow-md hover:border-[var(--color-border-hover)] no-underline group px-5 py-4" aria-label="Les {{ $page['_page']['part'] }}: {{ $page['label'] }}">
                                <div class="flex-1 min-w-0">
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
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layout>
