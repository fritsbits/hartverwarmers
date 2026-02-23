<x-layout :title="$content['title']">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumbs --}}
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <li><a href="{{ route('home') }}" class="hover:text-[var(--color-primary)]">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('tools.videolessen') }}" class="hover:text-[var(--color-primary)]">Videolessen</a></li>
                <li>/</li>
                <li class="text-[var(--color-text-primary)] font-medium">{{ $content['title'] }}</li>
            </ol>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">
            <div class="lg:w-2/3">
                <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Video lessenreeks</p>
                <h1 class="text-5xl mt-1 mb-4">{{ $content['title'] }}</h1>

                {{-- Video embed --}}
                <div class="aspect-video rounded-lg overflow-hidden shadow-lg mb-4">
                    <iframe src="https://www.youtube.com/embed/{{ $content['video'] }}?rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>

                {{-- Description --}}
                <div class="bg-[var(--color-bg-white)] border border-[var(--color-border-light)] rounded-lg p-6">
                    @foreach($content['description'] as $description)
                        <p class="text-[var(--color-text-secondary)] mb-3 last:mb-0">{{ $description }}</p>
                    @endforeach

                    <hr class="my-4 border-[var(--color-border-light)]">

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap gap-4 text-sm text-[var(--color-text-secondary)]">
                            @foreach($content['facts'] as $fact)
                                <span class="inline-flex items-center gap-1">{{ $fact['text'] }}</span>
                            @endforeach
                        </div>
                        @php $firstLesson = $content['_pages']->where('_page.part', 1)->first() @endphp
                        @if($firstLesson)
                            <flux:button variant="primary" href="{{ $firstLesson['url'] }}" size="sm">
                                Start met de eerste les &rarr;
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="lg:w-1/3">
                @isset($content['author'])
                    @include('content.templates.courses._author', ['author' => $content['author']])
                @endisset
            </div>
        </div>

        {{-- All lessons --}}
        <div class="mt-12">
            <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide mb-4">Alle lessen in deze reeks</p>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @each('content.templates.courses._video', $content['_pages']->sortBy('_page.part'), 'page')
            </div>
        </div>
    </div>
</x-layout>
