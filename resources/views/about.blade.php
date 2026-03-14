<x-layout title="Over ons" description="Hartverwarmers is een gratis platform van en voor activiteitenbegeleiders in de ouderenzorg. Ontdek het verhaal, de community en het DIAMANT-model." :full-width="true">

    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Over Hartverwarmers</span>
                <h1 class="mt-1">Meer tijd voor wat écht telt</h1>
                <p class="text-2xl text-[var(--color-text-secondary)] mt-4" style="font-weight: var(--font-weight-light);">
                    Hartverwarmers bestaat zodat jij je tijd kunt steken in wat écht telt — de mensen in jouw zorg. Niet in het uitdenken en uitwerken van activiteiten. Dat doen we samen: een community van activiteitenbegeleiders die hun beste ideeën deelt, zodat jij het warm water niet telkens opnieuw hoeft uit te vinden.
                </p>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Community + Story --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                {{-- Left column: activity Polaroid --}}
                <div>
                    <figure class="photo-polaroid" style="transform: rotate(-2deg)">
                        <img src="/img/about/lancering-activiteit.jpg" alt="Virtueel museumbezoek bij WZC Nottebohm" class="w-full aspect-[4/3] object-cover">
                        <figcaption>Virtueel museumbezoek — een van de activiteiten die bewoners verbindt met de wereld buiten het woonzorgcentrum.</figcaption>
                    </figure>
                </div>

                {{-- Right column: community + story text --}}
                <div class="lg:col-span-2">
                    <span class="section-label">De hartverwarmers</span>
                    <h2 class="mt-1 mb-4">Samen maken we het verschil</h2>
                    <p class="text-[var(--color-text-secondary)] max-w-2xl" style="font-weight: var(--font-weight-light);">
                        Hartverwarmers is niet het werk van één organisatie. Vandaag delen <strong class="text-[var(--color-text-primary)]">{{ number_format($aboutStats['contributors_count']) }} hartverwarmers</strong> uit heel Vlaanderen en Nederland hun <strong class="text-[var(--color-text-primary)]">{{ number_format($aboutStats['fiches_count']) }} praktijkfiches</strong> — gratis, voor meer dan <strong class="text-[var(--color-text-primary)]">{{ number_format($aboutStats['users_count']) }}+ collega's</strong>. Activiteitenbegeleiders, animatoren en ergotherapeuten die elke dag harten verwarmen en hun beste ideeën delen met de rest van de sector.
                    </p>
                    <a href="{{ route('contributors.index') }}" class="cta-link mt-4 inline-block">Ontdek wie er bijdraagt</a>
                </div>

            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Story + People + Book + Video --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                {{-- Left column: story + book + video --}}
                <div class="lg:col-span-2">
                    <span class="section-label">Het verhaal</span>
                    <h2 class="mt-1 mb-6">Geboren in één week, tijdens de eerste lockdown</h2>

                    <div class="text-[var(--color-text-secondary)] space-y-4 max-w-2xl" style="font-weight: var(--font-weight-light);">
                        <p>Maart 2020. Woonzorgcentra waren plots volledig afgesloten. Op sociale media deelden medewerkers creatieve manieren om bewoners — ondanks alles — een mooie dag te geven. Raamoptredens door muzikanten. Hobbykarren die langs de kamers trokken. Bingo vanuit de deuropening. Die energie mocht niet verloren gaan.</p>
                        <p>In één week bouwden we Hartverwarmers: een plek om die initiatieven te bundelen, zodat elk woonzorgcentrum kon leren van wat elders werkte. Wat begon als een crisisinitiatief, is vijf jaar later nog steeds springlevend. Elke maand vinden zo'n 50 nieuwe activiteitenbegeleiders de weg naar het platform.</p>
                    </div>

                    {{-- Book --}}
                    <div class="flex gap-5 items-start mt-10 pt-8 border-t border-[var(--color-border-light)] max-w-2xl">
                        <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="shrink-0">
                            <img src="/img/covers/hartverwarmers.jpg" alt="Hartverwarmers boekcover" class="w-24 shadow-md" style="transform: rotate(-2deg);">
                        </a>
                        <div>
                            <p class="text-lg font-semibold">Hartverwarmers — Deugddoende activiteiten voor woonzorgcentra</p>
                            <p class="text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">Politeia, 2021</p>
                            <p class="text-[var(--color-text-secondary)] mt-1">Bundelt een selectie van de beste activiteiten uit de community in boekvorm.</p>
                            <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="cta-link mt-1 inline-block">Bekijk bij Standaard Boekhandel</a>
                        </div>
                    </div>

                    {{-- Video --}}
                    <div class="mt-10 pt-8 border-t border-[var(--color-border-light)] max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-widest text-[var(--color-text-secondary)] mb-3">Mei 2020 — twee maanden na de lancering</p>
                        <div class="aspect-video rounded-xl overflow-hidden shadow-md">
                            <iframe src="https://www.youtube-nocookie.com/embed/k8zetWJ-Pro" title="Hartverwarmers — het ontstaan" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                        </div>
                        <p class="text-sm text-[var(--color-text-secondary)] mt-2">
                            Hartverwarmers had toen al meer dan 25.000 bezoekers en ruim 100 ingediende activiteiten.
                        </p>
                    </div>
                </div>

                {{-- Right column: people --}}
                <div>
                    <span class="text-sm font-semibold uppercase tracking-widest text-[var(--color-text-secondary)]">De mensen erachter</span>

                    <figure class="photo-polaroid mt-4 inline-block" style="transform: rotate(2deg)">
                        <img src="/img/about/frederik-vincx.webp" alt="Frederik Vincx" class="w-full aspect-[4/3] object-cover object-top">
                        <figcaption><strong class="text-[var(--color-text-primary)]">Frederik Vincx</strong><br><small>Oprichter</small></figcaption>
                    </figure>
                    <p class="text-[var(--color-text-secondary)] mt-3" style="font-weight: var(--font-weight-light);">Digitale bouwer en planner. Helpt lokale impactorganisaties bij het ontwikkelen van digitale producten en diensten. Bouwde Hartverwarmers in 2020 vanuit <a href="https://www.frederikvincx.com/" target="_blank" rel="noopener noreferrer" class="underline hover:text-[var(--color-primary)]">zijn eigen praktijk</a> en draagt het platform sindsdien vrijwillig.</p>

                    <figure class="photo-polaroid mt-8 inline-block" style="transform: rotate(-1.5deg)">
                        <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-full aspect-square object-cover">
                        <figcaption><strong class="text-[var(--color-text-primary)]">Maite Mallentjer</strong><br><small>Pedagoog dagbesteding</small></figcaption>
                    </figure>
                    <p class="text-[var(--color-text-secondary)] mt-3" style="font-weight: var(--font-weight-light);">Hielp Hartverwarmers mee vormgeven bij de lancering in 2020. Brengt in 2026 haar expertise in via het <a href="{{ route('goals.index') }}" class="underline hover:text-[var(--color-primary)]">DIAMANT-model</a> — een kwaliteitskader dat ons helpt toe te werken naar betere activiteiten.</p>
                </div>

            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- CTA section --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

                {{-- Left: primary CTA --}}
                <div class="lg:col-span-2" x-data="{ open: false }">
                    <span class="section-label">Steun Hartverwarmers</span>
                    <h2 class="mt-1 mb-4">Doe een gift</h2>
                    <p class="text-[var(--color-text-secondary)] max-w-2xl" style="font-weight: var(--font-weight-light);">
                        Hartverwarmers is een vrijwillig project — de domeinnaam, server, e-maildienst en technische infrastructuur worden persoonlijk bekostigd. Wil je dit platform steunen of een samenwerking verkennen? Laat het weten. Elke bijdrage, hoe klein ook, helpt om de vaste kosten te dekken.
                    </p>
                    <div class="mt-6" x-show="!open">
                        <button @click="open = true" class="btn-pill">Neem contact op</button>
                    </div>
                    <div x-show="open" x-collapse x-cloak class="mt-6 max-w-lg">
                        <livewire:support-contact-form />
                    </div>
                </div>

                {{-- Right: secondary CTAs stacked --}}
                <div class="space-y-10">
                    <div>
                        <div class="w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center mb-3">
                            <flux:icon.pencil-square class="size-5 text-[var(--color-primary)]" />
                        </div>
                        <h3 class="text-xl">Deel jouw activiteit</h3>
                        <p class="text-[var(--color-text-secondary)] mt-2" style="font-weight: var(--font-weight-light);">Heb jij een activiteit die werkt? Voeg ze toe en help een collega.</p>
                        <a href="{{ route('fiches.create') }}" class="cta-link mt-2 inline-block">Nieuwe fiche toevoegen</a>
                    </div>
                    <div x-data="{ copied: false, async share() { const data = { title: 'Hartverwarmers', url: window.location.origin }; try { if (navigator.share) { await navigator.share(data); } else { await navigator.clipboard.writeText(data.url); this.copied = true; setTimeout(() => this.copied = false, 2000); } } catch (e) {} } }">
                        <div class="w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] flex items-center justify-center mb-3">
                            <flux:icon.share class="size-5 text-[var(--color-primary)]" />
                        </div>
                        <h3 class="text-xl">Verspreid het woord</h3>
                        <p class="text-[var(--color-text-secondary)] mt-2" style="font-weight: var(--font-weight-light);">Ken jij iemand die dit platform zou gebruiken? Deel de link.</p>
                        <button @click="share()" class="mt-2 inline-flex items-center gap-1 font-semibold text-[var(--color-primary)] hover:text-[var(--color-primary-hover)] transition-colors cursor-pointer">
                            <span x-show="!copied">Deel Hartverwarmers</span>
                            <span x-show="copied" x-cloak>Link gekopieerd!</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </section>
</x-layout>
