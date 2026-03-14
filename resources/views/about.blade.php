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

    {{-- Block 2 — Community (stats woven into prose) --}}
    <section>
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label">De hartverwarmers</span>
            <h2 class="mt-1 mb-4">Samen maken we het verschil</h2>
            <p class="text-lg text-[var(--color-text-secondary)] max-w-3xl" style="font-weight: var(--font-weight-light);">
                Hartverwarmers is niet het werk van één organisatie. Vandaag delen <strong class="text-[var(--color-text-primary)]">{{ number_format($aboutStats['contributors_count']) }} hartverwarmers</strong> uit heel Vlaanderen en Nederland hun <strong class="text-[var(--color-text-primary)]">{{ number_format($aboutStats['fiches_count']) }} praktijkfiches</strong> — gratis, voor meer dan <strong class="text-[var(--color-text-primary)]">{{ number_format($aboutStats['users_count']) }}+ collega's</strong>. Activiteitenbegeleiders, animatoren en ergotherapeuten die elke dag harten verwarmen en hun beste ideeën delen met de rest van de sector.
            </p>
            <a href="{{ route('contributors.index') }}" class="cta-link mt-4 inline-block">Ontdek wie er bijdraagt</a>
        </div>
    </section>

    {{-- Block 3 — Story + People (side by side) --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-5xl mx-auto px-6 py-20">
            <span class="section-label">Het verhaal</span>
            <h2 class="mt-1 mb-8">Geboren in één week, tijdens de eerste lockdown</h2>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
                {{-- Left: the story (takes 3 of 5 columns) --}}
                <div class="lg:col-span-3 text-[var(--color-text-secondary)] space-y-4" style="font-weight: var(--font-weight-light);">
                    <p>Maart 2020. Woonzorgcentra waren plots volledig afgesloten. Op sociale media deelden medewerkers creatieve manieren om bewoners — ondanks alles — een mooie dag te geven. Raamoptredens door muzikanten. Hobbykarren die langs de kamers trokken. Bingo vanuit de deuropening. Die energie mocht niet verloren gaan.</p>
                    <p>In één week bouwden we Hartverwarmers: een plek om die initiatieven te bundelen, zodat elk woonzorgcentrum kon leren van wat elders werkte. Wat begon als een crisisinitiatief, is vijf jaar later nog steeds springlevend. Elke maand vinden zo'n 50 nieuwe activiteitenbegeleiders de weg naar het platform.</p>
                    <p class="text-[var(--color-text-primary)] mt-6" style="font-weight: 400;">— <a href="https://www.frederikvincx.com/" target="_blank" rel="noopener noreferrer" class="underline hover:text-[var(--color-primary)]">Frederik Vincx</a>, oprichter</p>
                </div>

                {{-- Right: the people (takes 2 of 5 columns) --}}
                <div class="lg:col-span-2">
                    <span class="section-label text-sm !text-[var(--color-text-secondary)]" style="font-size: 12px;">De mensen erachter</span>

                    <figure class="photo-polaroid mt-4 inline-block" style="transform: rotate(2deg)">
                        <img src="/img/about/frederik-vincx.webp" alt="Frederik Vincx" class="w-full aspect-[4/3] object-cover object-top">
                        <figcaption><strong class="text-[var(--color-text-primary)]">Frederik Vincx</strong><br><small>Oprichter</small></figcaption>
                    </figure>

                    <figure class="photo-polaroid mt-6 inline-block" style="transform: rotate(-1.5deg)">
                        <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-full aspect-square object-cover">
                        <figcaption><strong class="text-[var(--color-text-primary)]">Maite Mallentjer</strong><br><small>Pedagoog dagbesteding</small></figcaption>
                    </figure>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-4" style="font-weight: var(--font-weight-light);">Maite hielp Hartverwarmers mee vormgeven bij de lancering in 2020. In 2026 brengt ze haar expertise in via het <a href="{{ route('goals.index') }}" class="underline hover:text-[var(--color-primary)]">DIAMANT-model</a> — een kwaliteitskader dat ons helpt toe te werken naar betere activiteiten.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Book presentation photo as large polaroid --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-4xl mx-auto px-6 pb-16">
            <figure class="photo-polaroid mx-auto" style="transform: rotate(-0.5deg); max-width: 56rem;">
                <img src="/img/about/lancering-boek.jpg" alt="Boekvoorstelling van het Hartverwarmers boek" class="w-full">
                <figcaption class="!text-base">Boekvoorstelling van <em>Hartverwarmers</em>, 2021 — bewoners ontdekken het boek.</figcaption>
            </figure>
        </div>
    </section>

    {{-- Book --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-3xl mx-auto px-6 pb-16">
            <div class="flex gap-6 items-start">
                <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="shrink-0">
                    <img src="/img/covers/hartverwarmers.jpg" alt="Hartverwarmers boekcover" class="w-24 shadow-md" style="transform: rotate(-2deg);">
                </a>
                <div>
                    <p class="font-semibold">Hartverwarmers — Deugddoende activiteiten voor woonzorgcentra</p>
                    <p class="text-sm text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">Politeia, 2021</p>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-1">Bundelt een selectie van de beste activiteiten en legt het fundament van het DIAMANT-model uit.</p>
                    <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="cta-link mt-1 inline-block text-sm">Bekijk bij Standaard Boekhandel</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Video --}}
    <section>
        <div class="max-w-5xl mx-auto px-6 py-16">
            <div class="aspect-video rounded-xl overflow-hidden shadow-lg">
                <iframe src="https://www.youtube-nocookie.com/embed/k8zetWJ-Pro" title="Hartverwarmers — het ontstaan" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
            </div>
            <p class="text-[var(--color-text-secondary)] mt-4 text-sm">
                Mei 2020 — amper twee maanden na de lancering. Hartverwarmers had toen al meer dan 25.000 bezoekers en ruim 100 ingediende activiteiten.
            </p>
        </div>
    </section>

    {{-- Support CTA --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-4xl mx-auto px-6 py-16">

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

            {{-- Secondary CTAs — compact row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-16 pt-10 border-t border-[var(--color-border-light)]">
                <div>
                    <p class="font-semibold text-[var(--color-text-primary)]">Deel jouw activiteit</p>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-1" style="font-weight: var(--font-weight-light);">Heb jij een activiteit die werkt? Voeg ze toe en help een collega.</p>
                    <a href="{{ route('fiches.create') }}" class="cta-link mt-2 inline-block text-sm">Nieuwe fiche toevoegen</a>
                </div>
                <div x-data="{ copied: false, async share() { const data = { title: 'Hartverwarmers', url: window.location.origin }; try { if (navigator.share) { await navigator.share(data); } else { await navigator.clipboard.writeText(data.url); this.copied = true; setTimeout(() => this.copied = false, 2000); } } catch (e) {} } }">
                    <p class="font-semibold text-[var(--color-text-primary)]">Verspreid het woord</p>
                    <p class="text-sm text-[var(--color-text-secondary)] mt-1" style="font-weight: var(--font-weight-light);">Ken jij iemand die dit platform zou gebruiken? Deel de link.</p>
                    <button @click="share()" class="cta-link mt-2 inline-block text-sm text-[var(--color-primary)] cursor-pointer">
                        <span x-show="!copied">Deel Hartverwarmers →</span>
                        <span x-show="copied" x-cloak>Link gekopieerd!</span>
                    </button>
                </div>
            </div>

        </div>
    </section>
</x-layout>
