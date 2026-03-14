<x-layout title="Over ons" description="Hartverwarmers is een gratis platform van en voor activiteitenbegeleiders in de ouderenzorg. Ontdek het verhaal, de community en het DIAMANT-model." :full-width="true">

    {{-- Block 1 — Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label section-label-hero">Over Hartverwarmers</span>
            <h1 class="mt-1">Meer tijd voor wat écht telt</h1>
            <p class="text-2xl text-[var(--color-text-secondary)] mt-4" style="font-weight: var(--font-weight-light);">
                Hartverwarmers bestaat zodat jij je tijd kunt steken in wat écht telt — de mensen in jouw zorg. Niet in het uitdenken en uitwerken van activiteiten. Dat doen we samen: een community van activiteitenbegeleiders die hun beste ideeën deelt, zodat jij het warm water niet telkens opnieuw hoeft uit te vinden.
            </p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 2 — Community --}}
    <section>
        <div class="max-w-5xl mx-auto px-6 py-16">
            <span class="section-label">De hartverwarmers</span>
            <h2 class="mt-1 mb-4">Samen maken we het verschil</h2>
            <p class="text-[var(--color-text-secondary)] max-w-3xl" style="font-weight: var(--font-weight-light);">
                Hartverwarmers is niet het werk van één organisatie. Het is het werk van honderden activiteitenbegeleiders, animatoren en ergotherapeuten uit heel Vlaanderen en Nederland — mensen die elke dag harten verwarmen en hun beste ideeën delen met de rest van de sector.
            </p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-10">
                <div class="text-center">
                    <p class="text-4xl font-heading font-bold text-[var(--color-primary)]">{{ number_format($aboutStats['fiches_count']) }}</p>
                    <p class="text-[var(--color-text-secondary)] mt-1">praktijkfiches</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-heading font-bold text-[var(--color-primary)]">{{ number_format($aboutStats['contributors_count']) }}</p>
                    <p class="text-[var(--color-text-secondary)] mt-1">hartverwarmers</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-heading font-bold text-[var(--color-primary)]">{{ number_format($aboutStats['users_count']) }}+</p>
                    <p class="text-[var(--color-text-secondary)] mt-1">gebruikers</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-heading font-bold text-[var(--color-primary)]">Gratis</p>
                    <p class="text-[var(--color-text-secondary)] mt-1">toegankelijk</p>
                </div>
            </div>

            <a href="{{ route('contributors.index') }}" class="cta-link mt-8 inline-block">Ontdek wie er bijdraagt</a>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 3 — Story + People (side by side) --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-5xl mx-auto px-6 py-16">
            <span class="section-label">Het verhaal</span>
            <h2 class="mt-1 mb-8">Geboren in één week, tijdens de eerste lockdown</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                {{-- Left: the story --}}
                <div class="text-[var(--color-text-secondary)] space-y-4" style="font-weight: var(--font-weight-light);">
                    <p>Maart 2020. Woonzorgcentra waren plots volledig afgesloten. Op sociale media deelden medewerkers creatieve manieren om bewoners — ondanks alles — een mooie dag te geven. Raamoptredens door muzikanten. Hobbykarren die langs de kamers trokken. Bingo vanuit de deuropening. Die energie mocht niet verloren gaan.</p>
                    <p>In één week bouwden we Hartverwarmers: een plek om die initiatieven te bundelen, zodat elk woonzorgcentrum kon leren van wat elders werkte. Wat begon als een crisisinitiatief, is vijf jaar later nog steeds springlevend. Elke maand vinden zo'n 50 nieuwe activiteitenbegeleiders de weg naar het platform.</p>
                </div>

                {{-- Right: the people --}}
                <div class="space-y-6">
                    <div class="flex gap-4 items-start">
                        <div class="shrink-0 w-16 h-16 rounded-full overflow-hidden bg-white shadow-sm">
                            <img src="/img/about/lancering-boek.jpg" alt="Frederik Vincx" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p class="font-semibold text-[var(--color-text-primary)]">Frederik Vincx</p>
                            <p class="text-sm text-[var(--color-text-secondary)]">Oprichter</p>
                            <p class="text-[var(--color-text-secondary)] mt-1" style="font-weight: var(--font-weight-light);">Bouwde Hartverwarmers in 2020 vanuit Soulcenter, zijn toenmalig softwarebedrijf voor woonzorgcentra. Draagt het platform sindsdien vrijwillig en persoonlijk.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start">
                        <div class="shrink-0 w-16 h-16 rounded-full overflow-hidden bg-white shadow-sm">
                            <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p class="font-semibold text-[var(--color-text-primary)]">Maite Mallentjer</p>
                            <p class="text-sm text-[var(--color-text-secondary)]">Pedagoog dagbesteding, AP Hogeschool Antwerpen</p>
                            <p class="text-[var(--color-text-secondary)] mt-1" style="font-weight: var(--font-weight-light);">Hielp Hartverwarmers mee vormgeven bij de lancering in 2020. Brengt in 2026 haar expertise in via het <a href="{{ route('goals.index') }}" class="underline hover:text-[var(--color-primary)]">DIAMANT-model</a> — een kwaliteitskader dat ons helpt toe te werken naar betere activiteiten.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Full-width lancering photo --}}
    <figure class="relative">
        <img src="/img/about/lancering-boek.jpg" alt="Boekvoorstelling van het Hartverwarmers boek" class="w-full h-64 md:h-96 object-cover">
        <figcaption class="bg-[var(--color-bg-subtle)] px-6 py-3 text-center text-sm text-[var(--color-text-secondary)]">
            Boekvoorstelling van <em>Hartverwarmers — Deugddoende activiteiten voor woonzorgcentra</em>, 2021.
        </figcaption>
    </figure>

    {{-- Book --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-4xl mx-auto px-6 py-12">
            <div class="flex gap-6 items-start">
                <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="shrink-0">
                    <img src="/img/covers/hartverwarmers.jpg" alt="Hartverwarmers boekcover" class="w-28 shadow-md" style="transform: rotate(-2deg);">
                </a>
                <div>
                    <p class="text-lg font-semibold">Hartverwarmers — Deugddoende activiteiten voor woonzorgcentra</p>
                    <p class="text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">Politeia, 2021</p>
                    <p class="text-[var(--color-text-secondary)] mt-2">Het boek bundelt een selectie van de beste activiteiten en legt het fundament van het DIAMANT-model uit.</p>
                    <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="cta-link mt-2 inline-block">Bekijk bij Standaard Boekhandel</a>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Video --}}
    <section>
        <div class="max-w-5xl mx-auto px-6 py-16">
            <div class="aspect-video rounded-xl overflow-hidden shadow-lg">
                <iframe src="https://www.youtube-nocookie.com/embed/k8zetWJ-Pro" title="Hartverwarmers — het ontstaan" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
            </div>
            <p class="text-[var(--color-text-secondary)] mt-4 text-center text-sm">
                Mei 2020 — amper twee maanden na de lancering. Hartverwarmers had toen al meer dan 25.000 bezoekers en ruim 100 ingediende activiteiten.
            </p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Support CTA + secondary CTAs --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-4xl mx-auto px-6 py-16 space-y-16">

            {{-- Primary CTA — Steun --}}
            <div x-data="{ open: false }">
                <span class="section-label">Steun Hartverwarmers</span>
                <h2 class="mt-1 mb-4">Doe een gift</h2>
                <p class="text-[var(--color-text-secondary)] max-w-2xl" style="font-weight: var(--font-weight-light);">
                    Hartverwarmers is een vrijwillig project — de domeinnaam, server, e-maildienst en technische infrastructuur worden persoonlijk bekostigd. Wil je dit platform steunen of een samenwerking verkennen? Laat het weten. Elke bijdrage, hoe klein ook, helpt om de vaste kosten te dekken.
                </p>
                <div class="mt-6">
                    <button @click="open = !open" class="btn-pill text-lg px-8 py-3" x-text="open ? 'Sluiten' : 'Neem contact op'"></button>
                </div>
                <div x-show="open" x-collapse x-cloak class="mt-6">
                    <livewire:support-contact-form />
                </div>
            </div>

            <hr class="border-[var(--color-border-light)]">

            {{-- Secondary CTA — Bijdragen --}}
            <div>
                <h3>Deel jouw activiteit</h3>
                <p class="text-[var(--color-text-secondary)] mt-2 max-w-2xl" style="font-weight: var(--font-weight-light);">
                    Heb jij een activiteit die werkt? Voeg ze toe aan de databank. Zo word jij ook een hartverwarmer — en help jij een collega die je misschien nooit zal ontmoeten.
                </p>
                <a href="{{ route('fiches.create') }}" class="cta-link mt-3 inline-block">Nieuwe fiche toevoegen</a>
            </div>

            {{-- Tertiary CTA — Delen --}}
            <div x-data="{ copied: false, async share() { const data = { title: 'Hartverwarmers', url: window.location.origin }; try { if (navigator.share) { await navigator.share(data); } else { await navigator.clipboard.writeText(data.url); this.copied = true; setTimeout(() => this.copied = false, 2000); } } catch (e) {} } }">
                <h3>Verspreid het woord</h3>
                <p class="text-[var(--color-text-secondary)] mt-2 max-w-2xl" style="font-weight: var(--font-weight-light);">
                    Ken jij een collega, een animator, een ergotherapeut die dit platform zou gebruiken? Stuur hen deze pagina. Hoe meer hartverwarmers, hoe rijker de community.
                </p>
                <button @click="share()" class="btn-pill mt-4">
                    <span x-show="!copied">Deel Hartverwarmers</span>
                    <span x-show="copied" x-cloak>Link gekopieerd!</span>
                </button>
            </div>

        </div>
    </section>
</x-layout>
