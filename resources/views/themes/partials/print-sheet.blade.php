@php
    $monthLabel = $month->locale('nl_BE')->translatedFormat('F Y');
    $monthLink ??= null;
    $first = $month->startOfMonth();
    $daysInMonth = $first->endOfMonth()->day;
    $leadingEmpty = $first->isoWeekday() - 1;
    $rows = (int) ceil(($leadingEmpty + $daysInMonth) / 7);
    $totalCells = $rows * 7;

    $entriesByDate = $dayThemes
        ->filter(fn ($t) => $t->occurrences->first()?->start_date !== null)
        ->groupBy(fn ($t) => $t->occurrences->first()->start_date->max($first)->format('Y-m-d'))
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
        <div class="grid" style="grid-template-rows: repeat({{ $rows }}, 1fr);">
            @for($cellIndex = 0; $cellIndex < $totalCells; $cellIndex++)
                @php($day = $cellIndex - $leadingEmpty + 1)
                @php($isWeekend = $cellIndex % 7 >= 5)
                @if($day < 1 || $day > $daysInMonth)
                    <div class="cell is-pad"></div>
                @else
                    @php($entries = $entriesByDate[$first->setDay($day)->format('Y-m-d')] ?? collect())
                    @php($showDescriptions = $entries->count() <= 2)
                    <div class="cell {{ $isWeekend ? 'is-weekend' : '' }} {{ $entries->isNotEmpty() ? 'has-themes' : '' }} {{ $entries->count() === 1 ? 'is-solo' : '' }} {{ $entries->count() >= 4 ? 'is-crowded' : '' }}">
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
        @if($monthLink)
            <div class="footer-link">
                {!! $monthLink['qr'] !!}
                <p class="footer-url">Fiches en ideeën bij de thema's van {{ $month->locale('nl_BE')->translatedFormat('F') }}:<br><a href="{{ $monthLink['url'] }}">{{ $monthLink['label'] }}</a></p>
            </div>
        @else
            <p class="footer-url">Alle activiteiten en fiches bij elk thema vind je op <strong>hartverwarmers.be/themas</strong></p>
        @endif
        <p class="footer-note">Veel warme momenten deze maand!</p>
    </footer>
</div>
