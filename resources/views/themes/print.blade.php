@php($monthLabel = $month->locale('nl_BE')->translatedFormat('F Y'))
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
        @include('themes.partials.print-sheet-styles')

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
            @include('themes.partials.print-sheet')
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
