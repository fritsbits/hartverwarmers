<x-layout title="Wat is er nieuw" description="Ontdek wat er veranderd is op Hartverwarmers: nieuwe structuur, betere navigatie en automatische PDF-conversie." :full-width="true">

    {{-- Hero / Block 1: Opening --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="max-w-3xl">
                <span class="section-label section-label-hero">Nieuw</span>
                <h1 class="mt-1">Een nieuwe Hartverwarmers.<br>Gebouwd door jullie.</h1>
                <div class="text-[var(--color-text-secondary)] mt-6 space-y-4 text-xl" style="font-weight: var(--font-weight-light);">
                    <p>Bijna 500 activiteiten. Dat hebben jullie samen opgebouwd, activiteitenbegeleiders uit heel Vlaanderen en Nederland. Dat verdient erkenning.</p>
                    <p>Het verhaal van Hartverwarmers begon in maart 2020, in de eerste week van de lockdown. Frederik Vincx bouwde het platform in één week, samen met Maite Mallentjer. Wat begon als een crisisinitiatief, groeide uit tot een community van meer dan 4.800 collega's. Na 2022 liep het platform even op de achtergrond. De community bleef groeien, maar de beheerders hadden minder tijd en middelen. Nu slaan Frederik en Maite opnieuw de handen in elkaar. Meer over hun verhaal lees je op de <a href="{{ route('about') }}" class="underline hover:text-[var(--color-primary)]">over ons-pagina</a>.</p>
                    <p>Dit is de eerste versie van de vernieuwde website. En er komt nog meer aan.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Block 2: Eerst het praktische --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="max-w-3xl">
                <span class="inline-flex w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] items-center justify-center mb-4">
                    <flux:icon.wrench-screwdriver class="size-5 text-[var(--color-primary)]" />
                </span>
                <h2>Eerst het praktische</h2>
                <div class="mt-6">
                    <flux:callout icon="information-circle">
                        <flux:callout.text>
                            Je moet opnieuw <a href="{{ route('login') }}" class="underline hover:text-[var(--color-primary)]">inloggen</a>. Dat is normaal, de website is volledig herbouwd. Je bestaand wachtwoord werkt gewoon nog. Alles wat er stond, is er nog.
                        </flux:callout.text>
                    </flux:callout>
                </div>
            </div>
        </div>
    </section>

    {{-- Block 3: Wat er anders is --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="max-w-3xl">
                <span class="inline-flex w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] items-center justify-center mb-4">
                    <flux:icon.arrows-right-left class="size-5 text-[var(--color-primary)]" />
                </span>
                <h2>Wat er anders is</h2>
                <div class="text-[var(--color-text-secondary)] mt-6 space-y-8" style="font-weight: var(--font-weight-light);">
                    <div>
                        <h3 class="text-[var(--color-text-primary)]">Initiatieven en fiches</h3>
                        <p class="mt-2">De structuur is iets veranderd, en dat vraagt even gewenning. Activiteiten zijn nu gegroepeerd onder <a href="{{ route('initiatives.index') }}" class="underline hover:text-[var(--color-primary)]">initiatieven</a>, een breed concept zoals &ldquo;Quiz&rdquo; of &ldquo;Muziek&rdquo;. Een concrete uitwerking van een collega noemen we een fiche. Zo zie je in één oogopslag wat het initiatief is én hoe anderen het al hebben toegepast.</p>
                    </div>
                    <div>
                        <h3 class="text-[var(--color-text-primary)]">Geen foto-upload meer</h3>
                        <p class="mt-2">Eén ding is ook verdwenen: je kan geen eigen foto meer toevoegen aan een fiche. Tot twee keer toe moesten we boetes betalen omdat er afbeeldingen waren opgeladen waarvoor de rechten niet klopten. Dat willen we niet opnieuw riskeren, en daarom hebben we die optie verwijderd.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Block 4: Wat er beter is --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="max-w-3xl">
                <span class="inline-flex w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] items-center justify-center mb-4">
                    <flux:icon.arrow-trending-up class="size-5 text-[var(--color-primary)]" />
                </span>
                <h2>Wat er beter is</h2>
                <div class="text-[var(--color-text-secondary)] mt-6 space-y-8" style="font-weight: var(--font-weight-light);">
                    <div>
                        <h3 class="text-[var(--color-text-primary)]">Beter bladeren en previews</h3>
                        <p class="mt-2">Bladeren gaat een stuk vlotter. Je ziet meteen previews van wat er in een fiche zit, zonder eerst te moeten downloaden. En als je even wegklikt, vind je gemakkelijk de weg terug.</p>
                    </div>
                    <div>
                        <h3 class="text-[var(--color-text-primary)]">Automatische PDF-conversie</h3>
                        <p class="mt-2">Bestanden openen lukt nu ook voor iedereen. Vroeger zagen we regelmatig in de reacties dat mensen een PowerPoint niet konden openen. Nu zetten we elk bestand automatisch om naar PDF. Simpel, maar het scheelt een hoop gedoe.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Block 5: Wat er aankomt --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="max-w-3xl">
                <span class="inline-flex w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] items-center justify-center mb-4">
                    <flux:icon.sparkles class="size-5 text-[var(--color-primary)]" />
                </span>
                <h2>Wat er aankomt</h2>
                <p class="text-[var(--color-text-secondary)] mt-6" style="font-weight: var(--font-weight-light);">
                    Binnenkort introduceren we het DIAMANT-model, een kwaliteitskader rond zinvolle activiteiten in woonzorgcentra, ontwikkeld vanuit de expertise van Maite Mallentjer. Meer daarover volgt.
                </p>
                <div class="mt-4">
                    <flux:badge color="lime">Binnenkort</flux:badge>
                </div>
            </div>
        </div>
    </section>

    {{-- Block 6: Jouw feedback telt — warm closing with polaroid + CTA --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <div class="lg:col-span-2">
                    <span class="inline-flex w-10 h-10 rounded-full bg-[var(--color-bg-accent-light)] items-center justify-center mb-4">
                        <flux:icon.heart class="size-5 text-[var(--color-primary)]" />
                    </span>
                    <h2>Jouw feedback telt</h2>
                    <div class="text-[var(--color-text-secondary)] mt-6 space-y-4" style="font-weight: var(--font-weight-light);">
                        <p>We hebben al gebruikerstests gedaan met echte activiteitenbegeleiders, en wat we leerden hebben we meteen verwerkt. Maar we zijn er nog niet. Heb jij een suggestie, een vraag, of iets dat niet klopt?</p>
                        <a href="mailto:info@hartverwarmers.be" class="cta-link inline-block">Stuur een mailtje</a>
                        <p>We lezen alles.</p>
                    </div>

                    <div class="mt-8 pt-8 border-t border-[var(--color-border-light)]">
                        <p class="text-[var(--color-text-primary)] text-lg font-heading font-bold">We naderen de 500 activiteiten. Welke activiteit deel jij?</p>
                        <a href="{{ route('fiches.create') }}" class="btn-pill mt-4 inline-block">Deel een activiteit</a>
                    </div>
                </div>

                {{-- Polaroid accent --}}
                <div class="hidden lg:flex flex-col items-center justify-center gap-4">
                    <figure class="photo-polaroid" style="transform: rotate(2deg)">
                        <img src="/img/about/frederik-vincx.webp" alt="Frederik Vincx" class="w-36 aspect-[4/3] object-cover object-top" loading="lazy" width="144" height="108">
                        <figcaption><strong class="text-[var(--color-text-primary)]">Frederik</strong></figcaption>
                    </figure>
                    <figure class="photo-polaroid -mt-2" style="transform: rotate(-2.5deg)">
                        <img src="/img/wonen-en-leven/maitemallentjer.jpg" alt="Maite Mallentjer" class="w-36 aspect-square object-cover" loading="lazy" width="144" height="144">
                        <figcaption><strong class="text-[var(--color-text-primary)]">Maite</strong></figcaption>
                    </figure>
                </div>
            </div>
        </div>
    </section>

</x-layout>
