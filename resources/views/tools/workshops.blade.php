<x-layout title="Workshops" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tools & inspiratie</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Workshops</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <span class="section-label section-label-hero">Workshops</span>
            <h1 class="text-5xl mt-1">Doe-het-zelf-workshops Wonen &amp; leven in het woonzorgcentrum</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4">Geef je team extra steun om persoonsgericht te werken. Geef hen deze volledig uitgewerkte workshops, telkens met stappenplan en werkbladen</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Visie --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Visie</span>
            <h2 class="mb-6">Krijg de neuzen in dezelfde richting</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($workshopsVisie as $workshop)
                    @include('tools._workshop', ['workshop' => $workshop])
                @endforeach
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Proces --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Proces</span>
            <h2 class="mb-6">Evalueer jullie aanpak en stuur bij</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($workshopsProces as $workshop)
                    @include('tools._workshop', ['workshop' => $workshop])
                @endforeach
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Activiteiten --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Activiteiten</span>
            <h2 class="mb-6">Vernieuw het aanbod met frisse ideeën</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($workshopsActiviteiten as $workshop)
                    @include('tools._workshop', ['workshop' => $workshop])
                @endforeach
            </div>
        </div>
    </section>
</x-layout>
