<x-layout title="Gebruiksvoorwaarden" :full-width="true">
    {{-- Hero --}}
    <section class="bg-[var(--color-bg-cream)]">
        <div class="max-w-4xl mx-auto px-6 py-16">
            <span class="section-label">Juridisch</span>
            <h1 class="mt-1">Gebruiksvoorwaarden</h1>
            <p class="text-lg text-[var(--color-text-secondary)] mt-4 font-light">De regels voor het gebruik van het Hartverwarmers-platform.</p>
            <p class="text-meta text-sm mt-4">Laatst bijgewerkt: 14 maart 2026</p>
        </div>
    </section>

    <hr class="border-[var(--color-border-light)]">

    {{-- Content --}}
    <section>
        <div class="max-w-4xl mx-auto px-6 py-16 space-y-12">

            {{-- 1. Definities --}}
            <div>
                <h2 class="text-xl mb-3">1. Definities</h2>
                <ul class="space-y-2 text-[var(--color-text-secondary)]">
                    <li class="flex gap-2"><strong class="text-[var(--color-text-primary)] shrink-0">Platform</strong> <span>— de website Hartverwarmers, bereikbaar via hartverwarmers.be</span></li>
                    <li class="flex gap-2"><strong class="text-[var(--color-text-primary)] shrink-0">Beheerder</strong> <span>— Frederik Vincx Impact Studio BV, Kasteeldreef 47, 1083 Ganshoren, KBO 0652.723.886</span></li>
                    <li class="flex gap-2"><strong class="text-[var(--color-text-primary)] shrink-0">Gebruiker</strong> <span>— iedere persoon die een account aanmaakt op het platform</span></li>
                    <li class="flex gap-2"><strong class="text-[var(--color-text-primary)] shrink-0">Bijdrage</strong> <span>— alle inhoud die een gebruiker deelt, waaronder fiches, uitwerkingen, reacties, bestanden en profielinformatie</span></li>
                    <li class="flex gap-2"><strong class="text-[var(--color-text-primary)] shrink-0">Initiatief</strong> <span>— een activiteitenidee op het platform, waaraan gebruikers fiches kunnen koppelen</span></li>
                    <li class="flex gap-2"><strong class="text-[var(--color-text-primary)] shrink-0">Fiche</strong> <span>— een praktische uitwerking van een initiatief, gedeeld door een gebruiker</span></li>
                </ul>
            </div>

            {{-- 2. Toepasselijkheid --}}
            <div>
                <h2 class="text-xl mb-3">2. Toepasselijkheid</h2>
                <p class="text-[var(--color-text-secondary)]">Door een account aan te maken of het platform te gebruiken, ga je akkoord met deze gebruiksvoorwaarden en ons <a href="{{ route('legal.privacy') }}" class="text-[var(--color-primary)] hover:underline">privacybeleid</a>. Hartverwarmers is uitsluitend bedoeld voor personen van 18 jaar of ouder.</p>
            </div>

            {{-- 3. Account --}}
            <div>
                <h2 class="text-xl mb-3">3. Account</h2>
                <ul class="list-disc list-inside space-y-1 text-[var(--color-text-secondary)]">
                    <li>Je bent verantwoordelijk voor de veiligheid van je account en wachtwoord.</li>
                    <li>De informatie die je bij registratie opgeeft, moet correct en actueel zijn.</li>
                    <li>Je mag slechts een account per persoon aanmaken.</li>
                    <li>Je mag je account niet overdragen aan een andere persoon.</li>
                </ul>
            </div>

            {{-- 4. Inhoud en licentie --}}
            <div>
                <h2 class="text-xl mb-3">4. Inhoud en licentie</h2>
                <p class="text-[var(--color-text-secondary)] mb-3">Door een bijdrage te delen op Hartverwarmers:</p>
                <ul class="list-disc list-inside space-y-1 text-[var(--color-text-secondary)]">
                    <li>Behoud je het <strong class="text-[var(--color-text-primary)]">auteursrecht</strong> op je eigen bijdragen.</li>
                    <li>Verleen je het platform een niet-exclusieve, kosteloze licentie om je bijdrage te tonen, op te slaan en beschikbaar te maken voor andere gebruikers.</li>
                    <li>Verleen je andere gebruikers het recht om je bijdrage te gebruiken, aan te passen en toe te passen binnen hun eigen woonzorgcentrum of zorginstelling, <strong class="text-[var(--color-text-primary)]">uitsluitend voor niet-commerciele doeleinden</strong>.</li>
                    <li>Bevestig je dat de bijdrage je eigen werk is, of dat je toestemming hebt om het te delen.</li>
                    <li><strong class="text-[var(--color-text-primary)]">Garandeert</strong> je dat je bijdrage geen inbreuk maakt op het auteursrecht, merkrecht of enig ander intellectueel eigendomsrecht van derden.</li>
                </ul>
                <p class="text-[var(--color-text-secondary)] mt-3">Gebruik van bijdragen buiten de context van de ouderenzorg of voor commerciele doeleinden is niet toegestaan zonder schriftelijke toestemming van de auteur.</p>
            </div>

            {{-- 4b. Vrijwaring --}}
            <div>
                <h2 class="text-xl mb-3">4b. Vrijwaring bij auteursrechtclaims</h2>
                <p class="text-[var(--color-text-secondary)] mb-3">Als gebruiker <strong class="text-[var(--color-text-primary)]">vrijwaart</strong> je de beheerder tegen alle claims, kosten en schade die voortvloeien uit een bewering dat jouw bijdrage inbreuk maakt op de intellectuele eigendomsrechten van een derde.</p>
                <p class="text-[var(--color-text-secondary)]">Dit betekent dat als een derde partij een claim indient met betrekking tot content die jij hebt geupload, jij verantwoordelijk bent voor de verdediging en eventuele kosten, en niet het platform.</p>
            </div>

            {{-- 5. Verwijdering --}}
            <div>
                <h2 class="text-xl mb-3">5. Verwijdering van account en inhoud</h2>
                <p class="text-[var(--color-text-secondary)] mb-3">Je kunt op elk moment vragen om je account te verwijderen door een e-mail te sturen naar <a href="mailto:info@hartverwarmers.be" class="text-[var(--color-primary)] hover:underline">info@hartverwarmers.be</a>. Bij verwijdering:</p>
                <ul class="list-disc list-inside space-y-1 text-[var(--color-text-secondary)]">
                    <li>Worden je accountgegevens (naam, e-mail, profiel) permanent verwijderd binnen 30 dagen.</li>
                    <li>Worden al je fiches, reacties, bestanden, likes en bladwijzers verwijderd.</li>
                    <li>Is deze actie onomkeerbaar.</li>
                </ul>
            </div>

            {{-- 6. Gedragsregels --}}
            <div>
                <h2 class="text-xl mb-3">6. Gedragsregels</h2>
                <p class="text-[var(--color-text-secondary)] mb-3">Op Hartverwarmers verwachten wij dat gebruikers:</p>
                <ul class="list-disc list-inside space-y-1 text-[var(--color-text-secondary)]">
                    <li>Respectvol omgaan met andere bijdragers en hun werk.</li>
                    <li>Geen misleidende, aanstootgevende of onrechtmatige inhoud delen.</li>
                    <li><strong class="text-[var(--color-text-primary)]">Geen persoonsgegevens van bewoners, patienten of andere derden delen</strong> — ook niet in fiches, foto's of bestanden. Gebruik altijd geanonimiseerde of fictieve namen.</li>
                    <li>Het platform niet gebruiken voor commerciele doeleinden of reclame.</li>
                    <li>Geen schadelijke bestanden uploaden.</li>
                    <li>Het intellectueel eigendom van anderen respecteren.</li>
                </ul>
            </div>

            {{-- 7. Intellectueel eigendom --}}
            <div>
                <h2 class="text-xl mb-3">7. Intellectueel eigendom van het platform</h2>
                <p class="text-[var(--color-text-secondary)]">Het ontwerp, de vormgeving, het DIAMANT-model, de structuur en alle door de beheerder gecreeerde inhoud van Hartverwarmers zijn eigendom van de beheerder. Gebruikers mogen deze niet reproduceren of hergebruiken buiten het platform zonder schriftelijke toestemming.</p>
            </div>

            {{-- 8. Moderatie --}}
            <div>
                <h2 class="text-xl mb-3">8. Moderatie en auteursrechtbeleid</h2>
                <p class="text-[var(--color-text-secondary)] mb-3">De beheerder behoudt het recht om:</p>
                <ul class="list-disc list-inside space-y-1 text-[var(--color-text-secondary)]">
                    <li>Bijdragen onmiddellijk te verwijderen bij een gegronde melding van auteursrechtinbreuk, conform onze <a href="{{ route('legal.copyright') }}" class="text-[var(--color-primary)] hover:underline">notice-and-takedown procedure</a>.</li>
                    <li>Bijdragen te verwijderen of aan te passen die in strijd zijn met deze voorwaarden.</li>
                    <li>Accounts te waarschuwen, op te schorten of te verwijderen bij herhaaldelijke of ernstige overtredingen.</li>
                </ul>
                <p class="text-[var(--color-text-secondary)] mt-3"><strong class="text-[var(--color-text-primary)]">Herhaaldelijke overtredingen:</strong> Bij herhaaldelijke auteursrechtinbreuken kan het account van de gebruiker tijdelijk of permanent worden geschorst. De beheerder bepaalt of er sprake is van een herhaaldelijke overtreding.</p>
                <p class="text-[var(--color-text-secondary)] mt-3">Gebruikers kunnen ongepaste inhoud of vermoedelijke auteursrechtinbreuken melden via <a href="mailto:info@hartverwarmers.be" class="text-[var(--color-primary)] hover:underline">info@hartverwarmers.be</a>.</p>
            </div>

            {{-- 9. Aansprakelijkheid --}}
            <div>
                <h2 class="text-xl mb-3">9. Aansprakelijkheid</h2>
                <p class="text-[var(--color-text-secondary)] mb-3">Hartverwarmers wordt aangeboden "zoals het is". De beheerder:</p>
                <ul class="list-disc list-inside space-y-1 text-[var(--color-text-secondary)]">
                    <li>Garandeert niet dat het platform ononderbroken of foutloos beschikbaar is.</li>
                    <li>Is niet aansprakelijk voor de juistheid of volledigheid van door gebruikers gedeelde inhoud.</li>
                    <li>Is niet aansprakelijk voor schade die voortvloeit uit het toepassen van gedeelde activiteiten of materialen.</li>
                </ul>
                <p class="text-[var(--color-text-secondary)] mt-3">Gebruik van gedeelde activiteiten en materialen is op eigen verantwoordelijkheid. Zorg ervoor dat activiteiten geschikt zijn voor de specifieke bewonersgroep in je instelling.</p>
            </div>

            {{-- 10. Wijzigingen --}}
            <div>
                <h2 class="text-xl mb-3">10. Wijzigingen</h2>
                <p class="text-[var(--color-text-secondary)]">Wij behouden het recht om deze voorwaarden te wijzigen. Bij belangrijke wijzigingen informeren wij gebruikers via e-mail of via een melding op het platform, met een redelijke termijn om kennis te nemen van de wijzigingen.</p>
            </div>

            {{-- 11. Toepasselijk recht --}}
            <div>
                <h2 class="text-xl mb-3">11. Toepasselijk recht en geschillen</h2>
                <p class="text-[var(--color-text-secondary)]">Op deze voorwaarden is het Belgisch recht van toepassing. Geschillen worden voorgelegd aan de bevoegde rechtbanken van het arrondissement Brussel.</p>
            </div>

            {{-- 12. Contact --}}
            <div>
                <h2 class="text-xl mb-3">12. Contact</h2>
                <p class="text-[var(--color-text-secondary)]">Voor vragen over deze voorwaarden kun je ons bereiken via <a href="mailto:info@hartverwarmers.be" class="text-[var(--color-primary)] hover:underline">info@hartverwarmers.be</a>.</p>
            </div>

        </div>
    </section>
</x-layout>
