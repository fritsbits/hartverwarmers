<x-layout title="Videolessen" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tools & inspiratie</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Videolessen</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <span class="section-label section-label-hero">Videolessen</span>
            <h1 class="text-5xl mt-1">Lessenreeksen wonen &amp; leven in het woonzorgcentrum</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4">Leer op je eigen tempo over relatiegerichte zorg en zinvolle dagbesteding</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Lessenreeks 1 --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="md:w-5/12 md:order-1">
                    <a href="{{ route('content', 'lessenreeks/praktijkvoorbeeld-relatiegerichte-zorg-met-leefplan') }}">
                        <img src="https://images.prismic.io/soulcenterbe/d11b9137-b9ce-497f-b28f-97c8fa875b8f_lessenreeks-persoonsgerichte+zorg.jpg?auto=compress,format" alt="Lessenreeks persoonsgerichte zorg" class="w-full rounded-xl shadow-lg">
                    </a>
                </div>
                <div class="md:w-7/12 md:order-2">
                    <span class="section-label">Praktijkvoorbeeld</span>
                    <h2 class="mt-1">
                        <a href="{{ route('content', 'lessenreeks/praktijkvoorbeeld-relatiegerichte-zorg-met-leefplan') }}" class="hover:text-[var(--color-primary)]">Zo werk je persoonsgericht met een leefplan</a>
                    </h2>
                    <p class="text-[var(--color-text-secondary)] mt-3">Woonzorgcentrum Sint-Camillus uit Wevelgem werkt persoonsgericht met een leefplan voor iedere bewoner. De voorbije jaren werken ze een helder stappenplan uit om relatiegericht te werken. In deze lessenreeks delen ze hun volledige aanpak aan de hand van praktijkvoorbeelden.</p>
                    <div class="meta-group mt-4">
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            6 videos
                        </span>
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            21 minuten
                        </span>
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Intervisiebladen en leefplan canvas
                        </span>
                    </div>
                    <div class="mt-5">
                        <a href="{{ route('content', 'lessenreeks/praktijkvoorbeeld-relatiegerichte-zorg-met-leefplan') }}" class="cta-link">Start eerste les</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Lessenreeks 2 --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="md:w-7/12">
                    <span class="section-label">Stemmen & gids</span>
                    <h2 class="mt-1">
                        <a href="{{ route('content', 'lessenreeks/vijf-stemmen-vijf-ondersteuningsvragen') }}" class="hover:text-[var(--color-primary)]">Zo geef je bewoners een thuisgevoel</a>
                    </h2>
                    <p class="text-[var(--color-text-secondary)] mt-3">Maite Mallentjer is de auteur van het boek 'a sense of home', dat woonzorgcentra inspireert om bewoners een thuisgevoel te geven. Als team kan je heel praktisch aan de slag met Maite's concept 'vijf stemmen'. Vijf gedragingen van bewoners die een teken zijn van een ondersteuningsnood.</p>
                    <div class="meta-group mt-4">
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            5 videos
                        </span>
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            37 minuten
                        </span>
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Stemmengids
                        </span>
                    </div>
                    <div class="mt-5">
                        <a href="{{ route('content', 'lessenreeks/vijf-stemmen-vijf-ondersteuningsvragen') }}" class="cta-link">Start eerste les</a>
                    </div>
                </div>
                <div class="md:w-5/12">
                    <a href="{{ route('content', 'lessenreeks/vijf-stemmen-vijf-ondersteuningsvragen') }}">
                        <img src="/img/content/gids-maite-stemmen.jpg" alt="Stemmengids" class="w-full rounded-xl shadow-lg">
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layout>
