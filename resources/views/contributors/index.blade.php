<x-layout title="Bijdragers" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Bijdragers</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Bijdragers</span>
            <h1 class="text-5xl mt-1">Onze bijdragers</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4">Ontmoet de activiteitenbegeleiders die hun kennis en ervaring delen.</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Contributors Grid --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            @if($contributors->isEmpty())
                <div class="text-center py-12">
                    <flux:text class="text-[var(--color-text-secondary)]">Nog geen bijdragers.</flux:text>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($contributors as $contributor)
                        <a href="{{ route('contributors.show', $contributor) }}">
                            <flux:card class="hover:shadow-md transition-shadow text-center">
                                @if($contributor->avatar_path)
                                    <div class="flex justify-center mb-4">
                                        <img src="{{ Storage::url($contributor->avatar_path) }}" alt="{{ $contributor->full_name }}" class="w-20 h-20 rounded-full object-cover">
                                    </div>
                                @else
                                    <div class="flex justify-center mb-4">
                                        <div class="w-20 h-20 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-2xl font-semibold">
                                            {{ substr($contributor->first_name, 0, 1) }}
                                        </div>
                                    </div>
                                @endif

                                <flux:heading size="lg" class="mt-4">{{ $contributor->full_name }}</flux:heading>
                                @if($contributor->function_title)
                                    <flux:text class="text-[var(--color-text-secondary)] text-sm">{{ $contributor->function_title }}</flux:text>
                                @endif
                                @if($contributor->organisation)
                                    <flux:text class="text-sm">{{ $contributor->organisation }}</flux:text>
                                @endif
                            </flux:card>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layout>
