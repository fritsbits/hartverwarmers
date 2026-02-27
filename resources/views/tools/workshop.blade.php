<x-layout :title="$workshop['title']" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('tools.workshops') }}">Workshops</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $workshop['title'] }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex flex-col md:flex-row items-start gap-8">
                <div class="md:w-8/12">
                    <span class="section-label section-label-hero">Workshop</span>
                    <h1 class="text-5xl mt-1 mb-4">{{ $workshop['title'] }}</h1>
                    <p class="text-2xl text-[var(--color-text-secondary)]">{{ $workshop['hero_description'] ?? $workshop['teaser'] }}</p>

                    <div class="meta-group mt-4">
                        @isset($workshop['duration'])
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Duur: {{ $workshop['duration'] }}
                            </span>
                        @endisset
                        @isset($workshop['audience'])
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Deelnemers: {{ $workshop['audience'] }}
                            </span>
                        @endisset
                        @isset($workshop['format'])
                            <span class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Materiaal: {{ $workshop['format'] }}
                            </span>
                        @endisset
                    </div>

                    @isset($workshop['links'])
                        <div class="flex flex-wrap gap-x-6 gap-y-2 mt-4">
                            @foreach($workshop['links'] as $link)
                                <a href="{{ $link['url'] }}" class="cta-link text-sm">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    @endisset
                </div>
                @isset($workshop['hero_image'])
                    <div class="md:w-4/12 flex-shrink-0">
                        <img src="{{ $workshop['hero_image'] }}" alt="{{ $workshop['title'] }}" class="max-w-xs mx-auto rounded-lg shadow mb-4">
                    </div>
                @endisset
            </div>
        </div>
    </section>

    {{-- Goal --}}
    @if(!empty($workshop['goal']))
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/4">
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Waar dient het voor?</h2>
                    </div>
                    <div class="md:w-3/4 columns-1 sm:columns-2 gap-8">
                        @foreach($workshop['goal'] as $goalItem)
                            <p class="text-[var(--color-text-secondary)] mb-4 break-inside-avoid">{{ $goalItem }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Steps --}}
    @if(!empty($workshop['steps']))
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/4">
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Stappen</h2>
                    </div>
                    <div class="md:w-3/4">
                        @foreach($workshop['steps'] as $index => $step)
                            <div class="flex gap-4 mb-6">
                                <div class="text-2xl font-bold text-[var(--color-primary)]">{{ $index + 1 }}</div>
                                <p class="text-[var(--color-text-secondary)]">{{ $step['content'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Download --}}
                @isset($workshop['download'])
                    <div class="bg-[var(--color-bg-subtle)] rounded-xl p-8 mt-12 text-center">
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Download de pdf</h2>
                        @isset($workshop['download']['description'])
                            <p class="text-[var(--color-text-secondary)] mt-2">{{ $workshop['download']['description'] }}</p>
                        @endisset
                        <flux:button variant="primary" href="{{ $workshop['download']['url'] }}" target="_blank" class="mt-4">
                            {{ $workshop['download']['label'] ?? 'Download' }}
                        </flux:button>
                    </div>
                @endisset

                {{-- Back --}}
                <div class="mt-12">
                    <flux:button variant="ghost" href="{{ route('tools.workshops') }}">
                        &larr; Alle workshops
                    </flux:button>
                </div>
            </div>
        </section>
    @endif
</x-layout>
