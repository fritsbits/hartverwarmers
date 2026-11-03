<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Themakalender {{ $year }} — Hartverwarmers</title>
    <link href="https://fonts.bunny.net/css?family=aleo:700|fira-sans:300,400,500,600,700|nanum-pen-script:400&display=swap" rel="stylesheet">
    <style>
        @include('themes.partials.print-sheet-styles')

        @page { size: A3 landscape; margin: 0; }

        html, body { background: var(--c-white); }

        .sheet { height: 296.5mm; break-after: page; }
        .sheet:last-child { break-after: auto; }

        .sheet-footer { padding: 3mm 12mm 5mm; }

        /* ————— Cover ————— */
        .cover { border-top: 20px solid var(--c-primary); }

        .cover-header {
            margin: 0 70px;
            padding: 34px 0 27px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 30px;
            border-bottom: 1px solid var(--c-border);
        }

        .cover-brand { font: 700 32px/1.4 'Aleo', serif; }

        .cover-header p { color: var(--c-ink-faint); font-size: 18px; }

        .cover-main {
            padding: 64px 70px 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .cover-title {
            font: 700 83px/1.15 'Aleo', serif;
            letter-spacing: -0.03em;
            color: var(--c-primary-text);
        }

        .cover-subtitle {
            font-size: 29px;
            line-height: 1.5;
            margin-top: 20px;
            color: var(--c-ink-soft);
        }

        .cover-year-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 80px;
            margin-top: auto;
            padding: 35px 0 48px;
        }

        .cover-year {
            font: 700 300px/1.05 'Aleo', serif;
            letter-spacing: -0.04em;
            font-variant-numeric: tabular-nums;
        }

        .cover-description {
            max-width: 370px;
            font-size: 24px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .cover-description strong { font-weight: 600; }

        .cover-months {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px;
            border-top: 2px solid var(--c-primary);
            padding: 21px 0 24px;
            font-size: 18px;
            font-weight: 500;
            color: var(--c-ink-soft);
        }

        .cover-footer {
            padding: 20px 70px 25px;
            background: var(--c-cream);
            display: flex;
            justify-content: space-between;
            gap: 35px;
            font-size: 18px;
            color: var(--c-ink-soft);
        }

        /* ————— How-to page ————— */
        .guide-main {
            flex: 1;
            padding: 16mm 16mm 0;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .guide-kicker {
            font-size: 11pt;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--c-primary-text);
        }

        .guide-title {
            font: 700 40pt/1.15 'Aleo', serif;
            margin-top: 3mm;
            max-width: 260mm;
        }

        .guide-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16mm;
            margin-top: 18mm;
        }

        .guide-column h2 {
            font: 700 19pt/1.3 'Aleo', serif;
            padding-bottom: 4mm;
            margin-bottom: 7mm;
            border-bottom: 2px solid var(--c-primary);
        }

        .guide-column ul { list-style: none; display: flex; flex-direction: column; gap: 6mm; }

        .guide-column li {
            font-size: 14pt;
            line-height: 1.55;
            color: var(--c-ink-soft);
            padding-left: 6mm;
            position: relative;
        }

        .guide-column li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.6em;
            width: 1.8mm;
            height: 1.8mm;
            border-radius: 50%;
            background: var(--c-primary);
        }

        .guide-column li strong { color: var(--c-ink); font-weight: 600; }

        .guide-note {
            margin-top: auto;
            margin-bottom: 10mm;
            padding: 6mm 8mm;
            background: var(--c-cream);
            border: 1px solid var(--c-border);
            border-radius: 2.5mm;
            font-size: 13pt;
            line-height: 1.5;
            color: var(--c-ink-soft);
            display: flex;
            justify-content: space-between;
            gap: 12mm;
        }

        .guide-note strong { color: var(--c-ink); font-weight: 600; }

        .guide-note a { color: var(--c-primary-text); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <section class="sheet cover">
        <header class="cover-header">
            <div class="cover-brand">hartverwarmers.be</div>
            <p>Voor begeleiders in woonzorgcentra</p>
        </header>
        <div class="cover-main">
            <h1 class="cover-title">Themakalender</h1>
            <p class="cover-subtitle">Een jaar om samen te beleven.</p>
            <div class="cover-year-row">
                <div class="cover-year">{{ $year }}</div>
                <p class="cover-description"><strong>Twaalf maanden vol aanknopingspunten.</strong><br>Thema's, kleine ideeën en ruimte voor jullie eigen plannen.</p>
            </div>
            <div class="cover-months">
                @foreach($sheets as $sheet)
                    <span>{{ $sheet['month']->locale('nl_BE')->translatedFormat('F') }}</span>
                @endforeach
            </div>
        </div>
        <footer class="cover-footer">
            <p>Deugddoende activiteiten voor woonzorgcentra</p>
            <p>Voor grote plannen én kleine momenten.</p>
        </footer>
    </section>

    <section class="sheet guide">
        <div class="guide-main">
            <p class="guide-kicker">Themakalender {{ $year }}</p>
            <h1 class="guide-title">Zo haal je het meeste uit deze kalender</h1>

            <div class="guide-columns">
                <div class="guide-column">
                    <h2>Wat er op elk blad staat</h2>
                    <ul>
                        <li>Elke maand staat op <strong>één A3-blad</strong>. Hang het aan het prikbord of in de teamruimte.</li>
                        <li>Bij elke datum lees je welk thema er valt, met <strong>een kort idee</strong> om mee te starten.</li>
                        <li>Duurt een thema meerdere dagen, dan staat het op de eerste dag met <strong>t/m</strong>. Loopt het door in de volgende maand, dan vind je het daar terug op de eerste.</li>
                        <li>Thema's die een hele periode lopen, zoals de seizoenen, staan in de strook bovenaan.</li>
                        <li>Scan de <strong>QR-code</strong> onderaan het blad voor alle fiches van die maand.</li>
                    </ul>
                </div>

                <div class="guide-column">
                    <h2>Kies samen met de bewoners</h2>
                    <ul>
                        <li>Een themadag is <strong>een aanknopingspunt</strong>, geen verplicht nummer. Vraag wat bewoners aanspreekt en laat de rest gerust liggen.</li>
                        <li><strong>Jullie eigen tradities</strong> tellen evenveel: de verjaardag van het huis, de kermis in het dorp, het bezoek van de harmonie. Schrijf ze erbij, er is plaats genoeg in de vakjes.</li>
                        <li><strong>Plan ook rust.</strong> Een stille week of een babbel op de kamer is soms het beste programma.</li>
                        <li>Sommige thema's raken aan <strong>familie, ziekte of verlies</strong>. Peil vooraf wie wat meer omkadering kan gebruiken.</li>
                    </ul>
                </div>

                <div class="guide-column">
                    <h2>Afdrukken</h2>
                    <ul>
                        <li>De kalender is gemaakt voor <strong>A3, liggend</strong>. Druk af op ware grootte.</li>
                        <li>Alleen A4 bij de hand? Kies <strong>passend op de pagina</strong>. Alles blijft op één blad, al worden de korte ideeën dan wel klein.</li>
                        <li>In <strong>zwart-wit</strong> blijft alles leesbaar.</li>
                        <li>Druk enkel de maanden af die je nodig hebt. Zo hang je telkens het blad van deze en volgende maand op.</li>
                    </ul>
                </div>
            </div>

            <div class="guide-note">
                <p><strong>Nog niet elke datum ligt vast.</strong> Sommige thema's hebben nog geen bevestigde datum voor {{ $year }}. Ze verschijnen op <a href="{{ $themesUrl }}">hartverwarmers.be/themas</a> zodra de datum bekend is.</p>
                <p><strong>Deel de kalender gerust</strong> met collega's in andere huizen. Ze vinden hem gratis op <a href="{{ $shareUrl }}">{{ $shareLabel }}</a>.</p>
            </div>
        </div>
    </section>

    @foreach($sheets as $sheet)
        @include('themes.partials.print-sheet', $sheet)
    @endforeach
</body>
</html>
