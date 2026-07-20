# Briefing — Verkenning #2: "Een klassieke activiteit laten schitteren"

**Voor:** een verse AI-agent/thread die verkenning #2 maakt
**Van:** de thread die verkenning #1 en de brainstorm maakte (12 juni 2026)
**Opdrachtgever:** Frederik Vincx — bereidt een online meeting voor met **Maite Mallentjer** (pedagoog dagbesteding, inhoudelijke partner van het platform)

---

## 1. De opdracht

Maak een **tweede verkenningsdocument** (zelfde genre als verkenning #1: een digesteerbaar, screenshare-baar overzicht — géén werkende code, niets in de app zelf) dat het concept "DIAMANT-wizard" op **andere manieren** bekijkt dan verkenning #1 deed.

Vertrek van de brainstorm in `docs/design-sprint/2026-06-12-brainstorm-interactievormen.md` — **lees die eerst volledig**. Die bevat 12 concept-schetsen, de variatie-assen, vier voorgestelde kruisingen ("routes") en een suggestie voor de opbouw van verkenning #2. De schetsen zijn divergent materiaal: selecteer, combineer en scherp aan — werk niet slaafs alle 12 uit.

**Check bij de start kort met Frederik** welke richtingen uit de brainstorm hij wil uitwerken (de brainstorm stelt er vier voor + het canvas), en in welke vorm (zelfde HTML-deckstijl als verkenning #1, of anders).

## 2. De feedback van Frederik die deze tweede ronde stuurt

Op verkenning #1 (de 5 pistes) gaf hij deze koerscorrectie:

1. **Herformuleer het vertrekpunt.** Niet "een fiche laten schitteren" (veronderstelt een bestaande fiche op het platform) maar **"hoe laat je een klassieke activiteit schitteren"** — de bingo, zangnamiddag of knutselmiddag die een begeleidster al jaren doet en die níét op het platform staat. Het hoeft niet meer per se aan een fiche te hangen.
2. **Verbreed voorbij de AI-as.** Verkenning #1 varieerde vooral "hoeveel AI". Hij zoekt andersoortige interactievormen om over een activiteit na te denken — **eventueel in meerdere stappen** of gespreid in de tijd.
3. **Output hoeft niet tastbaar of printbaar te zijn.**

## 3. Wat er al ligt (paden relatief aan projectroot)

| Bestand | Wat het is |
|---|---|
| `docs/design-sprint/diamant-wizard-pistes.html` | **Verkenning #1** — 5 pistes op de AI-as (Keuzewizard, Schitterkaarten, Slimme Collega, Diamantgesprek, Schittermoment), elk met mock-screens, groepssessienotes en vragen voor Maite. Bekijk hem in de browser om de stijl en het niveau te zien. De 5 mechanieken blijven geldig als bouwstenen. |
| `docs/design-sprint/2026-06-12-brainstorm-interactievormen.md` | **De brainstorm** — input voor jouw werk. Bevat ook een eerlijke kritiek op verkenning #1: de spannende assen zijn *waar komt de activiteit vandaan*, *wanneer gebeurt het denken* en *met wie* — niet "hoeveel AI". |

Verkenning #2 moet **naast** verkenning #1 kunnen staan in de meeting: overlap vermijden, ernaar verwijzen mag.

## 4. Projectcontext (het hoognodige)

- **Platform:** Hartverwarmers — Nederlandstalig (Vlaams) Laravel-platform waar animatoren in woonzorgcentra activiteitenfiches delen. Kernbegrippen: *initiatieven* (ideeën) → *fiches* (praktische uitwerkingen).
- **DIAMANT:** 7-facettenmodel van Maite voor betekenisvolle activiteiten — **D**oen, **I**nclusief, **A**utonomie, **M**ensgericht, **A**nderen, **N**ormalisatie, **T**alent. Alle facetcontent (kernvragen, contrastparen, praktijkverhalen zoals Rosa/Albert/Wiske, tips, aanpassingen) staat in **`config/diamant.php`** — dat is je content-goudmijn; gebruik die teksten verbatim waar mogelijk.
- **Projectfiche (Notion, samengevat):** wizard die DIAMANT activeert op het moment dat het ertoe doet. Appetite: 1–2 weken bouwtijd, na validatie met Maite. **No-gos: geen scores of ratings van fiches/activiteiten · altijd optioneel · niet belerend · eerste versie zonder opslag van antwoorden.** De meeting met Maite is de validatie-spike; mogelijk gebruikt zij het ook in groepsopleidingen.
- **Doelgroep:** animatoren/begeleidsters in Vlaamse woonzorgcentra, overwegend vrouwen 35–55, praktisch, **niet tech-native**, weinig tijd (werkpauzes). Typen is een drempel; tikken/kiezen werkt. Mag nooit als beoordeling voelen.
- **Merk:** warm, praktisch, aanmoedigend — een collega, geen autoriteit. Kernemotie: **belonging en trots**. Esthetiek: scrapbook/prikbord, cream & oranje, polaroids, papier.

## 5. Praktische aanwijzingen

- **Locatie deliverable:** `docs/design-sprint/` — kies een sprekende bestandsnaam.
- **Huisstijl** (indien je mocks maakt): tokens staan in `CLAUDE.md` van het project (oranje `#E8764B`, cream `#FEF8F4`, ink `#231E1A`, e.d.). Fonts: **Aleo** (koppen, 700) + **Fira Sans** (body) via Bunny Fonts. Verkenning #1 bevat herbruikbare CSS: mock-browserframes (`.mock-frame`), gem-vorm (`.gem`, clip-path), post-its (`.m-sticky`), papier (`.m-paper`), gele "vraag voor Maite"-kaarten (`.maite-card`). Kopieer gerust.
- **Voorbeeldcontent:** fiche **id 416** — *"Een oude passie opnieuw beleven: naar de beenhouwerij"* (bewoonster was slager; kiest zelf charcuterie; leefgroep eet haar soep). Voor klassiekers: gebruik herkenbare voorbeelden als bingo, zangnamiddag, kaartnamiddag. Fictieve namen ok (gebruiker "Els", bewoonster "Maria") — markeer ze als fictief.
- **Taal:** Vlaams-Nederlands, imperatief, peer-toon. Geen Hollandismen ("hartstikke", "gewoon" als bijwoord, "lekker" buiten eten, "super"). Wel: woonzorgcentrum, bewoners, begeleidster, animatoren, leefgroep.
- **Screenshot-verificatie:** Playwright is globaal geïnstalleerd; draai scripts met `NODE_PATH=$(npm root -g) node /tmp/script.cjs` (`.cjs`, niet `.js`). Eén screenshot-pass aan het einde volstaat.
- **Raak de applicatiecode niet aan.** Dit is verkennend meeting-materiaal. Geen migraties, geen routes, geen Livewire.

## 6. Waar dit naartoe moet

Frederik wil in de meeting met Maite kunnen **voelen en vergelijken** hoe verschillende benaderingen zouden werken, om samen te beslissen wat de eerste bouwronde wordt (appetite 1–2 weken, gefaseerd mag). Goede feedback uit die meeting klinkt als *"dit zou ik gebruiken in een sessie"* of *"dit mist X"*. Maak dus per uitgewerkte richting expliciet: voor wie, op welk moment, wat de gebruiker doet, wat Maite eraan heeft in opleidingen, en welke vraag je aan Maite stelt.
