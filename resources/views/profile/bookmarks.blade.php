<x-profile-layout title="Favorieten" description="Bekijk je opgeslagen initiatieven en fiches.">
    @if($fiches->isEmpty())
        <div class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-[var(--color-border-light)] mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
            <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen fiches als favoriet gemarkeerd.</flux:text>
            <flux:button variant="primary" href="{{ route('initiatives.index') }}">
                Ontdek initiatieven
            </flux:button>
        </div>
    @else
        <div class="grid md:grid-cols-2 gap-6">
            @foreach($fiches as $fiche)
                <x-fiche-card :fiche="$fiche" />
            @endforeach
        </div>
    @endif
</x-profile-layout>
