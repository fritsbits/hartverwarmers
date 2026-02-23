<x-layout :title="$content['title']">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumbs --}}
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <li><a href="{{ route('home') }}" class="hover:text-[var(--color-primary)]">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('tools.videolessen') }}" class="hover:text-[var(--color-primary)]">Videolessen</a></li>
                <li>/</li>
                @if($parent)
                    <li><a href="{{ url($overviewSlug) }}" class="hover:text-[var(--color-primary)]">{{ $parent['title'] }}</a></li>
                    <li>/</li>
                @endif
                <li class="text-[var(--color-text-primary)] font-medium">{{ $content['title'] }}</li>
            </ol>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">
            <div class="lg:w-2/3">
                <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Video lessenreeks</p>

                {{-- Video embed --}}
                <div class="aspect-video rounded-lg overflow-hidden shadow-lg mt-3 mb-4">
                    <iframe src="https://www.youtube.com/embed/{{ $content['video']['id'] }}?rel=0&modestbranding=1&autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>

                {{-- Content --}}
                <div class="bg-[var(--color-bg-white)] border border-[var(--color-border-light)] rounded-lg p-6">
                    <h1 class="text-5xl">{{ $content['title'] }}</h1>
                    <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide mt-1">Les {{ $content['part'] }}</p>

                    @if($parent && isset($parent['credits']))
                        <p class="text-sm text-[var(--color-text-secondary)] mt-2">{{ $parent['credits'] }}</p>
                    @endif

                    @php $nextPage = $content['_pages']->where('_page.part', $content['part']+1)->first() @endphp
                    @if($nextPage)
                        <hr class="my-4 border-[var(--color-border-light)]">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <span class="text-sm text-[var(--color-text-secondary)]">
                                In lessenreeks <a href="{{ url($overviewSlug) }}" class="text-[var(--color-primary)] hover:underline">{{ $parent['title'] }}</a>
                            </span>
                            <flux:button variant="primary" href="{{ $nextPage['url'] }}" size="sm">
                                Volgende les &rarr;
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="lg:w-1/3">
                @if($parent && isset($parent['author']))
                    @include('content.templates.courses._author', ['author' => $parent['author']])
                @endif
            </div>
        </div>

        {{-- Other lessons --}}
        <div class="mt-12">
            <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide mb-4">Andere lessen binnen deze reeks</p>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @each('content.templates.courses._video', $content['_pages']->sortBy('_page.part')->where('slug', '!=', $slug), 'page')
            </div>
        </div>
    </div>
</x-layout>
