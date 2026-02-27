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
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Video + Author --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex flex-col lg:flex-row gap-8">
                <div class="lg:w-2/3">
                    <div class="aspect-video rounded-lg overflow-hidden shadow-lg">
                        <iframe src="https://www.youtube.com/embed/{{ $content['video']['id'] }}?rel=0&modestbranding=1&autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                    </div>

                    @php $nextPage = $content['_pages']->where('_page.part', $content['part']+1)->first() @endphp
                    @if($nextPage)
                        <div class="mt-6 flex justify-end">
                            <flux:button variant="primary" href="{{ $nextPage['url'] }}">
                                Volgende les &rarr;
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

    <hr class="border-[var(--color-border-light)]">

    {{-- Other lessons --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Reeks</span>
            <h2 class="mb-6">Andere lessen binnen deze reeks</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @each('content.templates.courses._video', $content['_pages']->sortBy('_page.part')->where('slug', '!=', $slug), 'page')
            </div>
        </div>
    </section>
</x-layout>
