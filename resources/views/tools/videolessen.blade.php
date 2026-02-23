<x-layout title="Videolessen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumbs --}}
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <li><a href="{{ route('home') }}" class="hover:text-[var(--color-primary)]">Home</a></li>
                <li>/</li>
                <li><a href="{{ route('tools.index') }}" class="hover:text-[var(--color-primary)]">Tools & inspiratie</a></li>
                <li>/</li>
                <li class="text-[var(--color-text-primary)] font-medium">Videolessen</li>
            </ol>
        </nav>

        <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Videolessen</p>
        <h1 class="text-5xl mt-1">Lessenreeksen wonen &amp; leven in het woonzorgcentrum</h1>
        <p class="text-2xl text-[var(--color-text-secondary)] mt-4 mb-10">Leer op je eigen tempo over relatiegerichte zorg en zinvolle dagbesteding</p>

        {{-- Lessenreeks 1 --}}
        <div class="flex flex-col md:flex-row items-center gap-8 py-8">
            <div class="md:w-5/12 md:order-1">
                <a href="{{ route('content', 'lessenreeks/praktijkvoorbeeld-relatiegerichte-zorg-met-leefplan') }}">
                    <img src="https://images.prismic.io/soulcenterbe/d11b9137-b9ce-497f-b28f-97c8fa875b8f_lessenreeks-persoonsgerichte+zorg.jpg?auto=compress,format" alt="Lessenreeks persoonsgerichte zorg" class="w-full rounded-xl shadow-lg">
                </a>
            </div>
            <div class="md:w-7/12 md:order-2">
                <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Werken met het leefplan</p>
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mt-1">
                    <a href="{{ route('content', 'lessenreeks/praktijkvoorbeeld-relatiegerichte-zorg-met-leefplan') }}" class="hover:text-[var(--color-primary)]">Persoonsgerichte zorg in de praktijk</a>
                </h2>
                <p class="text-[var(--color-text-secondary)] mt-3">Woonzorgcentrum Sint-Camillus uit Wevelgem werkt persoonsgericht met een leefplan voor iedere bewoner. De voorbije jaren werken ze een helder stappenplan uit om relatiegericht te werken. In deze lessenreeks delen ze hun volledige aanpak aan de hand van praktijkvoorbeelden.</p>
                <div class="flex flex-wrap gap-4 mt-4 text-sm text-[var(--color-text-secondary)]">
                    <span class="inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        6 videos
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        21 minuten
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Intervisiebladen en leefplan canvas
                    </span>
                </div>
                <div class="mt-5">
                    <flux:button variant="primary" href="{{ route('content', 'lessenreeks/praktijkvoorbeeld-relatiegerichte-zorg-met-leefplan') }}">
                        Start eerste les &rarr;
                    </flux:button>
                </div>
            </div>
        </div>

        <hr class="my-10 border-[var(--color-border-light)]">

        {{-- Lessenreeks 2 --}}
        <div class="flex flex-col md:flex-row items-center gap-8 py-8">
            <div class="md:w-7/12">
                <p class="text-sm font-semibold text-[var(--color-primary)] uppercase tracking-wide">Dialoog met bewoners</p>
                <h2 class="text-2xl font-bold text-[var(--color-text-primary)] mt-1">
                    <a href="{{ route('content', 'lessenreeks/vijf-stemmen-vijf-ondersteuningsvragen') }}" class="hover:text-[var(--color-primary)]">Stemmengids thuisgevoel (Sense of home)</a>
                </h2>
                <p class="text-[var(--color-text-secondary)] mt-3">Maite Mallentjer is de auteur van het boek 'a sense of home', dat woonzorgcentra inspireert om bewoners een thuisgevoel te geven. Als team kan je heel praktisch aan de slag met Maite's concept 'vijf stemmen'. Vijf gedragingen van bewoners die een teken zijn van een ondersteuningsnood.</p>
                <div class="flex flex-wrap gap-4 mt-4 text-sm text-[var(--color-text-secondary)]">
                    <span class="inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        5 videos
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        37 minuten
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Stemmengids
                    </span>
                </div>
                <div class="mt-5">
                    <flux:button variant="primary" href="{{ route('content', 'lessenreeks/vijf-stemmen-vijf-ondersteuningsvragen') }}">
                        Start eerste les &rarr;
                    </flux:button>
                </div>
            </div>
            <div class="md:w-5/12">
                <a href="{{ route('content', 'lessenreeks/vijf-stemmen-vijf-ondersteuningsvragen') }}">
                    <img src="/img/content/gids-maite-stemmen.jpg" alt="Stemmengids" class="w-full rounded-xl shadow-lg">
                </a>
            </div>
        </div>
    </div>
</x-layout>
