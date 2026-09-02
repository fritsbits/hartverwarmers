# Themakalender: hoe we stil verval voorkomen

Plan opgesteld op 2 september 2026, als vervolg op
[`maandmail-diagnose.md`](maandmail-diagnose.md). Dat document legt uit wat er misging;
dit document zegt wat we eraan doen, in welke volgorde, en waarom niet alles opgelost
hoeft te worden met een waakhond.

De verkenning waaruit dit plan komt staat in
`.lavish/themakoppelingen-faallandschap.html`. Dat bestand staat lokaal en niet in git.

## Doel

Zorgen dat de koppelingen tussen fiches en kalenderthema's niet opnieuw maandenlang
kunnen wegrotten zonder dat iemand het merkt, en de stukken die niemand kan automatiseren
op tijd om aandacht laten vragen. De aanleiding was een maandmail die bij elk aankomend
thema "0 fiches beschikbaar" zette, maandenlang, ontdekt door toevallig naar een e-mail te
kijken.

Het plan valt in twee delen uiteen. Zeven van de acht manieren waarop dit stukgaat zijn
ontbrekende schakels in de pijplijn en verdwijnen door te bouwen. Precies één vraagt een
mens die oordeelt welke fiche bij welke dag hoort, en die krijgt een alarm, geen rapport.

## Meting van 2 september 2026, lokale databank

Hermeet dit voor je begint. Een parallelle thread wijzigde `themes.json` drie keer tijdens
de verkenning (`8784243`, `fdf8a46`, `d4c8893`) en toen schoven deze getallen al een keer.

| Wat | Waarde |
|---|---|
| Thema's in `themes.json` | 85 |
| Thema's met een `fiche_slugs`-sleutel | 78 |
| Thema's zonder die sleutel | 7: `bloeddonordag`, `baarddag`, `dag-van-de-mensenrechten`, `dag-van-de-onschuldige-kinderen`, `wereld-diabetes-dag`, `wereld-dovendag`, `winteruur` |
| Koppelingen die het bestand voorschrijft | 321, waarvan er 320 oplosbaar zijn |
| Rijen in `fiche_theme` | 322 |
| Gepubliceerde fiches | 470, waarvan er 261 aan geen enkel kalenderthema hangen |
| Horizon van de occurrences | 2026-12-31 |
| Thema's in het venster van 30 dagen dat de maandmail toont | 13 |

## De acht faalvormen

| # | Mechanisme | Ritme | Toestand vandaag |
|---|---|---|---|
| 1 | De wizard schrijft `Tag`-rijen van type `theme`, nooit `fiche_theme`, dus een nieuwe fiche bereikt de kalender nooit | continu, ongeveer twee gepubliceerde fiches per week | actief, en zo bedoeld |
| 2 | `themes.json` bevat alleen `occurrences_2026`; het venster krimpt vanaf midden december en staat op 1 januari 2027 op nul | jaarlijks | actief, over 121 dagen |
| 3 | `routes/console.php:11` plant `themes:rollover`, dat in `app/Console/Commands_legacy/` staat en niet geregistreerd is | 365 stille mislukkingen per jaar | actief |
| 4 | Een thema dat zonder `fiche_slugs` binnenkomt krijgt geen tweede ronde: `themes:import` slaat het over, en `themes:suggest-fiches` slaat alles over dat de sleutel al heeft | bij elke uitbreiding van de kalender | actief voor 7 thema's |
| 5 | Een slug in het bestand wijst naar een fiche die in de prullenbak zit of niet gepubliceerd is; de import waarschuwt op één console-regel en gaat verder | sluipend, groeit met de leeftijd van de catalogus | actief: `installeer-een-massagestoel` bij `dag-van-de-verzorgenden-en-zorgkundigen`, geschrapt op 2026-03-19 |
| 6 | `themes.json` aanpassen en committen verandert niets aan de databank; iemand moet de import draaien | bij elke wijziging | actief en bevestigd, zie hieronder |
| 7 | `resources/views/emails/monthly-digest.blade.php:69` drukt "0 fiches beschikbaar" af voor elk thema in het venster, voor alle lezers | bij elke verzending | actief voor de 7 sleutelloze thema's |
| 8 | Elke waakhond die we toevoegen is zelf een gepland commando dat kan verdwijnen zoals nummer 3 | bij introductie | nog niet van toepassing |

### Faalvorm 6 is nagekeken en bevestigd

`themes:import` staat **niet** in het Forge-deployscript. Dat script draait
`composer install`, `optimize`, `storage:link`, `migrate --force`,
`db:seed --class=OkrSeeder --force` en `npm run build`. Push-to-deploy staat aan, dus code
landt vanzelf, maar een wijziging aan `themes.json` blijft zonder gevolg tot iemand met de
hand `php artisan themes:import` draait via Forge, site, Commands.

Twee valkuilen in dat paneel: de uitvoer verschijnt pas na een paginaherlaad, en soms
toont Forge "No output available" of een `cat: ... No such file` terwijl het commando wel
gelukt is. Controleer het resultaat op `/themas/print?maand=JJJJ-MM` in plaats van op het
uitvoerpaneel te vertrouwen.

## Beslissingen

| Beslissing | Keuze | Gevolg |
|---|---|---|
| Eigenaarschap van `fiche_theme` | `themes.json` blijft de enige bron. De applicatie schrijft nooit in de koppeltabel. | De wizard kan niet koppelen bij het aanmaken. Faalvorm 1 blijft dus bestaan en moet gemeld worden in plaats van opgelost. De git-diff blijft het reviewmoment. |
| Een bewust leeg thema markeren | `fiche_slugs: []` betekent bekeken, er hoort niets bij. Een ontbrekende sleutel betekent nog te doen. | "Thema's zonder sleutel" wordt een controle die op nul hoort te staan. Geen reden-veld in het bestand; die redenering staat in `maandmail-diagnose.md`. |
| `themes:rollover` | Opruimen. | De occurrence-generator neemt de rol over en schrijft in het bestand in plaats van in de databank, wat past bij het eigenaarschap hierboven. |
| Alarmering | **Geen periodiek rapport.** Alleen een mail wanneer er echt iets mis is. | De drempels moeten één tot twee maanden voor het pijn doet aanslaan, zodat er nog tijd is om thema's en fiches toe te voegen. De stille bevestiging staat op `/admin/gezondheid`, waar ze geen aandacht kost. |
| Deploystap | Geparkeerd. | Het gat is bevestigd, maar de keuze was "later, eerst deze ronde met de hand". Zie ronde 3.4. |

De alarmeringskeuze is tijdens de review herzien. Een eerdere versie stelde een
maandelijkse statusmail voor. Een mail die meestal zegt dat er niets te melden valt, wordt
weggeklikt, en het volgende echte signaal gaat mee de prullenbak in.

## Ronde 1: de bloedingen stelpen

Eén commit. Drie faalvormen zijn nu actief, en elk later signaal is onbetrouwbaar zolang
ze dat blijven.

### 1.1 Een test die nakijkt of geplande commando's echt bestaan

`tests/Feature/Infrastructure/ScheduledCommandsTest.php` controleert nu of de tekst
`themes:rollover` in de planning voorkomt. Die test staat groen terwijl het commando niet
bestaat, en houdt het lijk dus in leven.

Vervang dat door een test die elke geplande taak nagaat en controleert of het commando in
het artisan-register zit:

- Lees `app(Schedule::class)->events()`.
- Elke `$event->command` ziet eruit als `'/usr/bin/php' 'artisan' naam --optie=x`. Neem het
  woord na `artisan` en haal de aanhalingstekens weg.
- Controleer voor elk `array_key_exists($naam, Artisan::all())`, en noem alle missers in de
  foutmelding.
- Behoud de bestaande controles per commando voor de taken die er nog toe doen, minus
  `themes:rollover`.

Deze test moet falen vóór 1.2 en slagen erna. Draai hem in die volgorde, zodat je weet dat
hij werkt.

### 1.2 `themes:rollover` opruimen

- Haal de regel `Schedule::command('themes:rollover')` uit `routes/console.php`.
- Verwijder `app/Console/Commands_legacy/RolloverThemes.php`.
- Verwijder `test_theme_rollover_is_scheduled`.

In `app/Console/Commands_legacy/` staan ook `CleanupThemes.php` en `ImportThemes.php`, even
onbereikbaar. **Vraag het na voor je die twee weggooit.** Ze kunnen bewaard zijn als
referentie voor het datamodel van voor de occurrences.

### 1.3 De dode slug weghalen

Haal `installeer-een-massagestoel` uit de `fiche_slugs` van
`dag-van-de-verzorgenden-en-zorgkundigen` in `database/seeders/data/themes.json`. Die fiche
is op 2026-03-19 in de prullenbak beland. Haal de fiche zelf niet terug: dat is een
inhoudelijke keuze en geen herstelling.

### 1.4 De import stopt op een onbekende slug

In `ImportThemesCommand` levert een onoplosbare slug nu een `$this->warn()` en een teller
op, waarna de import gewoon doorgaat en commit. Verander dat:

- Verzamel de onbekende slugs per thema tijdens het synchroniseren.
- Zijn er die, draai de transactie terug en geef `self::FAILURE` terug, met elke onbekende
  slug en het bijbehorende thema erbij.
- Voeg `--allow-unknown-slugs` toe voor het oude gedrag.

Waarom die ontsnappingsklep: de lokale databank is niet altijd gelijk aan productie. Een
slug die op productie klopt kan lokaal falen. Streng is het juiste standaardgedrag, omdat
de import een kandidaat is voor de deploystap en een slechte slug een release hoort af te
breken. De vlag is er voor lokale runs tegen een verouderde kopie.

Tests: een onbekende slug laat het commando falen en `fiche_theme` onaangeroerd; hetzelfde
bestand met `--allow-unknown-slugs` slaagt en koppelt de oplosbare slugs.

### 1.5 De afspraak over lege arrays toepassen

Geef de 7 sleutelloze thema's `"fiche_slugs": []`.

Daar is geen codewijziging voor nodig. `ImportThemesCommand` behandelt een lege array al
als `sync([])`, en `SuggestThemeFiches` slaat elk thema over dat de sleutel al heeft, dus
een lege array houdt een thema vanzelf buiten toekomstige AI-rondes.

Voeg een test toe over het echte bestand `database/seeders/data/themes.json`: elk thema
heeft een `fiche_slugs`-sleutel. Dat is wat faalvorm 4 definitief doodt, want een thema dat
zonder beslissing wordt toegevoegd laat vanaf dan de testsuite falen in plaats van
maandenlang onopgemerkt te blijven liggen.

### 1.6 De databank gelijkzetten

Draai `php artisan themes:import`. Nooit `migrate:fresh`, `migrate:reset` of `db:wipe`: de
lokale databank bevat handmatig ingevoerde inhoud zonder backup.

Controleer daarna dat het aantal rijen in `fiche_theme` gelijk is aan het aantal oplosbare
slugs in het bestand.

## Ronde 2: de klif wegnemen

Eén commit. De enige harde deadline in het hele landschap. Het venster van de maandmail
krimpt door december heen en staat op 1 januari op nul, en die krimp is gecamoufleerd
omdat december sowieso een dunnere maand is: op 1 december staan er nog 14 thema's in het
venster, op de tiende 9, op de twintigste 5, op de eenendertigste 1.

### 2.1 `themes:extend-occurrences --year=JJJJ [--dry-run]`

Een nieuw commando dat `occurrences_JJJJ` in `themes.json` schrijft. Het raakt de databank
nooit aan; `themes:import` past het toe, net als bij `themes:suggest-fiches`.

Bereken per thema de gelegenheid van het doeljaar uit `recurrence_rule`, met de meest
recente bestaande occurrence als referentie:

- `fixed`, 79 thema's: dezelfde maand en dag. Behoud de duur, zodat een gelegenheid met een
  `end_date` evenveel dagen blijft beslaan.
- `nth_weekday`, 2 thema's: `vaderdag` met "2nd Sunday of June" en `winteruur` met "Last
  Sunday of October". Ontleed `recurrence_detail`. Alleen deze twee vormen bestaan; faal
  luid op elke andere formulering in plaats van te gokken.
- `easter`, 2 thema's: `onze-lieve-heer-hemelvaart` met "Easter + 39 days" en `pinksteren`
  met "Easter + 49 days". Bereken paaszondag voor het doeljaar met het anonieme
  Gregoriaanse algoritme en tel de offset uit `recurrence_detail` erbij op.
- `variable_annual`, 1 thema (`ronde-van-frankrijk`) en `school_calendar`, 1 thema
  (`herfstvakantie`): niet berekenbaar. Sla ze over en noem ze aan het eind als handwerk,
  met de datums van het vorige jaar erbij als houvast.

Schrijf met dezelfde encoder als `SuggestThemeFiches` (`JSON_PRETTY_PRINT |
JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`) zodat de diff leesbaar blijft. Overschrijf
een bestaand `occurrences_JJJJ`-blok nooit zonder `--force`.

Tests: elk regeltype levert de juiste datum voor een bekend jaar; een schrikkeljaar met 29
februari; een onontleedbare `recurrence_detail` laat het commando falen; de twee handmatige
thema's worden gemeld en niet verzonnen.

Na de run kijk je de diff na en vul je de twee handmatige datums zelf in.

### 2.2 De maandmail toont geen lege thema's meer

Sluit in `App\Services\MonthlyDigest\Composer::compose()` de gelegenheden uit waarvan het
thema nul gepubliceerde fiches heeft, zowel uit de `themes`-verzameling als uit
`upcomingThemeCount`. De nul wordt daarmee een intern signaal in plaats van iets dat elke
lezer ziet.

Kijk na of `MonthlyDigestNotification::featuredTheme()` en `previewText()` nog kloppen met
een vooraf gefilterde lijst. Commit `98096ec` maakte de onderwerpregel al afhankelijk van
een thema met fiches, dus dit hoort de kandidatenlijst te versmallen en niet de logica te
veranderen.

Tests: `ComposerTest` dekt de uitsluiting; `MonthlyDigestNotificationTest` dekt een venster
waarin elk thema leeg is, zodat de `@if ($payload->themes->isNotEmpty())` in de template
ook echt geraakt wordt.

## Ronde 3: alleen alarm slaan als er iets mis is

Eén commit. Begin hier pas als ronde 1 en 2 klaar zijn, anders melden de eerste alarmen
vooral dingen die je toch al aan het herstellen was.

### 3.1 `themes:health-check`

Een nieuw commando, wekelijks gepland op maandag om 09:00 in Europe/Brussels. Het meet de
voorwaarden en mailt de beheerder **alleen** bij een overschrijding, in de vorm van
`CheckQueueHealth`: een `Mail::raw` naar het beheerdersadres, plus een cachesleutel per
voorwaarde zodat dezelfde klacht niet elke week opnieuw binnenkomt.

Afkoelperiode: 14 dagen per voorwaarde. Deze dingen bewegen traag, en een wekelijkse
herhaling leert je de mail te negeren. Dat is nu net het falen dat dit hele plan probeert
te voorkomen.

Voorwaarden en drempels, in `config/themes.php`:

| Voorwaarde | Drempel | Waarom dit getal |
|---|---|---|
| Horizon van de occurrences | minder dan 60 dagen vooruit | Slaat rond 1 november aan voor het gat van 2027, zodat november en december overblijven om te handelen. Dat is de gevraagde aanlooptijd van één tot twee maanden. |
| Aankomende thema's met koppelingen in het bestand maar nul gepubliceerde fiches, komende 60 dagen | 1 of meer | Dat wijst op weggerotte koppelingen, niet op een inhoudelijk gat. Een thema met `fiche_slugs: []` is bewust leeg en telt nooit mee. |
| Bestand tegenover databank | elk verschil tussen de oplosbare slugs in het bestand en de rijen in `fiche_theme` | Vangt een import die nooit gedraaid is. Zolang de deploystap geparkeerd blijft, is dit de enige bewaking op faalvorm 6. |
| Gepubliceerde fiches aangemaakt na de datumstempel | meer dan 25 | Bij ongeveer 13 nieuwe fiches per maand komt 25 neer op zo'n twee maanden aangroei. |

Al de rest in het landschap wordt gedekt door een test of door een import die faalt, en
hoort hier niet nog eens overgedaan te worden.

### 3.2 De datumstempel

`themes.json` krijgt bovenaan een `fiche_match_watermark` met een ISO-datum: tot waar een
koppelronde gekeken heeft. Zonder die stempel is "fiches zonder thema" een getal dat nooit
op nul komt, want een groot deel van de catalogus hoort bij geen enkele kalenderdag. Zo'n
getal zegt niets en wordt genegeerd.

- `themes:suggest-fiches` zet hem op vandaag wanneer een ronde afgerond is.
- Hij is met de hand aan te passen, want de ronde van september is handmatig gebeurd.
- `themes:health-check` telt de gepubliceerde fiches die erna zijn aangemaakt.

Zet de beginwaarde op `2026-09-01`, de datum van de handmatige ronde.

### 3.3 Stille bevestiging op de adminpagina

`themes:health-check` schrijft zijn volledige momentopname en een tijdstempel naar de
cache. Voeg een blok toe aan `/admin/gezondheid` (`HealthController` plus
`resources/views/admin/health.blade.php`) met de vier getallen en het moment waarop de
controle laatst gedraaid heeft.

Dat is het antwoord op faalvorm 8 zonder periodieke mail. Sterft de controle zelf, dan
veroudert het tijdstempel op die pagina, en de test uit ronde 1 vangt een commando dat
helemaal ophoudt te bestaan.

### 3.4 De deploystap

Het gat is bevestigd: `themes:import` staat niet in het deployscript. Het voorstel is één
regel vóór `$ACTIVATE_RELEASE()`, naast de `OkrSeeder` die er al staat:

```
$FORGE_PHP artisan themes:import
```

**Deze keuze is geparkeerd.** Op 2 september is ze voorgelegd en was het antwoord "later,
eerst deze ronde met de hand". Zet de regel er dus niet bij zonder het opnieuw te vragen.

Zet `themes:prune-removed` er in geen geval bij. Dat commando verwijdert themarijen en is
er bewust op gebouwd om buiten de deploy te blijven, zie `d4c8893`.

## Randvoorwaarden

- Productie draait op Digital Ocean via Laravel Forge. Geen Laravel Cloud, geen
  `cloud.yaml`. Het deployscript staat in de Forge-UI.
- Nooit `migrate:fresh`, `migrate:reset` of `db:wipe`. De lokale databank bevat handmatig
  ingevoerde inhoud zonder backup.
- Geen schemawijziging op de gecureerde tabellen `fiches` en `users`. Dit plan heeft
  helemaal geen migratie nodig.
- Maak geen git branches zonder het te vragen.
- Eén commit per ronde. Code, tests en tekstaanpassingen gaan samen; geen aparte
  `test:`- of `fix:`-commits voor werk dat ernaast gecommit wordt.
- Draai `vendor/bin/pint --dirty --format agent` na PHP-wijzigingen.
- Tests draaien met `php artisan test --parallel --compact`. Sluis testuitvoer nooit door
  `tail` of `head`, schrijf naar een bestand.

## Wanneer het af is

Ronde 1 is klaar als de planningstest faalt vóór de opruiming en slaagt erna, de import
faalt op de dode slug en slaagt zodra die weg is, elk thema in het bestand een
`fiche_slugs`-sleutel heeft, en `fiche_theme` precies zoveel rijen telt als het bestand
voorschrijft.

Ronde 2 is klaar als `themes:extend-occurrences --year=2027` een diff oplevert die je kunt
nalezen, de twee handmatige thema's gemeld worden in plaats van verzonnen, en geen enkele
test van de maandmail nog een thema met nul fiches rendert.

Ronde 3 is klaar als `themes:health-check` niets verstuurt bij gezonde data, precies één
mail stuurt wanneer de horizon in een test onder 60 dagen zakt, en daarna 14 dagen zwijgt.
