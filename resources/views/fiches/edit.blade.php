<x-layout title="Fiche bewerken" :full-width="true">
    {{-- Hero — cream background --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-12">
            {{-- Breadcrumbs --}}
            <div class="mb-6">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                    @if($fiche->initiative)
                        <flux:breadcrumbs.item href="{{ route('initiatives.show', $fiche->initiative) }}">{{ $fiche->initiative->title }}</flux:breadcrumbs.item>
                        <flux:breadcrumbs.item href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}">{{ $fiche->title }}</flux:breadcrumbs.item>
                    @else
                        <flux:breadcrumbs.item>{{ $fiche->title }}</flux:breadcrumbs.item>
                    @endif
                    <flux:breadcrumbs.item>Bewerken</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>

            <span class="section-label section-label-hero">Fiche bewerken</span>
            <h1 class="mt-3">{{ $fiche->title }}</h1>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Form section — white background --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-12">
            <livewire:fiche-edit :fiche="$fiche" />
        </div>
    </section>
</x-layout>
