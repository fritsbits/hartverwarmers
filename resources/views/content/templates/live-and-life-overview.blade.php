<x-layout title="Wonen en leven projectoverzicht">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumbs --}}
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <li><a href="{{ route('home') }}" class="hover:text-[var(--color-primary)]">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('tools.index') }}" class="hover:text-[var(--color-primary)]">Tools & inspiratie</a></li>
                <li>/</li>
                <li class="text-[var(--color-text-primary)] font-medium">Wonen en leven</li>
            </ol>
        </nav>

        <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Gids voor woonzorgcentra</p>
        <h1 class="text-5xl mt-1">Overzicht van aanpakken rond wonen en leven</h1>

        <div class="flex flex-col lg:flex-row gap-8 mt-8">
            <div class="lg:w-1/2">
                <hr class="mb-6 border-[var(--color-border-light)]">
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

        {{-- Models list --}}
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mt-12 mb-6">Projecten rond wonen en leven</h2>

        @include('content.templates._live-and-life-models-list')

        {{-- Curator --}}
        <hr class="my-8 border-[var(--color-border-light)]">
        <div class="flex flex-col md:flex-row gap-8 py-6">
            <div class="md:w-2/3">
                <div class="flex items-center gap-4 mb-4">
                    <img src="/img/avatar-frederik.jpg" alt="Frederik Vincx" class="w-14 h-14 rounded-full">
                    <div>
                        <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Curator</p>
                        <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Frederik Vincx</h3>
                    </div>
                </div>
                <p class="text-[var(--color-text-secondary)]">Frederik Vincx is sociaal ondernemer en ontwerper van software voor teams. Sinds enkele jaren focust hij zijn energie op woonzorgcentra. Hij is de ontwerper en bezieler van Hartverwarmers.</p>
            </div>
        </div>
    </div>
</x-layout>
