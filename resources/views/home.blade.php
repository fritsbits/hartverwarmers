<x-layout title="Laat je bewoners schitteren" :full-width="true">
    <!-- Hero Section -->
    <section class="bg-[var(--color-bg-cream)] text-center">
        <div class="py-20 max-w-3xl mx-auto px-6">
            <h1 class="text-5xl mb-4">Laat je bewoners schitteren</h1>
            <p class="text-[var(--color-text-secondary)] text-2xl font-light mb-8">
                Hartverwarmers helpt begeleiders in woonzorgcentra om elke dag iets betekenisvols mogelijk te maken. Met {{ $stats['initiatives'] }} initiatieven gedeeld door {{ $stats['contributors'] }} collega's.
            </p>

            <!-- Search bar (opens command palette modal) -->
            <div class="max-w-xl mx-auto">
                <flux:modal.trigger name="search">
                    <flux:input as="button" icon="magnifying-glass" placeholder="Zoek initiatieven en fiches..." class="!rounded-full !py-3.5 !text-base" />
                </flux:modal.trigger>
            </div>
        </div>
    </section>

    <!-- Featured Initiatives -->
    @if($initiatives->isNotEmpty())
        <section class="bg-[var(--color-bg-base)]">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <div class="mb-10">
                    <span class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                        </svg>
                        Ontdek initiatieven
                    </span>
                    <h2 class="text-3xl">Maak vandaag het verschil</h2>
                    <p class="text-[var(--color-text-secondary)] mt-2">Blader door activiteiten die werken in woonzorgcentra en laat je inspireren</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($initiatives->take(3) as $initiative)
                        <x-initiative-card :initiative="$initiative" />
                    @endforeach
                </div>

                <div class="text-center mt-10">
                    <a href="{{ route('initiatives.index') }}" class="cta-link">
                        Bekijk alle initiatieven
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- Recent Fiches -->
    @if($recentFiches->isNotEmpty())
        <section class="bg-[var(--color-bg-base)]">
            <div class="max-w-6xl mx-auto px-6 pb-16">
                <div class="mb-10">
                    <span class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z" />
                        </svg>
                        Uitgewerkte fiches
                    </span>
                    <h2 class="text-3xl">Activiteiten gedeeld door collega's</h2>
                    <p class="text-[var(--color-text-secondary)] mt-2">Kant-en-klare fiches van begeleiders uit andere woonzorgcentra — direct bruikbaar in jouw werking</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Left: Recent --}}
                    <div>
                        <h3 class="text-lg font-heading font-bold mb-4">Nieuwste fiches</h3>
                        <div class="space-y-2">
                            @foreach($recentFiches->take(4) as $fiche)
                                <a href="{{ route('fiches.show', [$fiche->initiative, $fiche]) }}" class="fiche-list-item">
                                    <span class="fiche-list-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                    </span>
                                    <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                        <span class="font-body font-semibold text-base text-[var(--color-text-primary)] truncate">{{ $fiche->title }}</span>
                                        <span class="text-xs text-[var(--color-text-secondary)]">{{ $fiche->user?->full_name }}</span>
                                    </div>
                                    <span class="flex items-center gap-2.5 shrink-0">
                                        <span class="fiche-list-kudos @if($fiche->kudos_count > 0) fiche-list-kudos-active @endif">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                                            {{ $fiche->kudos_count }}
                                        </span>
                                        <span class="fiche-list-kudos">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z"/></svg>
                                            {{ $fiche->comments_count }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Right: Diamant van de maand --}}
                    @if($diamantFiche)
                        <div class="flex flex-col">
                            <h3 class="text-lg font-heading font-bold mb-4">Fiche van de maand</h3>
                            @php $previews = $diamantFiche->cardPreviewImages(3); @endphp
                            <a href="{{ route('fiches.show', [$diamantFiche->initiative, $diamantFiche]) }}" class="group flex flex-col flex-1 no-underline text-inherit rounded-[var(--radius-sm)] border border-[var(--color-border-light)] bg-[var(--color-bg-white)] overflow-hidden hover:shadow-card-hover hover:-translate-y-0.5 hover:border-[var(--color-border-hover)] transition-all duration-200">
                                @if(count($previews) > 0)
                                    <div class="fiche-card-header relative bg-[var(--color-bg-cream)] flex-1" style="overflow: hidden; min-height: 180px;">
                                        @foreach($previews as $i => $url)
                                            <div class="fiche-paper fiche-paper-{{ $i }}" style="z-index: {{ $i + 1 }}">
                                                <img src="{{ $url }}" alt="" loading="lazy" draggable="false">
                                            </div>
                                        @endforeach
                                        <div class="absolute top-3.5 left-0 z-10 flex items-center gap-2 pl-3 pr-4 py-1.5 text-sm font-semibold text-white bg-[var(--color-primary)] rounded-r-full shadow-md">
                                            <x-diamant-gem letter="" size="xxs" :inverted="true" />
                                            Diamantje
                                        </div>
                                    </div>
                                @endif
                                <div class="px-4 py-4 flex flex-col flex-1">
                                    <span class="font-body font-bold text-lg text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)] transition-colors">{{ $diamantFiche->title }}</span>
                                    <div class="mt-3 flex items-center text-sm">
                                        @if($diamantFiche->user)
                                            <div class="flex items-center gap-2.5">
                                                @if($diamantFiche->user->avatar_path)
                                                    <img src="{{ Storage::url($diamantFiche->user->avatar_path) }}" alt="{{ $diamantFiche->user->first_name }}" class="w-8 h-8 rounded-full object-cover shrink-0">
                                                @else
                                                    <div class="w-8 h-8 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-xs font-semibold shrink-0">
                                                        {{ strtoupper(substr($diamantFiche->user->first_name, 0, 1)) }}
                                                    </div>
                                                @endif
                                                <div class="flex flex-col">
                                                    <span class="font-semibold text-[var(--color-text-primary)] leading-tight">{{ $diamantFiche->user->full_name }}</span>
                                                    @if($diamantFiche->user->organisation)
                                                        <span class="text-xs text-[var(--color-text-secondary)] leading-tight">{{ $diamantFiche->user->organisation }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        <span class="ml-auto flex items-center gap-3">
                                            <span class="fiche-list-kudos @if($diamantFiche->kudos_count > 0) fiche-list-kudos-active @endif">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                                                {{ $diamantFiche->kudos_count }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z"/></svg>
                                                {{ $diamantFiche->comments_count }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <!-- DIAMANT Kompas -->
    @feature('diamant-goals')
    <section class="bg-[var(--color-bg-cream)] py-16">
        <div class="max-w-6xl mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-12 items-start">
                <div>
                    <span class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                        </svg>
                        Het DIAMANT-kompas
                    </span>

                    <h2 class="text-3xl mb-3">Zeven doelen om bewoners te laten schitteren</h2>
                    <p class="text-[var(--color-text-secondary)] max-w-2xl mb-6">
                        Het DIAMANT-kompas is het onderliggende kwaliteitskader van Hartverwarmers. Het helpt je kijken naar wat een activiteit écht waardevol maakt — voor deze bewoner, op dit moment. Elke letter van DIAMANT staat voor een manier van kijken.
                    </p>

                    <a href="{{ route('goals.index') }}" class="cta-link">Meer over het DIAMANT-model</a>
                </div>

                <div class="flex flex-col items-center justify-center">
                    <div class="flex gap-4 items-start">
                        <figure class="photo-polaroid" style="transform: rotate(-3deg)">
                            <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-36 aspect-square object-cover">
                            <figcaption>Maite Mallentjer</figcaption>
                        </figure>
                        <figure class="photo-polaroid -mt-2" style="transform: rotate(2deg)">
                            <img src="/img/wonen-en-leven/nadinepraet.jpg" alt="Nadine Praet" class="w-36 aspect-square object-cover">
                            <figcaption>Nadine Praet</figcaption>
                        </figure>
                    </div>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-4 text-center max-w-80">
                        Ontwikkeld door Maite Mallentjer (AP Hogeschool) en Nadine Praet (Arteveldehogeschool Gent).
                    </p>
                </div>
            </div>
        </div>
    </section>
    @endfeature
</x-layout>
