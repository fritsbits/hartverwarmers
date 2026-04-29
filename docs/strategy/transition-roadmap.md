# Transition Roadmap — Hartverwarmers 2026

> 🔭 **Transition by Design** is een methode om drie schaalniveaus van een project te definiëren: een minimale eerste versie, een medium versie, en een volledige macro-versie. Het helpt om snel te lanceren én het lange termijn beeld niet te verliezen.

## De drie schalen

### 🌱 Stap 1 (2020–2024): de bestaande site

**Kern-waardecreator:** Een databank van activiteiten voor activiteitenbegeleiders in woonzorgcentra. Ontstaan tijdens COVID-19, organisch gegroeid. Medewerkers vinden inspiratie en delen hun eigen activiteiten met collega's in andere WZC's.

**Mensen & stakeholders:**

- ~6.000 geregistreerde gebruikers, ~2.000 maandelijkse bezoekers
- ~135 actieve bijdragers uit heel Vlaanderen
- Maite Mallentjer als inhoudelijk anker (Sense of Home kader)
- Geen actief beheer meer sinds 2022 — platform groeide organisch

**Infrastructuur:**

- Laravel platform, oude server
- 350+ activiteiten, vrij toegankelijk
- Basisfiltering op thema en doelgroep
- Geen DIAMANT-framework, geen initiatieven-structuur

**Wat ontbrak:**

- Geen kwaliteitskader voor activiteiten
- Geen begeleiding naar betekenisvollere activiteiten
- Platform als passieve databank, niet als leeromgeving

### 🌿 Stap 2 (2026): kwaliteitsgericht platform met DIAMANT als kader

**Kern-waardecreator:** Het platform is niet langer een vergaarbak van activiteiten, maar begeleidt activiteitenbegeleiders om bewoners echt een goede tijd te geven. Het DIAMANT-framework zit ingebakken in de structuur — niet als theorie, maar als subtiele sturing. Een activiteitenbegeleider voelt: "dit platform helpt mij om beter na te denken over wat ik doe."

**Mensen & stakeholders:**

- Frederik (beheer + AI-experimenten)
- Maite Mallentjer (inhoudelijke validatie, gebruik in opleidingen AP Hogeschool)
- Nadine Praet (Arteveldehogeschool, via Maite)
- Bestaande community van ~135 bijdragers (reactivatie)
- Slapende gebruikers (~6.000 accounts)

**Infrastructuur:**

- Laravel platform op nieuwe server (Digital Ocean / Forge)
- DIAMANT als structurerend kader: initiatieven + fiches + facetten
- "Diamantje van de maand" — subtiele maandelijkse spotlight op één DIAMANT-facet
- Reactivatiecampagne: gesegmenteerde outreach naar slapende gebruikers
- Werkende e-mailinfrastructuur

**Wat bewust NIET in deze fase:**

- Geen DIAMANT-wizard (dat is stap 3)
- Geen volledige AI-automatisering
- Geen leermateriaal of onboarding-sequenties

### 🌳 Stap 3 (later 2026 / 2027): lerende community met tooling en externe verankering

**Kern-waardecreator:** Activiteitenbegeleiders worden actief begeleid om DIAMANT-principes te internaliseren via tooling, leermateriaal en community. Het platform is een leeromgeving geworden. Bewoners krijgen letterlijk een stem via quotes per DIAMANT-facet.

**Mensen & stakeholders:**

- Maite en Nadine: platform gelinkt aan formele opleidingstrajecten
- Curator-ambassadeurs vanuit de community
- Mogelijk: koepelorganisaties ouderenzorg of beleidsmakers
- Mogelijke externe financiering via AI-project of subsidiedossier

**Infrastructuur:**

- DIAMANT-wizard: interactieve tool die medewerkers begeleidt bij het toepassen van DIAMANT op een concrete fiche
- Onboarding-mails met DIAMANT-intro voor nieuwe gebruikers
- Quotes van echte bewoners per DIAMANT-facet
- Content van en door Maite op het platform
- Verwijzingen naar opleidingen (Maite, Nadine)
- Mogelijk: AI-gegenereerde suggesties op basis van gedrag

## 🛠️ Technische noot: gebruikersrollen

**Probleem:** Het "Diamantje van de maand" vereist dat Maite (of een toekomstige curator) zelf activiteiten kan aanduiden als kandidaat-diamantje — zonder superadmin te zijn.

**Oplossing:**

- Een apart gebruikersprofiel (werktitel: "curator" of "inhoudelijk beheerder") met beperkte adminrechten
- Maite krijgt een login met deze rol — ofwel een nieuwe account, ofwel een upgrade van haar bestaande login
- Via de bestaande toolbar op activiteiten kan zij diamantjes aanduiden

**AI-automatisering er omheen:**

- Wekelijks of bij elke nieuwe activiteit: automatische analyse van kandidaat-diamantjes
- Resultaat: een mail naar Maite met een overzicht van sterke kandidaten (activiteit + korte AI-motivering)
- Maite duidt zelf aan welke het wordt — geen verplichte toelichting nodig
- Implementatie: spike op de coding-laag, niet voor lancering

---

*Bron: 🔭 Transition Roadmap — Hartverwarmers 2026 (Notion, mrt 2026).*
