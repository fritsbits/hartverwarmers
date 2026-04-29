# Het DIAMANT-model

DIAMANT is het pedagogisch kader achter Hartverwarmers — zeven doelen voor zinvolle dagbesteding in woonzorgcentra. Niet als checklist, maar als spiegel: helpen begeleidsters scherper kijken naar wat ze doen en waarom.

## Herkomst

Het kader is mee uitgewerkt door **Maite Mallentjer**, pedagoog dagbesteding (auteursvermelding bij elke facet). Het is geen academisch model dat van bovenaf opgelegd wordt; het vertrekt vanuit de praktijk in Vlaamse woonzorgcentra en geeft taal aan kwaliteit van betekenisvolle activiteiten.

## De zeven facetten

| Letter | Keyword | Tagline |
|---|---|---|
| **D** | Doen | Zelf aan de slag |
| **I** | Inclusief | Iedereen erbij |
| **A** | Autonomie | De bewoner kiest |
| **M** | Mensgericht | Bij het levensverhaal |
| **A** | Anderen | Samen is meer |
| **N** | Normalisatie | Gewoon waar het kan |
| **T** | Talent | Krachten laten schitteren |

Elke letter staat voor één facet. Samen vormen ze geen volgorde of hiërarchie — een goede activiteit raakt vaak meerdere facetten tegelijk.

### D — Doen *(Zelf aan de slag)*

> Niet: we moeten de bewoner bezighouden. Maar: wat wil deze persoon vandaag ondernemen?

Bewoners doen zelf mee. Actief, niet alleen toeschouwer. De D staat voor activerend werken — bewoners zelf laten doen, in kleine stapjes, zodat ze vertrouwen opbouwen en het plezier van het bezig zijn zelf ervaren.

**Kernvraag:** Doen je bewoners vandaag iets dat hen energie geeft — of vullen ze de dag zonder het zelf te merken?

### I — Inclusief *(Iedereen erbij)*

> Niet: deze bewoner kan niet meedoen. Maar: hoe kan deze persoon op zijn manier deelnemen?

Iedereen hoort erbij. Niemand valt buiten de boot. Inclusief werken betekent activiteiten zo ontwerpen dat ze toegankelijk zijn voor alle bewoners, met oog voor diversiteit in cognitie, mobiliteit en achtergrond.

**Kernvraag:** Wie zit er niet aan tafel — en heb je je afgevraagd waarom?

### A — Autonomie *(De bewoner kiest)*

> Niet: wij weten wat het beste is. Maar: wat kiest deze persoon zelf?

Bewoners behouden regie. Ze kiezen zelf hoe en wanneer. Ook in een zorgomgeving willen mensen regie houden — over wat ze doen, wanneer, en met wie.

**Kernvraag:** Hebben je bewoners vandaag iets gekozen — of is alles voor hen beslist?

### M — Mensgericht *(Bij het levensverhaal)*

> Niet: wat is het zorgprofiel? Maar: wie is deze mens?

Elke bewoner wordt gezien. Als persoon, niet als dossier. Een activiteit is pas waardevol als ze aansluit bij wie de bewoner is, wat die heeft meegemaakt en waar die van houdt.

**Kernvraag:** Ken je het levensverhaal van je bewoners — of werk je vanuit het activiteitenprogramma?

### A — Anderen *(Samen is meer)*

> Niet: we zitten samen in de zaal. Maar: zijn we echt met elkaar verbonden?

Bewoners verbinden. Niet naast, maar met elkaar. Sociale verbinding en wederkerigheid met medebewoners, medewerkers, familie en buitenwereld — echte interactie, niet alleen fysieke nabijheid.

**Kernvraag:** Zitten je bewoners naast elkaar — of zijn ze echt met elkaar verbonden?

### N — Normalisatie *(Gewoon waar het kan)*

> Niet: het is tijd voor de activiteit. Maar: het is gewoon een mooie dag.

Het leven voelt vertrouwd. Huiselijk, gewoon, thuis. Aansluiten bij het gewone leven — niet schools. Activiteiten mogen niet kunstmatig aanvoelen.

**Kernvraag:** Voelt het hier aan als een thuis — of als een instelling met activiteiten?

### T — Talent *(Krachten laten schitteren)*

> Niet: wat kan deze bewoner nog? Maar: wat kan deze persoon ons brengen?

Bewoners schitteren. Vanuit wat ze wel kunnen. Vertrekken vanuit krachten in plaats van beperkingen — ontdekken wat iemand nog wél kan en meebrengt, en dat zichtbaar maken en waarderen.

**Kernvraag:** Zie je wat je bewoners nog wél kunnen — of focus je onbewust op wat niet meer lukt?

## Hoe DIAMANT in het platform leeft

Binnen Hartverwarmers is DIAMANT het structurerende kader voor de activiteiteninhoud:

- **Initiatieven** worden getagd met de DIAMANT-doelen die ze raken (slug: `doel-{facetSlug}`).
- Elk doel heeft een eigen pagina (`/doelen/{facetSlug}`) met praktijkverhalen, reflectievragen, aanpassingen en een tip.
- De **fiches** (uitgewerkte elaborations door begeleidsters) tonen welke DIAMANT-facetten ze aanspreken.

Het kader stuurt subtiel de manier waarop bewoners-bijdragen geëvalueerd worden (zie [evaluatiekader-bijdragen.md](evaluatiekader-bijdragen.md)) en hoe nieuwe content wordt aanbevolen.

## Implementatie in code

De DIAMANT-content is **config-driven** — niet database-gestuurd. Definities staan in `config/diamant.php` en zijn beschikbaar via `App\Services\DiamantService`. Dat houdt het kader stabiel en versie-gecontroleerd, terwijl initiatieven en fiches in de database leven.

Per facet is de volgende informatie gestructureerd: `letter`, `keyword`, `slug`, `tagline`, `subtitle`, `quote`, `description`, `author_name`, `challenges[]`, `core_question`, `contrast_positive`, `contrast_negative`, `practice_examples[]`, `reflection_questions[]`, `tip_title`/`tip_text`, `adaptations[]`, en `related_facets[]`.

---

*Bron: `config/diamant.php` (auteur: Maite Mallentjer, pedagoog dagbesteding).*
