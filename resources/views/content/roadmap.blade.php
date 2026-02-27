<x-layout title="Roadmap wonen en leven" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-6xl mx-auto px-6 pt-8 pb-16">
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item href="{{ route('home') }}">Home</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tools & inspiratie</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="{{ route('tools.index') }}">Gidsen en tools</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Roadmap</flux:breadcrumbs.item>
            </flux:breadcrumbs>

            <span class="section-label section-label-hero">Gids voor woonzorgcentra</span>
            <h1 class="text-5xl mt-1">Roadmap wonen en leven</h1>

            <div class="flex flex-col lg:flex-row items-start gap-8 mt-8">
                <div class="lg:w-7/12">
                    <p class="text-2xl text-[var(--color-text-secondary)]">Dit is een uitgebreid stappenplan om je hele woonzorgteam mee te nemen in wonen en leven. Het biedt een concreet antwoord op de vernieuwde focus op welbevinden in het woonzorgdecreet.</p>

                    <h3 class="font-semibold text-[var(--color-text-primary)] mt-8 mb-4">Wat je kan verwachten:</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-[var(--color-text-secondary)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            De verschillende etappes voor een gedragen woonleefbeleid
                        </li>
                        <li class="flex items-start gap-3 text-[var(--color-text-secondary)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Welke teamleden je wanneer betrekt
                        </li>
                        <li class="flex items-start gap-3 text-[var(--color-text-secondary)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Workshopformaten om samen jullie aanpak te bepalen
                        </li>
                        <li class="flex items-start gap-3 text-[var(--color-text-secondary)]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[var(--color-primary)] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            De boeken en werkvormen waarmee jullie aan de slag kunnen
                        </li>
                    </ul>
                </div>
                <div class="lg:w-5/12 text-center">
                    <img src="/img/content/boek-roadmap.jpg" alt="Roadmap wonen en leven" class="max-w-xs mx-auto rounded-lg shadow mb-4">
                    <h2 class="text-xl font-bold text-[var(--color-text-primary)]">Download e-book</h2>
                    <p class="text-[var(--color-text-secondary)] mt-1">Druk de roadmap af om later te lezen</p>
                    <flux:button variant="primary" href="https://prismic-io.s3.amazonaws.com/soulcenterbe/343d68ab-80ea-4a18-8bdb-5cf0d4b94314_roadmap-wonen-en-leven-soulcenter.pdf" target="_blank" class="mt-3" size="sm">
                        Download e-book
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Bergbeklimming --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Metafoor</span>
            <h2 class="mb-6">Bergbeklimming als beeld voor jullie verandertraject</h2>

            <div class="max-w-[48rem]">
                <p class="text-[var(--color-text-secondary)] mb-3">Je kan de weg naar een gedragen woonleefbeleid vergelijken met een bergbeklimming. Je hebt met je team een groots doel voor ogen. De weg naar boven is echter lang en nog onzeker.</p>
                <p class="text-[var(--color-text-secondary)] mb-6">Zo kan een roadmap er uit zien. We bekijken verderop alle etappes in detail.</p>

                <img src="/img/illustration/ws-overview-noframe.png" alt="overzicht roadmap" class="w-full rounded-lg shadow mb-4">

                <p class="text-[var(--color-text-secondary)] mb-3">Je krijgt een overzicht van een mogelijk verandertraject voor een team. Het is zowel toepasselijk voor constellaties van woonzorgcentra als voor individuele centra.</p>
                <p class="text-[var(--color-text-secondary)] mb-6">Deze aanbeveling is gebaseerd op voorafgaande trajecten met woonzorgcentra, op interviews met wooncentra die erg ver staan in hun woonleefbeleid, en op een grondige analyse van de huidige coachingstrajecten en theoretische kaders rond wonen en leven in het woonzorgcentrum.</p>

                <div class="aspect-video rounded-lg overflow-hidden shadow-lg mb-4">
                    <iframe src="https://www.youtube.com/embed/uNa0djrzX1s?rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>
                <p class="text-sm text-center text-[var(--color-text-secondary)]">Video-overzicht van de roadmap</p>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Inhoudsopgave --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <span class="section-label">Inhoudsopgave</span>
            <h2 class="mb-6">Een gedragen woonleefbeleid in vier etappes</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="#visie" class="flex items-center gap-4 p-4 rounded-lg hover:bg-[var(--color-bg-subtle)] transition-colors">
                    <img src="/img/illustration/icon_visie.svg" alt="Visie vormen" class="h-16 w-16 flex-shrink-0">
                    <div>
                        <p class="text-sm font-semibold text-amber-600 uppercase">Etappe 1</p>
                        <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Visie vormen</h3>
                    </div>
                </a>
                <a href="#verzamelen" class="flex items-center gap-4 p-4 rounded-lg hover:bg-[var(--color-bg-subtle)] transition-colors">
                    <img src="/img/illustration/icon_verzamelen.svg" alt="Verzamelen" class="h-16 w-16 flex-shrink-0">
                    <div>
                        <p class="text-sm font-semibold text-blue-600 uppercase">Etappe 2</p>
                        <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Verzamelen</h3>
                    </div>
                </a>
                <a href="#verspreiden" class="flex items-center gap-4 p-4 rounded-lg hover:bg-[var(--color-bg-subtle)] transition-colors">
                    <img src="/img/illustration/icon_verspreiden.svg" alt="Verspreiden" class="h-16 w-16 flex-shrink-0">
                    <div>
                        <p class="text-sm font-semibold text-red-600 uppercase">Etappe 3</p>
                        <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Verspreiden</h3>
                    </div>
                </a>
                <a href="#verankeren" class="flex items-center gap-4 p-4 rounded-lg hover:bg-[var(--color-bg-subtle)] transition-colors">
                    <img src="/img/illustration/icon_verankeren.svg" alt="Verankeren" class="h-16 w-16 flex-shrink-0">
                    <div>
                        <p class="text-sm font-semibold text-orange-600 uppercase">Etappe 4</p>
                        <h3 class="text-xl font-bold text-[var(--color-text-primary)]">Verankeren</h3>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Etappe 1: Visie --}}
    <section id="visie" class="scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="bg-[var(--color-bg-subtle)] rounded-xl p-8 mb-8">
                <div class="flex items-center gap-4">
                    <img src="/img/illustration/icon_visie.svg" alt="Visie" class="h-16 w-16">
                    <div>
                        <p class="text-sm font-semibold text-amber-600 uppercase">Etappe 1</p>
                        <h2 class="text-3xl">Vorm visie</h2>
                    </div>
                </div>
            </div>

            <div class="max-w-[48rem]">
                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-6 mb-3">Met goede zorg alleen kunnen woonzorgcentra zich niet meer onderscheiden</h3>
                <p class="text-[var(--color-text-secondary)] mb-3">Goede zorg is het olympische minimum. Klanten en werknemers verwachten meer. Met de slinkende wachtlijsten en de toenemende <em>war on talent</em> is het nodig om beleving voorop te stellen. Door je huis een leuke plek te maken om te wonen, wordt het ook een leuke plek om te werken. En <strong>die goesting straalt uit naar de buitenwereld</strong>.</p>
                <p class="text-[var(--color-text-secondary)] mb-6">Op 7 februari 2019 lanceerde toenmalig minister Jo Vandeurzen een <strong>nieuw woonzorgdecreet</strong> waarin dat 'Wonen en leven' veel meer aandacht krijgt.</p>

                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-8 mb-3">Een gemotiveerd team</h3>
                <p class="text-[var(--color-text-secondary)] mb-3">Een woonleefbeleid is deels strategisch en deels heel praktisch. Je team heeft een beleidsvisie nodig, maar daarnaast ook heel <strong>concrete tactieken om wonen en leven in de praktijk te brengen</strong>.</p>
                <p class="text-[var(--color-text-secondary)] mb-6">Daarom is het essentieel dat zowel directieleden als medewerkers betrokken zijn bij het uitstippelen en uitvoeren van het verandertraject.</p>

                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-8 mb-3">Visualiseer jullie visie</h3>
                <p class="text-[var(--color-text-secondary)] mb-3">Waar willen jullie naartoe? Hoe zien jullie het eindresultaat? Vorm samen een concreter beeld van jullie doel door de visie te visualiseren.</p>
                <p class="text-[var(--color-text-secondary)] mb-6">Een mogelijke oefening om jullie visie samen te verkennen is via visiecanvassen. Het is een cocreatieve aanpak waarbij je ofwel met een kernteam van enthousiastelingen of met al je medewerkers samen aan de slag kan gaan.</p>

                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-8 mb-3">Evalueer jullie huidige aanpak</h3>
                <p class="text-[var(--color-text-secondary)] mb-3">Kijk stap voor stap naar wat er al goed gaat in jullie team en bouw daar op verder.</p>

                <div class="aspect-video rounded-lg overflow-hidden shadow-lg my-6">
                    <iframe src="https://www.youtube.com/embed/PTpznJVCkaM?rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>
                <p class="text-sm text-center text-[var(--color-text-secondary)]">Toelichting bij workshop rond het evalueren van je aanpak</p>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Etappe 2: Verzamelen --}}
    <section id="verzamelen" class="scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="bg-[var(--color-bg-subtle)] rounded-xl p-8 mb-8">
                <div class="flex items-center gap-4">
                    <img src="/img/illustration/icon_verzamelen.svg" alt="Verzamelen" class="h-16 w-16">
                    <div>
                        <p class="text-sm font-semibold text-blue-600 uppercase">Etappe 2</p>
                        <h2 class="text-3xl">Verzamel woonleefplannen</h2>
                    </div>
                </div>
            </div>

            <div class="max-w-[48rem]">
                <p class="text-[var(--color-text-secondary)] mb-6">Nu wordt het concreet. Verlaat het abstracte terrein van visieontwikkeling begin met waar het echt om draait: samen mooie momenten beleven met bewoners.</p>

                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-8 mb-3">Leefplannen als basis</h3>
                <p class="text-[var(--color-text-secondary)] mb-6">Net zoals elke bewoner een zorgplan heeft hoort elke bewoner een leefplan te hebben. Daarin staan onder andere de verhalen, persoonlijke voorkeuren en verlangens van de bewoner.</p>

                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-8 mb-3">Stappenplan: samen opstellen leefplan</h3>
                <p class="text-[var(--color-text-secondary)] mb-4">Hoe slaag je er als team in om de leefplannen van bewoners te verzamelen?</p>

                <ol class="list-decimal list-inside space-y-2 text-[var(--color-text-secondary)] mb-6">
                    <li><strong>Kennismaken</strong> &mdash; Wie wil cliënt bij gesprek hebben? Ondersteuner vertelt wie je bent en wat je komt doen.</li>
                    <li><strong>Persoonlijke wensen in kaart brengen</strong> &mdash; Verkennen met vragen per leefgebied. Antwoorden noteren in leefplan.</li>
                    <li><strong>Samen doelen en acties bepalen</strong> &mdash; Wat zijn de wensen en doelen van de bewoner? Wat heeft prioriteit?</li>
                    <li><strong>Afspreken en in praktijk brengen</strong> &mdash; Overleg met elkaar: hoe doen we dit?</li>
                    <li><strong>Evaluatie</strong> &mdash; Hoe gaat het? Bijstellen van het leefplan en nieuwe acties afspreken.</li>
                </ol>

                <div class="aspect-video rounded-lg overflow-hidden shadow-lg my-6">
                    <iframe src="https://www.youtube.com/embed/DSbaoWp5D9Y?rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Etappe 3: Verspreiden --}}
    <section id="verspreiden" class="scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="bg-[var(--color-bg-subtle)] rounded-xl p-8 mb-8">
                <div class="flex items-center gap-4">
                    <img src="/img/illustration/icon_verspreiden.svg" alt="Verspreiden" class="h-16 w-16">
                    <div>
                        <p class="text-sm font-semibold text-red-600 uppercase">Etappe 3</p>
                        <h2 class="text-3xl">Verspreid leefplannen</h2>
                    </div>
                </div>
            </div>

            <div class="max-w-[48rem]">
                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-6 mb-3">Wonen en leven is de verantwoordelijkheid van iedereen</h3>
                <p class="text-[var(--color-text-secondary)] mb-6">Heel het team kan bijdragen om bewoners gelukkiger te maken. Zorgmedewerkers, de technische dienst, het keukenpersoneel, directie,.. <strong>iedereen kan een goeie babbel doen met bewoners</strong>.</p>

                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-8 mb-3">Stel de bewoner voor op multidisciplinair teamoverleg</h3>
                <p class="text-[var(--color-text-secondary)] mb-6">Doorgaans zijn het begeleiders wonen en leven en stagiairs die de tijd krijgen om leefplannen voor bewoners vast te leggen. Dit is een kans om alle waardevolle informatie over bewoners <strong>tot bij de rest van het team te brengen</strong>.</p>

                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-8 mb-3">Ken aandachtspersonen toe</h3>
                <p class="text-[var(--color-text-secondary)] mb-6">Aandachtspersonen zijn medewerkers en bewoners die een sterkere band hebben. De bewoner krijgt een vast persoon toegewezen om het leefplan op te stellen en uit te voeren.</p>

                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-8 mb-3">Visualiseer leefplannen</h3>
                <p class="text-[var(--color-text-secondary)] mb-6">Zorg dat je team niet om de leefplannen van bewoners heen kan. Verschillende huizen doen dit door de leefplannen te visualiseren in compacte moodboards of grotere posters.</p>

                <div class="aspect-video rounded-lg overflow-hidden shadow-lg my-6">
                    <iframe src="https://www.youtube.com/embed/PQOSEHdmzNY?rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
                </div>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Etappe 4: Verankeren --}}
    <section id="verankeren" class="scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="bg-[var(--color-bg-subtle)] rounded-xl p-8 mb-8">
                <div class="flex items-center gap-4">
                    <img src="/img/illustration/icon_verankeren.svg" alt="Verankeren" class="h-16 w-16">
                    <div>
                        <p class="text-sm font-semibold text-orange-600 uppercase">Etappe 4</p>
                        <h2 class="text-3xl">Veranker woonleefplannen</h2>
                    </div>
                </div>
            </div>

            <div class="max-w-[48rem]">
                <h3 class="text-xl font-semibold text-[var(--color-text-primary)] mt-6 mb-3">Proficiat</h3>
                <p class="text-[var(--color-text-secondary)] mb-6">Op dit moment staat het team figuurlijk op de top van de berg die jullie samen beklommen. Neem de tijd om samen terug te kijken naar de weg die jullie aflegden, en naar wat jullie onderweg leerden.</p>

                <img src="/img/illustration/ws-overview-noframe.png" alt="overzicht roadmap" class="w-full rounded-lg shadow mb-6">

                <p class="text-[var(--color-text-secondary)] mb-3">Evalueer het projectverloop in groep. Wat ging er goed? Wat waren de hindernissen? Hoe kan het team het beter doen en de rest van het team stimuleren?</p>
                <p class="text-[var(--color-text-secondary)] mb-6">Deze roadmap is vanuit het perspectief van het team. Maar hoe beleeft de bewoner dit traject? Maak een customer journey van de bewoner om een beter beeld te hebben op zijn of haar beleving.</p>
            </div>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Outro --}}
    <section>
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="bg-[var(--color-bg-subtle)] rounded-xl p-8">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="md:w-7/12">
                        <span class="section-label">Aanpak op maat</span>
                        <h2 class="mt-1">Ieder woonzorgcentrum is uniek. Iedere roadmap dus ook.</h2>
                        <p class="text-[var(--color-text-secondary)] mt-3">Met Hartverwarmers begeleiden we teams om hun visie rond wonen en leven in de praktijk te brengen.</p>

                        <div class="flex items-center gap-4 mt-6">
                            <img src="/img/avatar-frederik.jpg" alt="Frederik Vincx" class="w-14 h-14 rounded-full">
                            <div>
                                <p class="font-semibold text-[var(--color-text-primary)]">Frederik Vincx</p>
                                <p class="text-sm text-[var(--color-text-secondary)]">frederik@soulcenter.be</p>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-5/12">
                        <img src="/img/illustration/mockup-roadmap-white.jpg" alt="Roadmap wonen en leven" class="w-full rounded-lg">
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <flux:button variant="ghost" href="{{ route('tools.index') }}">
                    &larr; Gidsen en tools
                </flux:button>
            </div>
        </div>
    </section>
</x-layout>
