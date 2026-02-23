<x-layout title="Mijn bookmarks">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <!-- Breadcrumb -->
        <flux:breadcrumbs class="mb-6">
            <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('profile.show') }}">Profiel</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Bookmarks</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <h1 class="text-3xl font-bold mb-8">Mijn bookmarks</h1>

        @if($elaborations->isEmpty())
            <div class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-[var(--color-border-light)] mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <flux:text class="text-[var(--color-text-secondary)] mb-4">Je hebt nog geen uitwerkingen gebookmarkt.</flux:text>
                <flux:button variant="primary" href="{{ route('initiatives.index') }}">
                    Ontdek initiatieven
                </flux:button>
            </div>
        @else
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($elaborations as $elaboration)
                    <x-elaboration-card :elaboration="$elaboration" />
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
