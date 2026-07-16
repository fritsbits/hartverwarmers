@php
    $monthLabel = $month->locale('nl_BE')->translatedFormat('F Y');
    $first = $month->startOfMonth();
    $daysInMonth = $first->endOfMonth()->day;
    $leadingEmpty = $first->isoWeekday() - 1;
    $rows = (int) ceil(($leadingEmpty + $daysInMonth) / 7);
    $totalCells = $rows * 7;

    $entriesByDate = $dayThemes
        ->filter(fn ($t) => $t->occurrences->first()?->start_date !== null)
        ->groupBy(fn ($t) => $t->occurrences->first()->start_date->format('Y-m-d'))
        ->map(fn ($group) => $group->map(function ($t) {
            $occ = $t->occurrences->first();
            $isRange = $occ->end_date && ! $occ->end_date->equalTo($occ->start_date);

            return [
                'title' => $t->title,
                'description' => $t->description,
                'until' => $isRange ? $occ->end_date->locale('nl_BE')->translatedFormat('j F') : null,
            ];
        })->values());

    $formatRange = function ($occ) {
        $start = $occ->start_date->locale('nl_BE');
        if (! $occ->end_date || $occ->end_date->equalTo($occ->start_date)) {
            return $start->translatedFormat('j F');
        }

        return $start->translatedFormat('j F').' – '.$occ->end_date->locale('nl_BE')->translatedFormat('j F');
    };
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Themakalender {{ $monthLabel }} — Hartverwarmers</title>
    <link rel="icon" type="image/svg+xml" href="/img/favicon.svg">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=aleo:700|fira-sans:300,400,500,600,700|nanum-pen-script:400&display=swap" rel="stylesheet">
    <style>
        :root {
            --c-primary: #E8764B;
            --c-primary-hover: #D4683F;
            --c-ink: #231E1A;
            --c-ink-soft: #5C544D;
            --c-ink-faint: #756C65;
            --c-cream: #FEF8F4;
            --c-subtle: #F5F0EC;
            --c-border: #EBE4DE;
            --c-white: #FFFFFF;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        html { -webkit-font-smoothing: antialiased; }

        body {
            font-family: 'Fira Sans', sans-serif;
            color: var(--c-ink);
            background: var(--c-subtle);
        }

        /* ————— The A3 landscape sheet ————— */
        .sheet {
            width: 420mm;
            height: 297mm;
            background: var(--c-white);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Header band — the poster version of the mini calendar's orange month band */
        .band {
            background: var(--c-primary);
            color: var(--c-white);
            padding: 9mm 12mm 8mm;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 10mm;
            position: relative;
        }

        .band::after {
            content: '';
            position: absolute;
            inset: 100% 0 auto 0;
            height: 2mm;
            background: linear-gradient(to bottom, rgba(35, 30, 26, 0.10), transparent);
            pointer-events: none;
        }

        .band-kicker {
            font-size: 11pt;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.85);
        }

        .band-month {
            font-family: 'Aleo', serif;
            font-weight: 700;
            font-size: 42pt;
            line-height: 1;
            text-transform: lowercase;
            font-variant-numeric: tabular-nums;
            margin-top: 2.5mm;
        }

        .band-site {
            display: flex;
            align-items: center;
            gap: 4mm;
            padding-bottom: 1.5mm;
        }

        .band-site svg { width: 11mm; height: 11mm; display: block; }

        .band-site-name {
            font-family: 'Aleo', serif;
            font-weight: 700;
            font-size: 15pt;
        }

        .band-site-tagline {
            font-size: 9pt;
            font-weight: 400;
            color: rgba(255, 255, 255, 0.85);
            margin-top: 0.8mm;
        }

        /* Season band — month-long themes + monthly intro */
        .season-band {
            background: var(--c-cream);
            border-bottom: 1px solid var(--c-border);
            padding: 4.5mm 12mm;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12mm;
        }

        .season-intro {
            font-size: 10.5pt;
            font-weight: 300;
            line-height: 1.45;
            color: var(--c-ink-soft);
            max-width: 220mm;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .season-themes {
            display: flex;
            gap: 10mm;
            flex-shrink: 0;
        }

        .season-theme { text-align: right; }

        .season-theme-title {
            font-family: 'Aleo', serif;
            font-weight: 700;
            font-size: 12pt;
            color: var(--c-primary-hover);
        }

        .season-theme-range {
            font-size: 8.5pt;
            color: var(--c-ink-faint);
            font-variant-numeric: tabular-nums;
        }

        /* ————— Calendar grid ————— */
        .calendar {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 6mm 12mm 0;
            min-height: 0;
        }

        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }

        .weekday {
            font-size: 9pt;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: lowercase;
            color: var(--c-ink-faint);
            padding: 0 0 2mm 3mm;
        }

        .grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            grid-template-rows: repeat({{ $rows }}, 1fr);
            border: 1px solid var(--c-border);
            border-radius: 2.5mm;
            overflow: hidden;
            min-height: 0;
        }

        .cell {
            border-right: 1px solid var(--c-border);
            border-bottom: 1px solid var(--c-border);
            padding: 2.5mm 3mm;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 1.6mm;
        }

        .cell:nth-child(7n) { border-right: none; }
        .cell:nth-last-child(-n+7) { border-bottom: none; }

        .cell.is-weekend { background: var(--c-cream); }
        .cell.is-pad { background: var(--c-subtle); }

        .cell-day {
            font-size: 11pt;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            color: var(--c-ink-faint);
            line-height: 1;
        }

        .cell.has-themes .cell-day { color: var(--c-primary); }

        .cell-theme-title {
            font-family: 'Aleo', serif;
            font-weight: 700;
            font-size: 10pt;
            line-height: 1.25;
            text-wrap: balance;
        }

        .cell-until {
            font-family: 'Fira Sans', sans-serif;
            font-weight: 600;
            font-size: 7.5pt;
            color: var(--c-primary-hover);
            white-space: nowrap;
        }

        .cell-desc {
            font-size: 7.5pt;
            font-weight: 400;
            line-height: 1.4;
            color: var(--c-ink-soft);
            margin-top: 0.8mm;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .cell.is-solo .cell-desc { -webkit-line-clamp: 3; }

        /* ————— Footer ————— */
        .sheet-footer {
            padding: 4mm 12mm 6mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10mm;
        }

        .footer-url {
            font-size: 10pt;
            color: var(--c-ink-soft);
        }

        .footer-url strong {
            font-weight: 600;
            color: var(--c-primary-hover);
        }

        .footer-note {
            font-family: 'Nanum Pen Script', cursive;
            font-size: 17pt;
            color: var(--c-primary);
            transform: rotate(-1.5deg);
        }

        /* ————— Screen preview chrome ————— */
        @media screen {
            body {
                min-height: 100vh;
                padding: 0 24px 48px;
            }

            .toolbar {
                max-width: 1100px;
                margin: 0 auto;
                padding: 20px 4px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                flex-wrap: wrap;
            }

            .toolbar-back {
                font-size: 15px;
                font-weight: 500;
                color: var(--c-ink-soft);
                text-decoration: none;
            }

            .toolbar-back:hover { color: var(--c-ink); }

            .toolbar-actions {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .toolbar-tip {
                font-size: 13.5px;
                color: var(--c-ink-faint);
                max-width: 44ch;
                text-align: right;
            }

            .print-btn {
                font-family: 'Fira Sans', sans-serif;
                font-size: 16px;
                font-weight: 600;
                color: var(--c-white);
                background: var(--c-primary);
                border: none;
                border-radius: 9999px;
                padding: 11px 26px;
                white-space: nowrap;
                cursor: pointer;
                transition: background-color 150ms ease-out;
            }

            .print-btn:hover { background: var(--c-primary-hover); }

            .print-btn:focus-visible {
                outline: 2px solid var(--c-primary-hover);
                outline-offset: 2px;
            }

            .sheet-stage {
                display: flex;
                justify-content: center;
            }

            .sheet-scale { transform-origin: top center; }

            .sheet {
                border-radius: 4px;
                box-shadow: 0 1px 2px rgba(35, 30, 26, 0.06), 0 12px 32px -8px rgba(35, 30, 26, 0.18);
            }
        }

        /* ————— Print ————— */
        @page { size: A3 landscape; margin: 0; }

        @media print {
            html, body { height: 297mm; overflow: hidden; }
            body { background: var(--c-white); padding: 0; }
            .screen-only { display: none !important; }
            .sheet-scale { transform: none !important; height: auto !important; }
            .sheet { height: 296.5mm; border-radius: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <header class="toolbar screen-only">
        <a class="toolbar-back" href="{{ route('themes.index', ['maand' => $month->format('Y-m')]) }}">&larr; Terug naar de themakalender</a>
        <div class="toolbar-actions">
            <p class="toolbar-tip">Kies papierformaat A3 en liggende richting in het printvenster.</p>
            <button type="button" class="print-btn" onclick="window.print()">Druk de kalender af</button>
        </div>
    </header>

    <div class="sheet-stage">
        <div class="sheet-scale">
            <div class="sheet">
                <header class="band">
                    <div>
                        <p class="band-kicker">Themakalender</p>
                        <h1 class="band-month">{{ $monthLabel }}</h1>
                    </div>
                    <div class="band-site">
                        <svg viewBox="0 0 100 100" role="img" aria-label="Hartverwarmers">
                            <path d="M20,5 L2,36 L50,97 L98,36 L80,5 L50,22 Z" fill="#FFFFFF"/>
                            <line x1="2" y1="36" x2="98" y2="36" stroke="rgba(232,118,75,0.35)" stroke-width="2.5" stroke-linecap="round"/>
                            <line x1="14" y1="51.25" x2="86" y2="51.25" stroke="rgba(232,118,75,0.35)" stroke-width="2" stroke-linecap="round"/>
                            <line x1="20" y1="5" x2="50" y2="22" stroke="rgba(232,118,75,0.35)" stroke-width="1.75" stroke-linecap="round"/>
                            <line x1="80" y1="5" x2="50" y2="22" stroke="rgba(232,118,75,0.35)" stroke-width="1.75" stroke-linecap="round"/>
                        </svg>
                        <div>
                            <div class="band-site-name">hartverwarmers.be</div>
                            <div class="band-site-tagline">Deugddoende activiteiten voor woonzorgcentra</div>
                        </div>
                    </div>
                </header>

                @if($seasonThemes->isNotEmpty() || ! empty($monthIntro))
                    <div class="season-band">
                        @if(! empty($monthIntro))
                            <p class="season-intro">{{ $monthIntro['intro'] }}</p>
                        @endif
                        @if($seasonThemes->isNotEmpty())
                            <div class="season-themes">
                                @foreach($seasonThemes as $theme)
                                    @php($occ = $theme->occurrences->first())
                                    <div class="season-theme">
                                        <div class="season-theme-title">{{ $theme->title }}</div>
                                        @if($occ)
                                            <div class="season-theme-range">{{ $formatRange($occ) }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <main class="calendar">
                    <div class="weekdays">
                        @foreach(['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'] as $weekday)
                            <div class="weekday">{{ $weekday }}</div>
                        @endforeach
                    </div>
                    <div class="grid">
                        @for($cellIndex = 0; $cellIndex < $totalCells; $cellIndex++)
                            @php($day = $cellIndex - $leadingEmpty + 1)
                            @php($isWeekend = $cellIndex % 7 >= 5)
                            @if($day < 1 || $day > $daysInMonth)
                                <div class="cell is-pad"></div>
                            @else
                                @php($entries = $entriesByDate[$first->setDay($day)->format('Y-m-d')] ?? collect())
                                @php($showDescriptions = $entries->count() <= 2)
                                <div class="cell {{ $isWeekend ? 'is-weekend' : '' }} {{ $entries->isNotEmpty() ? 'has-themes' : '' }} {{ $entries->count() === 1 ? 'is-solo' : '' }}">
                                    <div class="cell-day">{{ $day }}</div>
                                    @foreach($entries as $entry)
                                        <div class="cell-theme">
                                            <div class="cell-theme-title">
                                                {{ $entry['title'] }}
                                                @if($entry['until'])
                                                    <span class="cell-until">t/m {{ $entry['until'] }}</span>
                                                @endif
                                            </div>
                                            @if($entry['description'] && $showDescriptions)
                                                <p class="cell-desc">{{ $entry['description'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endfor
                    </div>
                </main>

                <footer class="sheet-footer">
                    <p class="footer-url">Alle activiteiten en fiches bij elk thema vind je op <strong>hartverwarmers.be/themas</strong></p>
                    <p class="footer-note">Veel warme momenten deze maand!</p>
                </footer>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var stage = document.querySelector('.sheet-scale');
            var sheet = document.querySelector('.sheet');

            function fit() {
                var available = document.body.clientWidth - 48;
                var scale = Math.min(1, available / sheet.offsetWidth);
                stage.style.transform = 'scale(' + scale + ')';
                stage.style.height = (sheet.offsetHeight * scale) + 'px';
            }

            fit();
            window.addEventListener('resize', fit);
        })();
    </script>
</body>
</html>
