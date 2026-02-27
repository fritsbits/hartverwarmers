<x-layout title="Wonen en leven projectoverzicht" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tools & inspiratie</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('tools.index') }}">Gidsen en tools</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Wonen en leven</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Gids voor woonzorgcentra</span>
            <h1 class="text-5xl mt-1">Overzicht van aanpakken rond wonen en leven</h1>

            <div class="flex flex-col lg:flex-row gap-8 mt-8">
                <div class="lg:w-1/2">
                    <p class="text-2xl text-[var(--color-text-secondary)]">Kwaliteit van zorg, dat gaat steeds vaker over de kwaliteit van iemands ervaring en beleving. Het gaat over dagelijkse gewoontes, gebruikelijke ritmes, wensen en mogelijkheden. Over wat kan, opnieuw kan, wat mag en mogelijk wordt. Ondanks zorgen.</p>
                    <p class="text-[var(--color-text-secondary)] mt-4">Dat 'goed wonen en leven' kreeg de afgelopen jaren steeds meer aandacht. Er startten projecten en er verschenen tal van methodieken, aanpakken en modellen.</p>

                    <h3 class="font-semibold text-[var(--color-text-primary)] mt-8 mb-4">Wat je kan verwachten:</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-[var(--color-text-secondary)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Helicopterzicht over aanpakken rond wonen en leven
                        </li>
                        <li class="flex items-start gap-3 text-[var(--color-text-secondary)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Toelichting van positieve effecten en kernconcepten
                        </li>
                    </ul>
                </div>

                <div class="lg:w-1/2 text-center">
                    <img src="/img/content/boek-samenvattingen.jpg" alt="Samenvattingen wonen en leven" class="max-w-xs mx-auto rounded-lg shadow mb-4">
                    <h2 class="text-xl font-bold text-[var(--color-text-primary)]">Download e-book</h2>
                    <p class="text-[var(--color-text-secondary)] mt-1">Druk het overzicht af om later te lezen</p>
                    <flux:button variant="primary" href="https://prismic-io.s3.amazonaws.com/soulcenterbe/765eb76c-8d99-49b2-86e2-9f7f4793bc79_Wonen+en+leven+projectoverzicht+%E2%80%93%C2%A0Soulcenter.pdf" target="_blank" class="mt-3" size="sm">
                        Download e-book
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Models list --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Modellen</span>
            <h2 class="mb-6">Projecten rond wonen en leven</h2>

            @include('content.templates._live-and-life-models-list')
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Curator --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex flex-col md:flex-row gap-8">
                <div class="md:w-2/3">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="/img/avatar-frederik.jpg" alt="Frederik Vincx" class="w-14 h-14 rounded-full">
                        <div>
                            <span class="section-label">Curator</span>
                            <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Frederik Vincx</h3>
                        </div>
                    </div>
                    <p class="text-[var(--color-text-secondary)]">Frederik Vincx is sociaal ondernemer en ontwerper van software voor teams. Sinds enkele jaren focust hij zijn energie op woonzorgcentra. Hij is de ontwerper en bezieler van Hartverwarmers.</p>
                </div>
            </div>
        </div>
    </section>
</x-layout>
