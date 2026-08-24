# Briefing: de AI-kwaliteitsscores clusteren op een handvol waarden

Onderzoeksopdracht voor een aparte thread. Vastgesteld op 24 augustus 2026 tijdens het bouwen van de OKR "Inhoudelijke kwaliteit".

## Wat we zien

`FicheQualityAgent` geeft elke fiche twee scores van 0–100: `quality_score` (inhoudelijk) en `presentation_score`. In theorie 101 mogelijke waarden. In de praktijk gebruikt hij er een tiental.

Verdeling van `quality_score` over de 400 gepubliceerde fiches (lokale kopie, augustus 2026):

| score | 18 | 22 | 28 | 32 | 38 | 42 | 52 | 62 | 72 | 74 | 78 | 82 |
|-------|----|----|----|----|----|----|----|----|----|----|----|----|
| fiches| 26 | 42 | 31 | 19 | 49 | 28 | 39 | 46 | 46 | 10 | 16 | 16 |

Negen waarden dekken 80% van de bibliotheek. Alle andere scores komen één tot vier keer voor. De hoogste score ooit is 93.

Drie dingen vallen op:

1. **De waarden liggen op een rooster van tientallen ± 2.** 18, 22, 28, 32, 38, 42, 52, 62, 72, 78, 82. Ronde getallen (60, 65, 70, 75) komen vrijwel niet voor: 60 één keer, 68 één keer, 70 nooit.
2. **Er zitten gaten van tien punten.** Tussen 52 en 62 ligt niets, tussen 62 en 72 ook niet. Een fiche verbetert dus niet geleidelijk — ze springt een niveau of blijft staan.
3. **`presentation_score` clustert op exact hetzelfde rooster** (12, 18, 22, 28, 32, 38, 42, 52, 62), terwijl die dimensie een compleet andere rubriek gebruikt.

## Hypothese van de gebruiker

De inhoudelijke score wordt bepaald per DIAMANT-aspect, en per aspect is het effectief ja/nee zonder gradaties — waardoor de totaalscore in stappen springt.

**Tegenbewijs om mee te rekenen:** `presentation_score` clustert identiek en heeft niets met DIAMANT-aspecten te maken. Die rubriek beoordeelt titel, omschrijving, uitvoerbaarheid en volledigheid. Als beide dimensies op hetzelfde rooster landen, ligt de oorzaak waarschijnlijk niet in de rubriek maar in hoe het model getallen kiest. De hypothese verdient dus een bredere vraagstelling dan alleen de DIAMANT-aspecten.

## Waarom het uitmaakt

De OKR "Inhoudelijke kwaliteit" meet het aandeel fiches met `quality_score >= 70`. Door het rooster betekent die drempel in de praktijk "≥ 72". Dat is op zich stabiel — de drempel valt in een gat, dus er is geen geharrewar met randgevallen — maar het heeft twee gevolgen:

- **46 fiches staan op 62**, één niveau onder de drempel. Tillen die één stap, dan gaat de KR van 24% naar ongeveer 35%: precies het doel. De hele OKR hangt aan één sprong van één groep.
- **Echte verbetering blijft onzichtbaar** zolang ze binnen een niveau valt. Een fiche die merkbaar beter wordt maar niet één rooster­stap haalt, verandert niets aan de score en dus niets aan de KR.

## Wat uitzoeken

1. Reproduceer het patroon: laat dezelfde fiche meermaals scoren (identieke input) en kijk of de score stabiel is of rondspringt. Doe dat ook voor een fiche die je licht verbetert — springt hij een niveau of blijft hij staan?
2. Test of het aan de prompt ligt. De huidige rubriek in `app/Ai/Agents/FicheQualityAgent.php` geeft banden (0-30 zwak, 31-60 redelijk, 61-80 goed, 81-100 uitstekend). Een model dat in banden denkt, kiest mogelijk het midden of de rand van een band. Probeer varianten: expliciet vragen om een getal dat geen veelvoud van 10 is, of de score opbouwen uit deelscores per criterium die je zelf optelt.
3. Overweeg gestructureerde deelscores. In plaats van één getal per dimensie: laat de agent per criterium scoren (DIAMANT-aansluiting, originaliteit, betekenis — en voor presentatie: titel, omschrijving, uitvoerbaarheid, volledigheid) en tel die in code op. Dat geeft meer gradaties, maakt zichtbaar *waar* een fiche zakt, en levert meteen bruikbare feedback voor de bijdrager.
4. Kijk of een ander model of een andere temperatuur het rooster doorbreekt. De agent draait nu op `claude-sonnet-4-6`.
5. Bepaal wat er met bestaande scores gebeurt als de rubriek verandert. 400 fiches zijn met de huidige rubriek gescoord; een nieuwe rubriek maakt die onvergelijkbaar en verschuift de OKR-baseline. Beslis vooraf of alles opnieuw beoordeeld wordt en wat dat met de KR doet.

## Waar de code zit

- `app/Ai/Agents/FicheQualityAgent.php` — de rubriek en het output-schema.
- `app/Jobs/AssessFicheQuality.php` — bouwt de prompt op uit fiche, initiatief, tags, bestandsteksten en actieve DIAMANT-doelen; schrijft de scores weg.
- `app/Observers/FicheObserver.php` — dispatcht de beoordeling bij aanmaak en bij inhoudelijke wijziging (debounce van 10 minuten).
- `app/Console/Commands/AssessQuality.php` — handmatig (her)beoordelen.
- `app/Metrics/DiamantScoreShareMetric.php` — de KR die op de drempel van 70 steunt.

## Wat een goede uitkomst is

Een score die gradueel meebeweegt met echte verbetering, zodat een fiche die beter wordt ook zichtbaar beter scoort. En duidelijkheid of de bestaande 400 scores bruikbaar blijven of opnieuw moeten.
