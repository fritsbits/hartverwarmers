<x-layout :title="$tool['title']" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tools & inspiratie</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('tools.index') }}">Gidsen en tools</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $tool['title'] }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex flex-col md:flex-row items-start gap-8">
                <div class="md:w-8/12">
                    <span class="section-label section-label-hero">Tool</span>
                    <h1 class="text-5xl mt-1">{{ $tool['title'] }}</h1>
                    <p class="text-2xl text-[var(--color-text-secondary)] mt-4">{{ $tool['hero_description'] ?? $tool['teaser'] }}</p>
                    @isset($tool['links'])
                        <div class="flex flex-wrap gap-x-6 gap-y-2 mt-4">
                            @foreach($tool['links'] as $link)
                                <a href="{{ $link['url'] }}" class="cta-link text-sm">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    @endisset
                </div>
                @isset($tool['hero_image'])
                    <div class="md:w-4/12 flex-shrink-0">
                        <img src="{{ $tool['hero_image'] }}" alt="{{ $tool['title'] }}" class="max-w-xs mx-auto rounded-lg shadow mb-4">
                    </div>
                @endisset
            </div>
        </div>
    </section>

    {{-- Goal --}}
    @if(!empty($tool['goal']))
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/4">
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Waar dient het voor?</h2>
                    </div>
                    <div class="md:w-3/4 columns-1 sm:columns-2 gap-8">
                        @foreach($tool['goal'] as $goalItem)
                            <p class="text-[var(--color-text-secondary)] mb-4 break-inside-avoid">{{ $goalItem }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Steps --}}
    @if(!empty($tool['steps']))
        <hr class="border-[var(--color-border-light)]">

        <section>
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="flex flex-col md:flex-row gap-8">
                    <div class="md:w-1/4">
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Stappen</h2>
                    </div>
                    <div class="md:w-3/4">
                        @foreach($tool['steps'] as $index => $step)
                            <div class="mb-6">
                                <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Stap {{ $index + 1 }}</p>
                                <p class="text-[var(--color-text-secondary)] mt-1">{{ $step['content'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Download --}}
                @isset($tool['download'])
                    <div class="bg-[var(--color-bg-subtle)] rounded-xl p-8 mt-12 text-center">
                        <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">{{ $tool['title'] }}</p>
                        <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mt-1">Download de pdf</h2>
                        @isset($tool['download']['description'])
                            <p class="text-[var(--color-text-secondary)] mt-2">{{ $tool['download']['description'] }}</p>
                        @endisset
                        <flux:button variant="primary" href="{{ $tool['download']['url'] }}" target="_blank" class="mt-4">
                            {{ $tool['download']['label'] ?? 'Download' }}
                        </flux:button>
                    </div>
                @endisset

                {{-- Back --}}
                <div class="mt-12">
                    <flux:button variant="ghost" href="{{ route('tools.index') }}#tools">
                        &larr; Alle tools
                    </flux:button>
                </div>
            </div>
        </section>
    @endif
</x-layout>
