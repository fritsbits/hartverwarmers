<x-layout title="Over ons" description="Hartverwarmers is een gratis platform van en voor activiteitenbegeleiders in de ouderenzorg. Ontdek het verhaal, de community en het DIAMANT-model." :full-width="true">

    {{-- Block 1 — Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label section-label-hero">Over Hartverwarmers</span>
            <h1 class="mt-1">Jij hoort niet achter een computer te zitten.<br>Jij hoort bij je bewoners te zijn.</h1>
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

    {{-- Block 3 — Foundation --}}
    <section class="bg-[var(--color-bg-subtle)]">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label">Het fundament</span>
            <h2 class="mt-1 mb-4">Gebouwd op serieus vakmanschap</h2>
            <p class="text-[var(--color-text-secondary)] max-w-3xl" style="font-weight: var(--font-weight-light);">
                Alle activiteiten op Hartverwarmers zijn getoetst aan het <strong>DIAMANT-model</strong> — zeven doelstellingen die samen beschrijven wat een deugddoende activiteit kenmerkt. Niet als afvinklijst, maar als kompas. Het model is ontwikkeld door twee onderzoekers die de ouderenzorg door en door kennen.
            </p>

            <div class="flex gap-5 mt-8 justify-center">
                <figure class="photo-polaroid" style="transform: rotate(-3deg)">
                    <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-36 aspect-square object-cover">
                    <figcaption><strong class="text-[var(--color-text-primary)]">Maite Mallentjer</strong><br><small>Pedagoog dagbesteding, AP Hogeschool Antwerpen</small></figcaption>
                </figure>
                <figure class="photo-polaroid -mt-2" style="transform: rotate(2.5deg)">
                    <img src="/img/wonen-en-leven/nadinepraet.jpg" alt="Nadine Praet" class="w-36 aspect-square object-cover">
                    <figcaption><strong class="text-[var(--color-text-primary)]">Nadine Praet</strong><br><small>Onderzoeker ouderenzorg, Arteveldehogeschool Gent</small></figcaption>
                </figure>
            </div>

            <div class="text-center mt-6">
                <a href="{{ route('goals.index') }}" class="cta-link">Ontdek het DIAMANT-model</a>
            </div>

            {{-- Book --}}
            <div class="flex gap-6 items-start mt-12 pt-8 border-t border-[var(--color-border-light)]">
                <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="shrink-0">
                    <img src="/img/covers/hartverwarmers.jpg" alt="Hartverwarmers boekcover" class="w-28 shadow-md" style="transform: rotate(-2deg);">
                </a>
                <div>
                    <p class="text-lg font-semibold">Hartverwarmers — Deugddoende activiteiten voor woonzorgcentra</p>
                    <p class="text-[var(--color-text-secondary)]" style="font-weight: var(--font-weight-light);">Politeia, 2020</p>
                    <p class="text-[var(--color-text-secondary)] mt-2">Het boek bundelt een selectie van de beste activiteiten en legt het fundament van het DIAMANT-model uit.</p>
                    <a href="https://www.standaardboekhandel.be/p/hartverwarmers-9782509037831" target="_blank" rel="noopener noreferrer" class="cta-link mt-2 inline-block">Bekijk bij Standaard Boekhandel</a>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 4 — Story --}}
    <section>
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label">Het verhaal</span>
            <h2 class="mt-1 mb-4">Geboren in één week, tijdens de eerste lockdown</h2>
            <div class="text-[var(--color-text-secondary)] space-y-4 max-w-3xl" style="font-weight: var(--font-weight-light);">
                <p>Maart 2020. Woonzorgcentra waren plots volledig afgesloten. Op sociale media deelden medewerkers creatieve manieren om bewoners — ondanks alles — een mooie dag te geven. Raamoptredens door muzikanten. Hobbykarren die langs de kamers trokken. Bingo vanuit de deuropening. Die energie mocht niet verloren gaan.</p>
                <p>In één week bouwden we Hartverwarmers: een plek om die initiatieven te bundelen, zodat elk woonzorgcentrum kon leren van wat elders werkte. Wat begon als een crisisinitiatief, is vijf jaar later nog steeds springlevend. Elke maand vinden zo'n 50 nieuwe activiteitenbegeleiders de weg naar het platform. Elke maand worden nieuwe activiteiten toegevoegd — soms zonder dat ik er iets voor doe.</p>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 5 — Personal commitment --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label">Frederik Vincx — oprichter</span>
            <h2 class="mt-1 mb-4">Ik had de stekker kunnen uittrekken.<br>Dat heb ik niet gedaan.</h2>
            <div class="text-[var(--color-text-secondary)] space-y-4 max-w-3xl" style="font-weight: var(--font-weight-light);">
                <p>Hartverwarmers groeide uit een groter project — een softwarebedrijf voor woonzorgcentra dat we in 2022 stopzetten. Hartverwarmers had hetzelfde lot kunnen ondergaan. Maar de community bleef groeien, maand na maand, ook zonder actief beheer. Mensen vertrouwden op dit platform. Dat voelde als een verantwoordelijkheid.</p>
                <p>Ik heb gekozen om het te blijven dragen — alleen, persoonlijk, uit eigen zak. Ik betaal maandelijks voor de domeinnaam die dit adres levend houdt, de webserver die de site 24/7 online houdt, de e-maildienst waarmee duizenden begeleiders updates ontvangen, en de technische infrastructuur die alles draaiende houdt. De uren die ik erin steek? Die heb ik altijd gratis gegeven.</p>
            </div>

            {{-- Lancering photos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
                <img src="/img/about/lancering-activiteit.jpg" alt="Uitvoering van een activiteit — virtueel museumbezoek bij WZC Nottebohm" class="rounded-xl shadow-lg w-full">
                <img src="/img/about/lancering-boek.jpg" alt="Boekvoorstelling van het Hartverwarmers boek" class="rounded-xl shadow-lg w-full">
            </div>

            {{-- YouTube videos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <div class="aspect-video rounded-xl overflow-hidden shadow-lg">
                    <iframe src="https://www.youtube-nocookie.com/embed/k8zetWJ-Pro" title="Hartverwarmers — het ontstaan" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>
                <div class="aspect-video rounded-xl overflow-hidden shadow-lg">
                    <iframe src="https://www.youtube-nocookie.com/embed/TeNR4O0TJRc" title="Hartverwarmers — de groei" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Block 6 — Call to action --}}
    <section>
        <div class="max-w-4xl mx-auto px-6 py-16 space-y-16">

            {{-- Primary CTA — Steun --}}
            <div class="bg-[var(--color-bg-cream)] rounded-2xl p-8 md:p-12" x-data="{ open: false }">
                <span class="section-label">Steun Hartverwarmers</span>
                <h2 class="mt-1 mb-4">Help dit platform gratis houden</h2>
                <p class="text-[var(--color-text-secondary)] max-w-2xl" style="font-weight: var(--font-weight-light);">
                    Hartverwarmers is en blijft gratis. Maar gratis bestaat niet zonder iemand die de kosten draagt. Als dit platform ooit waarde heeft gehad voor jou — als je er een activiteit op vond die een bewoner een mooie dag bezorgde — overweeg dan een bijdrage. Elk bedrag helpt.
                </p>
                <div class="mt-6">
                    <flux:button variant="primary" @click="open = !open" x-text="open ? 'Sluiten' : 'Steun Hartverwarmers'" />
                </div>
                <div x-show="open" x-collapse x-cloak class="mt-6">
                    <livewire:support-contact-form />
                </div>
            </div>

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
