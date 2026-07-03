<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Diamantje van {{ $rotation->monthLabel() }} — Hartverwarmers</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=aleo:700|fira-sans:400,600" rel="stylesheet">
    <style>
        body { margin: 0; background: #FEF8F4; color: #231E1A; font-family: 'Fira Sans', sans-serif; font-size: 17px; line-height: 1.6; }
        .wrap { max-width: 34rem; margin: 0 auto; padding: 4rem 1.5rem; }
        .card { background: #FFFFFF; border: 1px solid #EBE4DE; border-radius: 12px; padding: 2rem; }
        h1 { font-family: 'Aleo', serif; font-weight: 700; font-size: 1.6rem; line-height: 1.25; margin: 0 0 1rem; }
        .label { text-transform: uppercase; letter-spacing: 0.1em; color: #E8764B; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.75rem; }
        .meta { color: #756C65; font-size: 0.9rem; margin: 0 0 1.5rem; }
        .btn { display: inline-block; background: #E8764B; color: #FFFFFF; border: 0; border-radius: 9999px; padding: 0.75rem 1.75rem; font-family: inherit; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #D4683F; }
        .success { background: #FDF3EE; border: 1px solid #EBE4DE; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; font-size: 0.95rem; }
        a { color: #E8764B; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="label">&#9733; Diamantje van {{ $rotation->monthLabel() }}</div>

            @if ($rotation->isAwarded())
                <h1>Dit diamantje is al toegekend</h1>
                <p class="meta">
                    Voor {{ $rotation->monthLabel() }} kreeg
                    <strong>{{ $rotation->fiche?->title ?? 'een fiche' }}</strong>
                    het diamantje al. Een volgende keuze maak je via de suggestiemail van volgende maand.
                </p>
            @elseif ($rotation->fiche_id === $fiche->id)
                @if (session('rotation-choice-saved'))
                    <div class="success">Keuze opgeslagen &#10003;</div>
                @endif
                <h1>{{ $fiche->title }}</h1>
                <p class="meta">
                    door {{ $fiche->user->full_name }}@if ($fiche->user->organisation) · {{ $fiche->user->organisation }}@endif
                </p>
                <p>
                    Deze fiche wordt op 1 {{ $rotation->month->locale('nl_BE')->isoFormat('MMMM') }}
                    het diamantje van de maand. Je hoeft verder niets te doen.
                </p>
            @else
                <h1>{{ $fiche->title }}</h1>
                <p class="meta">
                    door {{ $fiche->user->full_name }}@if ($fiche->user->organisation) · {{ $fiche->user->organisation }}@endif
                    @if (! is_null($fiche->quality_score)) · kwaliteitsscore {{ $fiche->quality_score }}@endif
                </p>
                <p>
                    Maak deze fiche het diamantje van {{ $rotation->monthLabel() }}?
                    De toekenning zelf gebeurt automatisch op 1 {{ $rotation->month->locale('nl_BE')->isoFormat('MMMM') }}.
                </p>
                <form method="POST" action="{{ $confirmUrl }}">
                    @csrf
                    <button type="submit" class="btn">Bevestig deze keuze</button>
                </form>
            @endif
        </div>
    </div>
</body>
</html>
