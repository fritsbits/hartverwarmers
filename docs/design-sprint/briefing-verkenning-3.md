# Briefing verkenning #3 — Exploratiepagina "Kwaliteitstools"

*Opgesteld 24 augustus 2026, na de selectieronde in `.lavish/kwaliteitstools-verkenning.html`. Bedoeld als zelfstandige opdracht voor een nieuwe thread.*

## De opdracht

Bouw **één standalone exploratiepagina** in `docs/design-sprint/` (conventie: zoals `diamant-wizard-pistes.html` en `klassieker-routes.html`) met **ver uitgewerkte interface-mockups** van de zes geselecteerde kwaliteitstools hieronder. Géén productiecode, géén wijzigingen aan de app — dit is een wiki-achtige exploratie die Frederik kan screensharen en projecteren op de denkdag van **donderdag 27 augustus 2026** met Maite Mallentjer en Nadine Praet.

Per tool toont de pagina:

1. **Waar het inhaakt** — het bestaande scherm/moment (de aanhaakpunten hieronder zijn geverifieerd tegen de codebase)
2. **De flow in stappen** — wat de begeleidster ziet en doet
3. **Mockups in huisstijl** — behoorlijk ver uitgewerkt, alsof het al bestaat, in de echte Hartverwarmers-look
4. **De vraag aan Maite** — wat er op de denkdag over beslist moet worden

Herbruikbare mock-CSS bestaat al in de twee eerdere verkennings-HTML's (`.mock-frame`, `.gem`, `.m-sticky`, `.m-paper`, `.maite-card`). Screenshots van de huidige schermen staan in `.lavish/assets/` (fiche-show, wizard-stap1, doelen-index, doelen-autonomie) — bruikbaar als "vandaag"-referentie.

## Context in vijf zinnen

Hartverwarmers is een Laravel-platform waar activiteitenbegeleiders in Vlaamse woonzorgcentra activiteitenfiches delen, rond het DIAMANT-model (7 facetten in `config/diamant.php`: Doen, Inclusief, Autonomie, Mensgericht, Anderen, Normalisatie, Talent). De sprong die voorligt: van horizon 1 (activiteiten delen) naar horizon 2 (het model helpt mensen *betere* activiteiten bedenken). De tools worden later gebouwd achter admin-only Pennant-flags zodat "zonder/met" demonstreerbaar is; de exploratiepagina is de stap dáárvoor. Maites toetssteen: echt bruikbaar in haar opleidingen, waar begeleidsters klassiekers (bingo, zangnamiddag) nieuw leven inblazen. Nadine (Arteveldehogeschool) is academisch kritisch op DIAMANT-onderbouwing: claims klein houden, beschrijvend.

**Harde no-gos:** geen scores/ratings richting gebruikers (de diamantscore blijft admin-only), alles optioneel, nooit belerend, geen chatbot, geen print-gebaseerde concepten.

## Huisstijl

- Fonts: **Aleo** 700 (koppen) + **Fira Sans** 300–700 (body), via Bunny Fonts
- Tokens: oranje `#E8764B` (hover `#D4683F`), teal `#4CB7C5`, geel `#F4C44E`, paars `#B57BB3`; tekst `#231E1A`/`#756C65`/`#C0B5AE`; cream `#FEF8F4`, subtle `#F5F0EC`, accent-light `#FDF3EE`; borders `#EBE4DE`/`#DDD5CD`
- Componenttaal: `.section-label` (uppercase oranje), `.cta-link` (oranje + pijl), `.btn-pill` (spaarzaam), flux:card, papiertextuur/polaroid/prikbord-esthetiek
- Facet-badge is het Blade-component `<x-diamant-gem>` (er bestaat géén `.diamant-badge`-CSS-class); **per-facet kleuren bestaan nog nergens** — de exploratiepagina mag een 7-kleurenpalet voorstellen als designbeslissing voor Maite
- Copy: Vlaams-Nederlands, imperatief, peer-toon, geen Hollandismen ("woonzorgcentrum", "bewoners", "begeleidster")

---

## De zes gekozen tools (status: BOUWEN → mock ver uitwerken)

### 1. A2 — "Laat ze schitteren": knop naast de download

**Gekozen variant: drie facetten, vaste keuzes (geen AI).**
Naast de downloadknop op de fichepagina een secundaire knop in `.cta-link`-stijl met diamant-icoon: *"Laat ze schitteren (5 min)"*. Opent een wizard (flux:modal) met 3 stappen — één per actief DIAMANT-doel van het initiatief. Per stap: `<x-diamant-gem>` + facetnaam (Aleo), de `core_question` uit de config als spiegelvraag ("Hebben je bewoners vandaag iets gekozen — of is alles voor hen beslist?"), en 2-3 aanvinkbare gecureerde ideeën (chips, actief = accent-light). Overslaan is overal een volwaardige knop. Eindscherm: het persoonlijke lijstje "zo maak ik van deze fiche een diamantje" in prikbord-esthetiek + "Mail naar mezelf".

**Extra te verkennen (expliciete feedback van Frederik):** downloaders willen vooral het bestand — niets mag ná de bedank-modal komen. Verken daarom in de mockup ook de piste waar de schitter-triggers **mee in het downloadpakket verpakt zitten**: een begeleidend documentje in de zip met de fiche-info plus de gekozen (of standaard-)diamant-triggers. De download-flow bouwt vandaag al zips (`zip_path`, on-the-fly fallback in `FicheController::downloadFiles()`).

**Aanhaakpunt:** `resources/views/fiches/show.blade.php` (downloadknoppen rond regels ~283 en ~340). Content: `core_question` + `adaptations` per facet in `config/diamant.php`; facetselectie via `$fiche->initiative->diamant_guidance` (nullable → fallback op 3 vaste facetten).

### 2. B2 — "Vraag je af": reflectieblok op de fichepagina

**Gekozen varianten: beide — kernvraag per actief doel én uitklapbare facetkaartjes met contrastpaar.**
De fichepagina toont vandaag *nul* DIAMANT-inhoud. Nieuw blok: per actief doel een rij met `<x-diamant-gem>`, facetnaam (Aleo bold), en de `core_question` cursief in tekst-secundair, in ruled-paper-quotestijl. Onderaan één `.cta-link` "Meer over de 7 doelen". Elke rij klapt open (accordion-patroon bestaat al op `initiatives/show.blade.php` regels 267-301) naar de "ik wil"-zin plus het **contrastpaar**: links `contrast_positive` op accent-light met diamantje, rechts `contrast_negative` op bg-subtle — bewust zonder rood of kruisje, nooit belerend. Mobiel gestapeld.

**Demo-verhaal:** de contrastparen zitten al maanden gevalideerd in `config/diamant.php` en worden nérgens gerenderd — "flag aan en slapende content wordt een spiegel".

### 3. C1 — "Wat er al schittert": de diamantscore wordt collega-feedback

**Gekozen varianten: alleen de waardering én waardering + één kans.**
De LLM-pipeline (`AssessFicheQuality` → `FicheQualityAgent`) schrijft vandaag al een Vlaamse beoordeling bij elke fiche, maar alleen de admin ziet die. Mock: op de eigen fichepagina ziet de **auteur** een compact cream kaartje: ✨-icoon, kop "Wat er al schittert" (Aleo bold), twee-drie zinnen concrete waardering ("Bewoners kiezen zelf de liedjes — dat is Autonomie ten voeten uit") + de betrokken facet-gems. Afsluiter in tekst-tertiair: *"Meegelezen door onze digitale collega — het laatste woord is altijd aan jou."* Variant 2 voegt onder een divider precies **één** kans toe, altijd als vraag ("Zou een bewoner de materialen kunnen klaarzetten?"), met `.cta-link` "Vul mijn fiche aan" en een stille knop "Zo is ze goed" — afwijzen is een gevierde keuze. **Nooit het cijfer tonen.**

### 4. F1 — "Fris je klassieker op": de herken-werkvorm met contrastparen

**Gekozen varianten: gecureerd (geen AI) én AI herschrijft de scènes naar jouw activiteit.**
Nieuwe werkvorm-pagina. Startscherm: raster van 6-8 klassieker-tegels (bingo, zangnamiddag, kaarten, wandeling…). Dan 7 herken-schermen: per facet twee mini-scènes als polaroidkaarten naast elkaar ("Bingo A: de begeleidster roept de nummers en deelt de prijsjes uit" vs. "Bingo B: Maria roept de nummers met haar marktstem en de winnaar kiest uit de prijzenmand") — tik aan welke op jouw versie lijkt, geen goed of fout. Na elke keuze schuift een warm kaartje open: "Herkenbaar. Eén idee om op te schuiven: …" + "zet op mijn spiekkaart". Slotscherm: de **spiekkaart** — max 3 zinnen op een geel notitieblok-kaartje, groot genoeg om met je telefoon te fotograferen. AI-variant: vooraf typ (of spreek) je je klassieker in twee zinnen; een agent herschrijft de 7 contrastparen naar háár activiteit (✨-lint "op maat van jouw activiteit"); zonder AI-key valt alles stil terug op de gecureerde scènes.

**Workshopgebruik (kern):** individueel herkennen (10 min), dan plenair rond één moedige deelnemer. Dit is Maites A/B-oefening, maar dan digitaal.

### 5. F2 — De Schitterscan: zelfscan met sterktes en groeikansen

**Gekozen variant: solo-scan met resultaatblad.**
±20 stellingen (à la de zelfscan-werkvorm die Nadine gebruikt) waarin een begeleidster haar eigen klassieker toetst: "Bij mijn activiteit kiezen bewoners zelf iets" — klopt / klopt een beetje / klopt niet. Eén stelling per scherm, grote tikknoppen, voortgangsbalk als 7 facetkleursegmenten (geen percentage). Resultaat: **geen score** maar twee kolommen — "Dit schittert al" en "Hier zit een groeikans", per facet gegroepeerd, met bij elke groeikans één gecureerde tip en een `.cta-link` naar de facetpagina. Framing Nadine-proof: beschrijvend, geen cijfers.

### 6. F5 — De Schittergalerij: voor & na van klassiekers

**Gekozen variant: bladerbare voor/na-galerij (gecureerd).**
8-10 makeovers van herkenbare klassiekers: galerij als raster van polaroid-flux:cards (titel, 2-3 facet-gems). Detail: tweekoloms — links een vaal grijzig kader "Zoals het vaak gaat", rechts een warm cream kader "Zo kan het schitteren", met de verschoven zinnen gemarkeerd (accent-light) en bij hover een tooltip met facet + één zin waarom. Onderaan `.cta-link` "Probeer dit met jouw klassieker" → de herken-werkvorm (F1). Toon: "één mogelijke versie", nooit "beste praktijk". Voor de exploratiepagina volstaan **2-3 volledig uitgeschreven makeovers** (bingo als hoofdvoorbeeld); de content zou later als JSON op de content-disk leven (patroon `ToolsInspirationController` + `JsonContent`).

---

## Misschien-strook (kleiner tonen, als "ook op tafel")

- **B1 De Diamantbril** — aan/uit-paneel met reflectievragen op de fichepagina; overlapt met B2. Eventueel als alternatieve uitvoering náást B2 schetsen (paneel + facet-uitlichting van fichesecties), niet volledig uitwerken.
- **G1 Hoe ging het? — reflectie bij herbezoek** (beide varianten aangevinkt). Frederiks notities: het gevraagd worden "hoe ging het voor jou / wat heb jij anders gedaan" is óók los van DIAMANT waardevol — een soort feedback/remix op een gedane activiteit. Maar remix × diamantfacetten koppelen vond hij vergezocht: **houd de remix simpel**, de reflectie menselijk. Eén mockup volstaat (banner bij herbezoek + drie tikbare mini-antwoorden die een publieke reactie worden).
- **E1 "Twee scènes, één tik"** op de doelenpagina (variant aangevinkt zonder status) — dicht bij F1; eventueel één mini-mockup: de contrastparen als tikbare kaarten op de bestaande facetpagina.

## Geparkeerd (niet op de exploratiepagina, wel noteren)

- **G2 Kwartaalfacet**: Frederik wil de kwartaal-/seizoenscyclus onthouden — "dit seizoen zetten we facet X in de kijker", met o.a. social-posts over activiteiten rond dat facet. Bewust geparkeerd.
- **A1 Schittercheck-modal na download**: afgevoerd omdat downloaders vooral het bestand willen en er al een bedank-modal komt — de trigger verhuist naar het downloadpakket zelf (zie A2-extra).
- Alle overige ideeën uit de catalogus: zie `.lavish/kwaliteitstools-verkenning.html` (22 ideeën, 68 varianten, met geverifieerde aanhaakpunten).

## Rode draden voor de mockups

1. **Herkennen > reflecteren** — quizzen zonder goed/fout, contrastparen, tikken i.p.v. typen (doelgroep: vrouwen 35-55, niet tech-native, weinig tijd).
2. **Slapende content activeren** — contrastparen, kernvragen, adaptations en praktijkverhalen zitten al gevalideerd in `config/diamant.php`; bijna elke mock kan met échte, bestaande teksten gevuld worden. Gebruik ze letterlijk.
3. **De voorbeeldfiche** — "Een oude passie opnieuw beleven: naar de beenhouwerij" (bewoonster was slager, kiest zelf charcuterie, leefgroep eet haar soep) is de vaste demo-casus uit alle eerdere verkenningen; bingo is de vaste klassieker-casus.
4. **AI als collega, nooit als beoordelaar** — suggesties zijn vragen; neem over · pas aan · sla over; de gebruiker tekent altijd zelf.
5. **Warm, tactiel, scrapbook** — polaroids, geruit papier, gele post-its, prikbord; nooit klinisch.
