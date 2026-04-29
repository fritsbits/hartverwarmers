# Context & doelpubliek

Wie zijn de mensen voor wie Hartverwarmers gemaakt is, en in welke werkrealiteit gebruiken ze het?

## Primair doelpubliek: activiteitenbegeleiders in Vlaamse woonzorgcentra

Activiteitenbegeleiders ("animatoren", "begeleidsters") in Vlaamse residentiële woonzorgcentra. Een paar invalshoeken op wie zij zijn:

- **Demografisch:** mostly vrouwen, leeftijd 35–55. Niet tech-native, maar wel digitaal handelbaar — ze gebruiken smartphones, e-mail, eenvoudige online tools dagelijks.
- **Praktisch ingesteld:** ze willen geen abstracte theorie of academische frameworks. Ze willen ideeën die ze morgen kunnen uitvoeren, met materialen die voorhanden zijn.
- **Werkdruk:** ze hebben weinig tijd. Bezoek aan het platform gebeurt tijdens een korte pauze of bij voorbereiding van de week. De zoektocht moet snel resultaat opleveren.
- **Trots op het werk:** activiteitenbegeleiding wordt soms onderschat ("knutselen met bejaarden") terwijl het in werkelijkheid pedagogisch werk is dat directe impact heeft op levenskwaliteit. Erkenning is schaars; het platform compenseert door bijdragers expliciet te vermelden.
- **Geïsoleerd:** vaak één begeleidster per WZC of per afdeling. Geen vaste collega's om mee te brainstormen. Ideeën inkopen bij andere WZC's via informele netwerken kostte tijd en energie — het platform centraliseert dat.

## De job to be done

Het platform helpt bij één terugkerende, weekverzwarende vraag: **"Wat ga ik volgende week doen met mijn bewoners?"**

In bredere zin: **vinden, aanpassen en delen van praktische activiteitenideeën die genuinely de levenskwaliteit van bewoners verbeteren.** Niet "tijd vullen" — wel "betekenis maken".

Concrete sub-jobs:

- **Inspiratie zoeken** voor een specifiek thema, seizoen of doelgroep (bv. "wat doen we voor Verloren Maandag met bewoners met dementie?").
- **Een idee adapteren** aan de eigen context (groepsgrootte, mobiliteit, materiaalmogelijkheden).
- **Een goed idee delen** met collega's binnen de eigen organisatie of breder (erkenning + bijdrage aan de community).
- **Op de hoogte blijven** van wat er in andere WZC's gebeurt — leren door te zien wat werkt.

## Secundair doelpubliek

- **Vrijwilligers en mantelzorgers** — gebruiken het platform om iets concreet te kunnen doen tijdens een bezoek. Minder frequent, maar belangrijke verbreding.
- **Studenten ergotherapie en zorg** — gebruiken het in hun opleiding, met begeleiders zoals Maite Mallentjer (AP Hogeschool) en Nadine Praet (Arteveldehogeschool).
- **Familieleden** — vooral op zoek naar inspiratie voor bezoekjes of geschenken die meer doen dan tijdverdrijf.

## Admin-publiek

Eén persoon: de platformbeheerder (Frederik). Technisch onderlegd. Gebruikt admin-pagina's om platformgezondheid te volgen, kwaliteitsscores te bekijken en AI-suggestiegebruik te monitoren. Admin-pagina's zijn interne tools — andere designprincipes dan de publieke kant (zie [`docs/DESIGN_SYSTEM.md`](../DESIGN_SYSTEM.md), sectie *Admin pages*).

## Wat dat betekent voor het ontwerp

Een paar consequenties die in elk designbesluit terugkomen:

- **Warmte boven polish.** Het platform is het tegenovergestelde van wat de gebruikers proberen weg te werken in hun WZC: kil, klinisch, institutioneel. Cream backgrounds, soft shadows, papertextures, organic touches. Geen SaaS-dashboard.
- **Direct bruikbare inhoud.** Een fiche moet binnen 30 seconden te scannen zijn. Materialen, doelgroep, tijd duidelijk in de openingsregels. Bijlagen zijn extra, niet vervanging van core info.
- **Vlaams Nederlands, niet Hollands.** Imperatieven ("Registreer", "Ontdek", "Deel"). Niets dat klinkt als een opleiding of beleidstekst. Voorkeurstaal: peer, niet autoriteit. Vermijd "hartstikke", "lekker", "super" als intensifier.
- **Toegankelijke leesgrootte.** WCAG AA, base 17px voor formulieren. Veel gebruikers zijn 40–60 en niet digitaal vlot.
- **Erkenning als motor.** Bijdragers worden zichtbaar vermeld. Likes en kudos versterken het gevoel "ik hoor erbij".

## Open punten / hiaten

Wat we nog niet expliciet hebben en zou helpen:

- **Persona's** als zelfstandig document (nu impliciet hier en in [`CLAUDE.md`](../../CLAUDE.md)).
- **Job stories** in JTBD-formaat ("Wanneer ik..., wil ik..., zodat...").
- **User journeys** — van eerste bezoek → registratie → eerste fiche-creatie → terugkeer.
- **Gebruikersinterviews** — geen recente, gestructureerde input. Maite & WZC-testers hebben in Q1 2026 wel feedback gegeven (zie [strategy/okrs-2026.md](../strategy/okrs-2026.md)).

Volgende stap zou zijn: één persona-document schrijven met JTBD-statements, op basis van bestaande waarnemingen en (indien mogelijk) een paar gerichte gesprekken.
