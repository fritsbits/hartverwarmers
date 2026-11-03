:root {
    --c-primary: #E8764B;
    --c-primary-hover: #D4683F;
    --c-primary-text: #A9501C;
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

.cell.is-crowded { gap: 1mm; }
.cell.is-crowded .cell-theme-title { font-size: 9pt; line-height: 1.2; }

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

.footer-link {
    display: flex;
    align-items: center;
    gap: 4mm;
}

.footer-link svg { width: 18mm; height: 18mm; display: block; flex-shrink: 0; }

.footer-link a {
    color: var(--c-primary-hover);
    font-weight: 600;
    text-decoration: none;
}

.footer-note {
    font-family: 'Nanum Pen Script', cursive;
    font-size: 17pt;
    color: var(--c-primary);
    transform: rotate(-1.5deg);
}
