<x-layout :title="$contributor->full_name" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('contributors.index') }}">Bijdragers</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $contributor->full_name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <div class="flex flex-col md:flex-row gap-8 items-start">
                <div class="md:w-64 text-center">
                    @if($contributor->avatar_path)
                        <div class="flex justify-center">
                            <img src="{{ $contributor->avatarUrl() }}" alt="{{ $contributor->full_name }}" class="w-32 h-32 rounded-full object-cover">
                        </div>
                    @else
                        <div class="flex justify-center">
                            <div class="w-32 h-32 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-4xl font-semibold">
                                {{ substr($contributor->first_name, 0, 1) }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex-1">
                    <span class="section-label section-label-hero">Bijdrager</span>
                    <h1 class="text-5xl mt-1">{{ $contributor->full_name }}</h1>
                    @if($contributor->function_title)
                        <p class="text-[var(--color-text-secondary)] mt-2">{{ $contributor->function_title }}</p>
                    @endif
                    @if($contributor->organisation)
                        <p class="font-medium mt-1">{{ $contributor->organisation }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Bio & Fiches --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            @if($contributor->bio)
                <div class="prose max-w-none mb-8">
                    {!! $contributor->bio !!}
                </div>
            @endif

            @if($contributor->fiches->isNotEmpty())
                <span class="section-label">Fiches</span>
                <h2 class="mb-6">Bijdragen van {{ $contributor->first_name }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($contributor->fiches as $fiche)
                        <x-fiche-card :fiche="$fiche" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layout>
