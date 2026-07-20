---
name: product-update
description: "Use when the user asks to write a product update / productupdate / 'wat is er nieuw'-bericht about a recently shipped feature. Drafts a compact Flemish JSON update for resources/content/updates/, asks at most 2-3 clarifying questions, and shows the draft for a yes/no before writing."
---

# Product update schrijven

Eén product update = één JSON-bestand in `resources/content/updates/`. Het verschijnt
automatisch op drie plaatsen:

- **de overzichtspagina** `/wat-is-er-nieuw` — datumstempel, titel en de `body`
  (afgekapt na drie regels)
- **een eigen detailpagina** `/wat-is-er-nieuw/{uid}` — de volledige `content`
- **de maandelijkse digest-mail** — éénmalig per ontvanger, nieuwste update,
  max 60 dagen oud, max 1 per mail; de mail toont enkel `title` + `body` + `link`

Publiceren = committen + deployen. Er is geen redactie-stap achteraf: wat hier
geschreven wordt, gaat zo naar duizenden animatoren.

## Workflow

1. **Verzamel eerst zelf context — vraag niets wat je zelf kan vinden.**
   Bekijk `git log --oneline -15` en de diff/bestanden van de feature. Bezoek of lees
   de relevante pagina/template om te zien wat de gebruiker er echt van merkt.
2. **Schrijf een eerste draft** volgens de schrijfregels hieronder.
3. **Stel maximaal 2-3 korte vragen**, alleen over wat je niet kan afleiden.
   Goede vragen: "Wat was de aanleiding?", "Is er een nuance die vermeld moet worden?",
   "Naar welke pagina moet de link wijzen?". Nul vragen is prima als de context volstaat.
4. **Toon de definitieve JSON** en vraag een simpele ja/nee. Pas aan tot akkoord.
5. **Schrijf het bestand** en herinner eraan dat het live gaat bij de volgende
   commit + deploy.

## Bestandsformaat

Bestandsnaam en `uid` zijn identiek: `YYYY-MM-korte-nederlandse-slug` (jaar-maand van
publicatie). `published_at` is de dag van schrijven.

    {
        "uid": "2026-07-themakalender-afdrukken",
        "published_at": "2026-07-16",
        "title": "Druk de themakalender af",
        "body": "Twee tot vier zinnen. Concreet en warm.",
        "content": "## Zo druk je het af\n\nGa naar de themakalender…\n\n- Puntje een\n- Puntje twee\n",
        "image": {
            "src": "/images/updates/themakalender-print.webp",
            "alt": "Het afgedrukte A3-blad aan een prikbord"
        },
        "link": { "url": "/themas", "label": "Bekijk de themakalender" }
    }

`link` is optioneel maar bijna altijd gewenst; `url` is een relatief pad.

`content` is optioneel maar zonder is de detailpagina bijna leeg — schrijf het dus
bijna altijd. Het is **markdown**, enkel `##`/`###`, alinea's, lijstjes, `**vet**` en
links. Ingebedde HTML wordt eruit gestript. De `body` staat al bovenaan de
detailpagina als inleiding: **herhaal die niet** in `content`, ga verder waar de body
stopt.

`image` is optioneel: één schermafbeelding, in `public/images/updates/`, bij voorkeur
`.webp` en ongeveer 4:3. Hij staat groot op de detailpagina en klein op het overzicht.
`alt` beschrijft wat er te zien is (geen "schermafbeelding van").

## Schrijfregels

- **Vlaams**, niet Hollands: geen "hartstikke", geen "gewoon" als bijwoord, geen
  "lekker" buiten eten, geen "super" als versterker. Wel: woonzorgcentrum, bewoners,
  begeleidster, animatoren.
- **Titel**: gebiedende wijs waar het kan ("Druk … af", "Ontdek …", "Vind …"),
  maximaal ± 6 woorden, geen punt.
- **Body**: 2 tot 4 zinnen. Eerste zin = wat je er als animator aan hebt, niet wat wij
  gebouwd hebben. Geen feature-jargon ("PDF-conversiepijplijn"), wel het effect
  ("elk bestand opent voor iedereen").
- **Content**: het verhaal achter de body. Drie tot vier korte `##`-secties werkt goed:
  hoe je het gebruikt, wat het je oplevert, wat handig is om te weten. Schrijf alleen
  wat je kan nakijken — klopt die knop, staat die echt daar? Verzin geen stappen.
  Kopjes in de gebiedende wijs of als vraag, nooit als feature-naam
  ("Zo druk je het af", niet "Printfunctionaliteit").
- **Toon**: een warme collega die iets handigs komt tonen. Direct en hoopvol, nooit
  academisch of verkoperig.
- **Linklabel**: gebiedende wijs ("Bekijk de themakalender", "Probeer het uit").

## Voorbeelden

Goed — kort, voordeel eerst, gebiedende wijs:

    {
        "uid": "2026-07-themakalender-afdrukken",
        "published_at": "2026-07-16",
        "title": "Druk de themakalender af",
        "body": "Hang de thema's van de maand op in je bureau of aan het prikbord. Elke maand van de themakalender kan je nu afdrukken op A3, klaar om op te hangen en samen met collega's te plannen.",
        "link": { "url": "/themas", "label": "Bekijk de themakalender" }
    }

Slecht — wij-perspectief, jargon, Hollands:

    {
        "title": "Print-functionaliteit toegevoegd",
        "body": "We hebben een super handige feature gebouwd waarmee je gewoon een A3-print kan genereren van de kalenderweergave."
    }
