<x-layout :title="$contributor->full_name">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <flux:breadcrumbs class="mb-6">
            <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('contributors.index') }}">Bijdragers</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $contributor->full_name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-col md:flex-row gap-8 items-start">
            <!-- Contributor Info -->
            <div class="md:w-64 text-center">
                @if($contributor->avatar_path)
                    <div class="flex justify-center">
                        <img src="{{ Storage::url($contributor->avatar_path) }}" alt="{{ $contributor->full_name }}" class="w-32 h-32 rounded-full object-cover">
                    </div>
                @else
                    <div class="flex justify-center">
                        <div class="w-32 h-32 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-4xl font-semibold">
                            {{ substr($contributor->first_name, 0, 1) }}
                        </div>
                    </div>
                @endif

                <h1 class="text-5xl mt-4">{{ $contributor->full_name }}</h1>
                @if($contributor->function_title)
                    <flux:text class="text-[var(--color-text-secondary)]">{{ $contributor->function_title }}</flux:text>
                @endif
                @if($contributor->organisation)
                    <p class="font-medium mt-2">{{ $contributor->organisation }}</p>
                @endif
            </div>

            <!-- Bio & Fiches -->
            <div class="flex-1">
                @if($contributor->bio)
                    <div class="prose max-w-none mb-8">
                        {!! $contributor->bio !!}
                    </div>
                @endif

                @if($contributor->fiches->isNotEmpty())
                    <flux:heading size="lg" class="mb-4">Fiches</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($contributor->fiches as $fiche)
                            <x-fiche-card :fiche="$fiche" />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout>
