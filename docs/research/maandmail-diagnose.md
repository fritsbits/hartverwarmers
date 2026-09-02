# Maandmail: diagnose van twee problemen

Onderzocht op 1 september 2026, naar aanleiding van twee observaties in een
verzonden mail en in het Resend-logboek:

1. In het blok "Thema's om alvast in te plannen" staat bij elk thema
   "0 fiches beschikbaar".
2. In Resend draagt de mail al dagenlang dezelfde onderwerpregel:
   "Baarddag komt eraan, verse ideeën liggen klaar".

Beide kloppen. Ze hebben verschillende oorzaken en worden in twee aparte
threads opgelost. Dit document is de gedeelde context.

## Hoe de maandmail in elkaar zit

| Onderdeel | Bestand |
|---|---|
| Inhoud samenstellen | `app/Services/MonthlyDigest/Composer.php` |
| Datacontainer + personalisatie | `app/Services/MonthlyDigest/Payload.php` |
| Onderwerpregel + preview text | `app/Notifications/MonthlyDigestNotification.php` |
| Template | `resources/views/emails/monthly-digest.blade.php` |
| Verzendcommando | `app/Console/Commands/SendMonthlyCohortNewsletter.php` |
| Planning | `routes/console.php`, dagelijks om 10:30 |
| Tests | `tests/Feature/Notifications/MonthlyDigestNotificationTest.php`, `tests/Feature/Commands/SendMonthlyCohortNewsletterTest.php`, `tests/Feature/MonthlyDigest/ComposerTest.php` |

De cadans is persoonlijk, niet collectief. `User::qualifiesForMonthlyDigestToday()`
laat een gebruiker mee als het aantal dagen sinds registratie deelbaar is door
30. Het commando draait elke dag en bedient dus elke dag een ander cohort. Die
eigenschap verklaart probleem 2, en is makkelijk over het hoofd te zien: per
gebruiker klopt het gedrag, in het Resend-overzicht ziet het er kapot uit.

De inhoud zelf is niet gepersonaliseerd. `Composer::compose()` draait één keer
per dag en levert voor iedereen dezelfde thema's, hetzelfde diamantje en
dezelfde recente fiches. `Payload::forUser()` haalt er alleen het diamantje uit
dat de gebruiker vorige keer al kreeg, en een productupdate die hij al zag.

## Probleem 1: thema's zonder fiches

De koppeltabel `fiche_theme` wordt gevuld vanuit een databestand, niet vanuit de
applicatie. `ImportThemesCommand` (regel 79 tot 104) leest per thema een
`fiche_slugs`-array uit `database/seeders/data/themes.json` en doet
`$theme->fiches()->sync(...)`. Dat is de enige schrijver.

En dat bestand is half ingevuld:

```
themes.json: 96 thema's, waarvan 25 met fiche_slugs.
De overige 71 hebben de sleutel niet eens.
```

Die 25 zijn precies de thema's die in de database koppelingen hebben. Alle 106
rijen dateren van 12 mei, de dag van de import. Sindsdien is er niets
bijgekomen, want er is geen tweede ronde `fiche_slugs` geschreven.

De thema's zelf kwamen in twee importrondes binnen, en de backfill draaide
ertussenin:

| Ronde | Ids | Aangemaakt | Thema's met fiches |
|---|---|---|---|
| 1, dagen van mei tot augustus | 1 tot 31 | 12 mei 11:32 | 25 |
| 2, dagen van september tot januari | 100 tot 165 | 12 mei 16:07 | 0 |

De backfill van 12 mei dekte alleen ronde 1. Alles vanaf september heeft dus
per constructie nul fiches. De vijf thema's uit
de screenshot (Terug naar school, Baarddag, Duurzame dinsdag, Week van de
geletterdheid, Open monumentendag) staan allemaal op nul, ook als je de
`published`-filter weglaat.

Let op bij het narekenen: de lokale database is een kopie van 11 mei, de
nieuwste fiche erin dateert van 11 mei 13:50. De aantallen bij de thema's van
mei tot augustus liggen in productie hoger. De nullen vanaf september niet,
want er is geen code die de pivot vult.

Verwar `Theme` niet met `Tag` van het type `theme`. Dat zijn twee assen. De
fichewizard hangt tags als "Muziek" en "Natuur & dieren" aan een fiche, nooit
een kalenderdag. De AI in de wizard vult `suggested_themes`, en dat gaat naar
`tags`, niet naar `fiche_theme`.

## Twee bevindingen ernaast, niet opgelost

`routes/console.php:11` plant elke nacht `themes:rollover`. Dat commando
bestaat niet meer. De enige versie staat in `app/Console/Commands_legacy/`, een
map die Laravel niet doorzoekt, en die code roept methodes aan die niet meer op
`Theme` staan. De taak faalt dus elke nacht in stilte.

`themes.json` bevat alleen `occurrences_2026`. Zonder een blok voor 2027 valt
het themablok in de maandmail vanaf januari leeg. De import is idempotent, dus
een blok toevoegen en opnieuw draaien volstaat.

## Probleem 2: weken dezelfde onderwerpregel

`MonthlyDigestNotification::featuredTheme()` (regel 113) kiest het thema met de
**kortste titel**. De kandidatenlijst komt uit `Composer::compose()` en is
begrensd op de **eerste 5 gelegenheden in een venster van 30 dagen**.

Die lijst verschuift nauwelijks van dag tot dag. Baarddag (8 tekens) komt op
3 augustus het venster binnen en wint elke dag tot 2 september, ongeveer een
maand lang. "Herfst" (6 tekens) zou winnen, maar valt als zesde buiten de
`limit(5)`.

De comment boven de methode zegt dat de inbox nooit twee keer dezelfde regel
toont. Per gebruiker klopt dat, want die krijgt om de 30 dagen post. Over alle
cohorten heen gaat elke dag dezelfde regel de deur uit.

Twee dingen maken het erger dan alleen saai:

- De onderwerpregel belooft "verse ideeën liggen klaar" voor een thema met nul
  fiches. Die belofte is bij elke verzending onwaar, en blijft onwaar tot
  probleem 1 opgelost is.
- Kortste titel is ook redactioneel een zwakke keuze. Baarddag is geen kop voor
  een nieuwsbrief aan animatoren in woonzorgcentra.

## Handoff prompt voor de tweede thread

```
De onderwerpregel van de maandmail (MonthlyDigestNotification) staat al weken
op "Baarddag komt eraan, verse ideeën liggen klaar". Los dat op.

Lees eerst docs/research/maandmail-diagnose.md voor de volledige diagnose.
Samengevat: featuredTheme() kiest de kortste titel uit de eerste 5 gelegenheden
in een venster van 30 dagen, en die verzameling verschuift bijna niet. De mail
gaat elke dag naar een ander cohort (persoonlijke 30-dagencadans), dus dezelfde
regel gaat een maand lang de deur uit. Bovendien belooft de regel "verse ideeën
liggen klaar" voor een thema dat nul fiches heeft.

Richting die ik in gedachten heb, denk mee en spreek tegen als er beter is:

- Kies alleen een thema met fiches_count > 0 als kop. Heeft geen enkel
  aankomend thema fiches, val dan terug op het diamantje of het aantal nieuwe
  fiches, zoals subjectLine() nu al doet.
- Kies binnen die groep op redactionele waarde in plaats van op titellengte.
  Meeste fiches, of dichtstbijzijnde datum.
- Voeg rotatie toe zodat cohorten van opeenvolgende dagen niet allemaal
  dezelfde regel krijgen. De cycle van de gebruiker of het gebruikers-id is
  beschikbaar in de notificatie.
- Denk na of de kandidatenlijst wel op limit(5) begrensd moet blijven voor de
  keuze van de kop. Nu valt "Herfst" eruit terwijl het een betere kop is.

De preview text (previewText()) leunt op dezelfde featuredTheme(). Controleer
dat die mee blijft kloppen.

Werk testgedreven, tests staan in tests/Feature/Notifications/
MonthlyDigestNotificationTest.php. Raak de weergave van "0 fiches beschikbaar"
in de template niet aan, dat is probleem 1 en loopt in een andere thread.
```

## Stand van zaken, 2 september 2026

De koppelingen zijn aangevuld. `themes.json` gaat van 25 naar 82 thema's met
`fiche_slugs`, samen 328 koppelingen. De matching gebeurde met de hand tegen een
verse export van productie (462 gepubliceerde fiches), maand per maand, niet met
het AI-commando: de Anthropic-sleutel had geen krediet meer.

`themes:suggest-fiches` blijft in de repo staan als de herhaalbare weg voor
later, bijvoorbeeld wanneer er nieuwe fiches bijkomen die nog nergens aan hangen.

Veertien thema's blijven bewust leeg, omdat er in de catalogus niets ligt dat
een animator op die dag echt zou bovenhalen: bloeddonordag, baarddag, balletdag,
coming-out-day, dag-van-de-jeugdbeweging, dag-van-de-mensenrechten,
dag-van-de-onschuldige-kinderen, dag-voor-de-uitbanning-van-geweld-tegen-vrouwen,
e-mailloze-vrijdag, kinderrechtendag, transgender-gedenkdag, wereld-diabetes-dag,
wereld-dovendag en winteruur. Dat is een inhoudelijk gat, geen bug. Zolang die
dagen in het venster van 30 dagen vallen, toont de mail er "0 fiches
beschikbaar" bij.
