<x-layout :title="$tool['title']">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumbs --}}
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <li><a href="{{ route('home') }}" class="hover:text-[var(--color-primary)]">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('tools.index') }}" class="hover:text-[var(--color-primary)]">Gidsen en tools</a></li>
                <li>/</li>
                <li class="text-[var(--color-text-primary)] font-medium">{{ $tool['title'] }}</li>
            </ol>
        </nav>

        {{-- Hero --}}
        <div class="flex flex-col md:flex-row items-start gap-8">
            <div class="flex-1">
                <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Tool</p>
                <h1 class="text-5xl mt-1">{{ $tool['title'] }}</h1>
                <p class="text-2xl text-[var(--color-text-secondary)] mt-4">{{ $tool['hero_description'] ?? $tool['teaser'] }}</p>
                @isset($tool['links'])
                    @foreach($tool['links'] as $link)
                        <flux:button variant="primary" href="{{ $link['url'] }}" class="mt-4">
                            {{ $link['label'] }} &rarr;
                        </flux:button>
                    @endforeach
                @endisset
            </div>
            @isset($tool['hero_image'])
                <div class="md:w-5/12 flex-shrink-0">
                    <img src="{{ $tool['hero_image'] }}" alt="{{ $tool['title'] }}" class="w-full rounded-lg">
                </div>
            @endisset
        </div>

        {{-- Goal --}}
        @if(!empty($tool['goal']))
            <div class="flex flex-col md:flex-row gap-8 mt-12 py-8">
                <div class="md:w-1/4">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Waar dient het voor?</h2>
                </div>
                <div class="md:w-3/4 columns-1 sm:columns-2 gap-8">
                    @foreach($tool['goal'] as $goalItem)
                        <p class="text-[var(--color-text-secondary)] mb-4 break-inside-avoid">{{ $goalItem }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Steps --}}
        @if(!empty($tool['steps']))
            <hr class="my-10 border-[var(--color-border-light)]">
            <div class="flex flex-col md:flex-row gap-8 pt-4">
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
        @endif

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
</x-layout>
