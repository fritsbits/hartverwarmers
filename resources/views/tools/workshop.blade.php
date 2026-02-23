<x-layout :title="$workshop['title']">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumbs --}}
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <li><a href="{{ route('home') }}" class="hover:text-[var(--color-primary)]">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('tools.workshops') }}" class="hover:text-[var(--color-primary)]">Workshops</a></li>
                <li>/</li>
                <li class="text-[var(--color-text-primary)] font-medium">{{ $workshop['title'] }}</li>
            </ol>
        </nav>

        {{-- Hero --}}
        <div class="flex flex-col md:flex-row items-start gap-8 pb-8 border-b border-[var(--color-border-light)]">
            <div class="flex-1">
                <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Workshop</p>
                <h1 class="text-5xl mt-1 mb-4">{{ $workshop['title'] }}</h1>
                <p class="text-2xl text-[var(--color-text-secondary)]">{{ $workshop['hero_description'] ?? $workshop['teaser'] }}</p>

                <div class="flex flex-wrap gap-4 mt-4 text-sm text-[var(--color-text-secondary)]">
                    @isset($workshop['duration'])
                        <span class="inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="text-[var(--color-text-secondary)]">Duur:</span> {{ $workshop['duration'] }}
                        </span>
                    @endisset
                    @isset($workshop['audience'])
                        <span class="inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="text-[var(--color-text-secondary)]">Deelnemers:</span> {{ $workshop['audience'] }}
                        </span>
                    @endisset
                    @isset($workshop['format'])
                        <span class="inline-flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <span class="text-[var(--color-text-secondary)]">Materiaal:</span> {{ $workshop['format'] }}
                        </span>
                    @endisset
                </div>

                @isset($workshop['links'])
                    @foreach($workshop['links'] as $link)
                        <flux:button variant="primary" href="{{ $link['url'] }}" class="mt-4">
                            {{ $link['label'] }} &rarr;
                        </flux:button>
                    @endforeach
                @endisset
            </div>
            @isset($workshop['hero_image'])
                <div class="md:w-5/12 flex-shrink-0">
                    <img src="{{ $workshop['hero_image'] }}" alt="{{ $workshop['title'] }}" class="w-full rounded-lg">
                </div>
            @endisset
        </div>

        {{-- Goal --}}
        @if(!empty($workshop['goal']))
            <div class="flex flex-col md:flex-row gap-8 mt-8 py-8">
                <div class="md:w-1/4">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Waar dient het voor?</h2>
                </div>
                <div class="md:w-3/4 columns-1 sm:columns-2 gap-8">
                    @foreach($workshop['goal'] as $goalItem)
                        <p class="text-[var(--color-text-secondary)] mb-4 break-inside-avoid">{{ $goalItem }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Steps --}}
        @if(!empty($workshop['steps']))
            <hr class="my-10 border-[var(--color-border-light)]">
            <div class="flex flex-col md:flex-row gap-8 pt-4">
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
        @endif

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
</x-layout>
