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

            <div class="meta-group mt-5">
                @foreach($content['facts'] as $fact)
                    <span class="meta-item">
                        @if($fact['icon'] === 'video_library')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        @elseif($fact['icon'] === 'access_time')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @endif
                        {{ $fact['text'] }}
                    </span>
                @endforeach
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
                        <iframe src="https://www.youtube.com/embed/{{ $content['video'] }}?rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                    </div>
                </div>
                @isset($content['author'])
                    <div class="lg:w-1/3">
                        @include('content.templates.courses._author', [
                            'author' => $content['author'],
                            'context' => array_slice($content['description'], 1),
                        ])
                    </div>
                @endisset
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- All lessons --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Lessen</span>
            <h2 class="mb-6">Alle lessen in deze reeks</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @each('content.templates.courses._video', $content['_pages']->sortBy('_page.part'), 'page')
            </div>
        </div>
    </section>
</x-layout>
