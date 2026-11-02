# Themakalender 2027: bronverslag

Broncontrole voor `database/seeders/data/themes.json`, uitgevoerd op 2 november 2026 volgens `docs/superpowers/plans/2026-11-02-themakalender-2027.md`.
Alle bronnen zijn op die dag geraadpleegd.

## Werkwijze

Een datum komt in `occurrences_2027` als minstens een van deze vier dingen geldt:

1. De organisator of een officiële bron publiceert de datum voor 2027.
2. Het is een vaste internationale dag (VN, WHO, UNESCO of vergelijkbaar) of een wettelijke feestdag.
3. De organisator noemt zelf een regel die de datum vastlegt, zoals "de tweede zondag van september".
4. De datum volgt uit Pasen of uit de stand van de zon.

Een thema zonder zo'n bron krijgt bewust geen occurrence in 2027.
Het blijft in het bestand staan, met zijn 2026-datum, en staat hieronder bij de open thema's.
`tests/Feature/ThemesJsonIntegrityTest.php` houdt die lijst bij in `OPEN_IN_2027`.

`themes:import` behandelt een jaarblok nu als de volledige lijst voor dat jaar.
Een thema dat in het blok ontbreekt, verliest zijn occurrence van dat jaar in de databank.
Zonder die aanpassing zou een oude, ongecontroleerde 2027-rij gewoon blijven staan.

De fichecatalogus komt uit de lokale databank: 470 gepubliceerde fiches, de jongste van 1 september 2026.
De productie-dry-run moet bevestigen dat alle gekozen slugs daar ook bestaan.

## Nieuwe thema's

Twintig thema's, zodat januari tot en met april een eigen invulling krijgen.
Moederdag vult een gat in mei: de mei-intro noemt moederdag, maar de kalender had alleen de Antwerpse variant op 15 augustus.

| Slug | 2027 | Regel | Bron |
|---|---|---|---|
| `nieuwjaar` | 1 jan | vast | [robinfo.oma.be, Belgische feestdagen 2027](https://robinfo.oma.be/nl/astro-info/kalender/belgische-feestdagen/belgische-feestdagen-2027/) |
| `driekoningen` | 6 jan | vast | kerkelijke kalender |
| `verloren-maandag` | 11 jan | maandag na de eerste zondag na Driekoningen | [nl.wikipedia.org, Verloren maandag](https://nl.wikipedia.org/wiki/Verloren_maandag) |
| `wereld-knuffeldag` | 21 jan | vast | verplaatst, zie correcties |
| `poezieweek` | 28 jan tot 3 feb | laatste donderdag van januari, zeven dagen | [poezieweek.com](https://www.poezieweek.com): "Van 28 januari t.e.m. 3 februari 2027" |
| `lichtmis` | 2 feb | vast | kerkelijke kalender |
| `carnaval` | 7 tot 9 feb | Pasen min 49 dagen, tot vastenavond | Pasen 2027 op 28 maart volgens robinfo.oma.be |
| `krokusvakantie` | 8 tot 14 feb | schoolkalender | [vlaanderen.be, schoolvakanties](https://www.vlaanderen.be/onderwijs-en-vorming/wat-mag-en-moet-op-school/schoolvakanties-vrije-dagen-en-afwezigheden/schoolvakanties) |
| `valentijn` | 14 feb | vast | traditie |
| `week-van-de-vrijwilliger` | 27 feb tot 7 mrt | jaarlijks vastgelegd | [vlaanderenvrijwilligt.be](https://www.vlaanderenvrijwilligt.be/aanbod/campagnes/week-van-de-vrijwilliger/): "in 2027 van 27/02 tot en met 7/03" |
| `internationale-vrouwendag` | 8 mrt | vast | VN-dag |
| `lente` | 20 mrt | maartequinox | 20 maart 2027, 21.25 uur Belgische tijd, volgens Wikipedia en Wikibooks |
| `dag-van-de-zorg` | 20 tot 21 mrt | jaarlijks vastgelegd | [dagvandezorg.be](https://www.dagvandezorg.be/): "Op zaterdag 20 en zondag 21 maart 2027" |
| `pasen` | 28 mrt | Pasen | robinfo.oma.be |
| `paasvakantie` | 29 mrt tot 11 apr | schoolkalender | vlaanderen.be, schoolvakanties |
| `1-april` | 1 apr | vast | traditie |
| `wereld-parkinsondag` | 11 apr | vast | Europese Parkinsonvereniging (EPDA) |
| `erfgoeddag` | 18 apr | eerste zondag na de paasvakantie | [erfgoeddag.be](https://www.erfgoeddag.be): "Zondag 18 april 2027" |
| `dag-van-de-aarde` | 22 apr | vast | earthday.org |
| `wereldboekendag` | 23 apr | vast | UNESCO |
| `moederdag` | 9 mei | tweede zondag van mei | vaste Belgische regel |

`wereld-parkinsondag` heeft geen passende fiche en krijgt `fiche_slugs: []`.
Het thema staat op de kalender, maar niet in de maandmail.

## Correcties op bestaande thema's

Per correctie: de oude waarde, de nieuwe regel, de 2027-datum en de bron.

| Slug | Stond | Wordt | 2027 | Bron |
|---|---|---|---|---|
| `open-monumentendag` | vast, 14 tot 15 sep | tweede zondag van september | 12 sep | [vlaanderen.be](https://www.vlaanderen.be/cultuur-sport-en-vrije-tijd/erfgoed/open-monumentendag): "Op 12 september 2027, de tweede zondag van september" |
| `duurzame-dinsdag` | vast, 5 sep | eerste dinsdag van september | 7 sep | [duurzamedinsdag.nl](https://www.duurzamedinsdag.nl/) |
| `baarddag` | vast, 2 sep | eerste zaterdag van september | 4 sep | [nationaltoday.com, World Beard Day](https://nationaltoday.com/world-beard-day/) |
| `internationale-dag-van-het-bier` | vast, 5 aug | eerste vrijdag van augustus | 6 aug | [en.wikipedia.org, International Beer Day](https://en.wikipedia.org/wiki/International_Beer_Day) |
| `wereld-dovendag` | vast, 23 sep | laatste zaterdag van september | 25 sep | [nl.wikipedia.org, Werelddovendag](https://nl.wikipedia.org/wiki/Werelddovendag); Doof Vlaanderen vierde op zaterdag 27 september 2025 |
| `dag-van-de-koffie` | vast, 26 sep | vast, 1 okt | 1 okt | [ico.org](https://ico.org/international-coffee-day.asp) |
| `herfst` | vast, 21 sep | septemberequinox | 23 sep | 06.02 uur UTC, [weather.gov, seasons](https://weather.gov/media/ind/seasons.pdf) |
| `dag-van-de-palliatieve-zorg` | vast, 9 okt | tweede zaterdag van oktober | 9 okt | World Hospice and Palliative Care Day |
| `reumadag` | vast, 11 okt | vast, 12 okt | 12 okt | Wereldreumadag |
| `dag-tegen-kanker` | vast, 21 okt | derde donderdag van oktober | 21 okt | [VRT, 2022](https://www.vrt.be/vrtnws/nl/2022/10/20/23ste-dag-tegen-kanker-ik-heb-al-wat-traantjes-moeten-laten-ze/); 2022, 2023 en 2026 vielen op de derde donderdag |
| `herfstvakantie` | 30 okt tot 7 nov | schoolkalender, één week | 1 tot 7 nov | vlaanderen.be, schoolvakanties: "van maandag 1 november tot en met zondag 7 november 2027" |
| `voorleesweek` | vast, 20 tot 28 nov | laatste volledige week van november | 20 tot 28 nov | [iedereenleest.be](https://www.iedereenleest.be/voorleesweek) |
| `wereld-knuffeldag` | vast, 21 nov | vast, 21 jan | 21 jan | World Hugging Day valt op 21 januari; voor 21 november is geen bron gevonden |
| `vrijwilligersdag` | vast, 7 dec | vast, 5 dec | 5 dec | [Vlaanderen Vrijwilligt](https://www.vlaanderenvrijwilligt.be/wp-content/uploads/2025/10/Doe-mee-met-IVY2026.pdf); 7 december is de Nederlandse datum |
| `lichtjesdag` | vast, 11 dec | tweede zondag van december | 12 dec | Wereldlichtjesdag |
| `winter` | vast, 21 dec | decemberzonnewende | 22 dec | 02.42 uur UTC, weather.gov |

Bij de palliatieve zorg en de Dag tegen Kanker klopte de 2027-datum toevallig, maar de regel niet.
Vanaf 2028 zou de generator er naast zitten.

De seizoenen `lente`, `herfst` en `winter` staan nu op `variable_annual`.
De generator meldt ze voortaan als handwerk.
Dat kost elk jaar drie datums opzoeken, maar het alternatief is een vaste datum die in 2027 twee dagen te vroeg valt.

## Correcties op 2026

Apart vermeld, omdat ze het lopende jaar raken.
Elke datum komt van de organisator of volgt uit de regel hierboven.

| Slug | Stond | Wordt |
|---|---|---|
| `herfstvakantie` | 31 okt tot 8 nov | 2 tot 8 nov |
| `voorleesweek` | 20 tot 28 nov | 21 tot 29 nov |
| `vrijwilligersdag` | 7 dec | 5 dec |
| `lichtjesdag` | 11 dec | 13 dec |
| `dag-van-de-wetenschap` | 28 nov | 15 nov |
| `wereld-knuffeldag` | 21 nov | 21 jan |
| `dag-van-de-buren` | 26 mei | 29 mei |
| `internationale-dag-van-het-bier` | 5 aug | 7 aug |
| `duurzame-dinsdag` | 5 sep | 1 sep |
| `baarddag` | 2 sep | 5 sep |
| `open-monumentendag` | 14 tot 15 sep | 13 sep |
| `herfst` | 21 sep | 23 sep |
| `wereld-dovendag` | 23 sep | 26 sep |
| `dag-van-de-koffie` | 26 sep | 1 okt |
| `dag-van-de-palliatieve-zorg` | 9 okt | 10 okt |
| `nacht-van-de-duisternis` | 9 okt | 10 okt |
| `reumadag` | 11 okt | 12 okt |
| `dag-tegen-kanker` | 21 okt | 15 okt |
| `week-van-smaak` | 20 tot 26 nov | 26 okt tot 1 nov |

## Open thema's

Deze elf thema's hebben geen occurrence in 2027.
Ze komen terug zodra een bron de datum bevestigt.

| Slug | Waarom open | Wat er wel is |
|---|---|---|
| `dag-van-de-buren` | organisator noemt geen regel; 2025 week af van "laatste vrijdag van mei" | 2026: vrijdag 29 mei, [lafetedesvoisins.be](https://lafetedesvoisins.be/) |
| `week-van-de-geletterdheid` | organisatorsite onbereikbaar, geen 2026- of 2027-datum | 2025 vermoedelijk 8 tot 14 september, niet bevestigd |
| `nacht-van-de-duisternis` | alleen 2026 gevonden, geen regel | 2026: zaterdag 10 oktober, [levendenacht.be](https://levendenacht.be/nacht-van-de-duisternis) |
| `week-van-smaak` | geen 2027-datum | 2026: 26 oktober tot 1 november, [weekvandesmaak.be](https://www.weekvandesmaak.be) |
| `dag-van-de-wetenschap` | organisator schoof 2026 een week op, dus geen betrouwbare regel | 2026: 15 november, [dagvandewetenschap.be](https://www.dagvandewetenschap.be) |
| `french-fries-day` | Amerikaanse dag, bronnen spreken elkaar tegen (13 juli of tweede vrijdag van juli) | geen |
| `nationale-frietjesdag` | geen bron gevonden | geen |
| `nationale-kappersdag` | geen bron gevonden | geen |
| `week-tegen-pijn` | geen Belgische of Vlaamse organisator gevonden | geen |
| `week-van-de-ouderen` | geen Vlaamse editie gevonden, alleen lokale seniorenweken | geen |
| `dag-van-de-verzorgenden-en-zorgkundigen` | geen datum gevonden | geen |

Alle elf staan op `variable_annual`.
Zo berekent `themes:extend-occurrences` er voor 2028 geen datum zonder bron bij, maar meldt het ze als handwerk.

De laatste zes hebben helemaal geen bron.
Schrappen is een redactionele keuze en hoort niet bij deze broncontrole.

## Behouden met een kanttekening

- `dag-van-de-animator`, 27 juli: de dag bestaat ([VRT, 2023](https://www.vrt.be/vrtnws/nl/2023/07/26/dag-van-de-animator-wordt-gevierd-op-het-speelplein-van-overijse/)), maar gaat over speelpleinanimatoren, niet over animatoren in de ouderenzorg.
- `dag-van-het-therapiedier`, 17 november: een enkele, zwakke bron (een bericht van het College of Europe uit 2021).
- `dag-van-de-senioren`, 21 augustus: de Amerikaanse National Senior Citizens Day, zonder Belgische tegenhanger.
- `terug-naar-school`, 1 september: vlaanderen.be zegt dat het schooljaar op 1 september begint, tenzij dat in een weekend valt; 1 september 2027 is een woensdag.
- `ronde-van-frankrijk`, 2 tot 25 juli: start in Edinburgh, [edinburgh.gov.uk](https://www.edinburgh.gov.uk/news/article/14514/tour-de-france-route-revealed-as-edinburgh-prepares-for-historic-grand-d%C3%A9part).
- `dag-van-de-mantelzorger`, 23 juni: Vlaamse datum volgens [VRT, 2024](https://www.vrt.be/vrtnws/nl/2024/06/23/dag-van-de-mantelzorg-mantelzorgplan-vivel/).

## Ongewijzigd en gecontroleerd

De overige vaste dagen zijn internationale dagen of wettelijke feestdagen waarvan de datum niet verschuift.
Hemelvaart (6 mei) en Pinksteren (16 mei) volgen uit Pasen en staan op robinfo.oma.be.
Vaderdag (13 juni) is de tweede zondag van juni, het winteruur (31 oktober) de laatste zondag van oktober.
De 10-daagse van de veerkracht loopt elk jaar van 1 tot 10 oktober ([Stad Gent](https://stad.gent/nl/samenleven-welzijn-gezondheid/nieuws-evenementen/10-daagse-van-de-veerkracht)).

## Maandintro's

De intro van april noemde Pasen, die van juni Hemelvaart.
Pasen valt in 2027 in maart en Hemelvaart in mei, dus beide zinnen zijn jaaronafhankelijk herschreven.
Een test bewaakt dat geen intro nog een feestdag noemt die rond Pasen schuift.
