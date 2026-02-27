<x-layout title="Themakalender" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Themakalender</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Themakalender</span>
            <h1 class="text-5xl mt-1">Thema's en speciale momenten</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4">Ontdek thema's, feestdagen en speciale momenten doorheen het jaar.</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Content --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <flux:card class="text-center py-12">
                <flux:heading size="lg" class="mb-4 font-heading font-bold">In opbouw</flux:heading>
                <flux:text class="text-[var(--color-text-secondary)]">De themakalender wordt momenteel vernieuwd. Kom binnenkort terug!</flux:text>
            </flux:card>
        </div>
    </section>
</x-layout>
