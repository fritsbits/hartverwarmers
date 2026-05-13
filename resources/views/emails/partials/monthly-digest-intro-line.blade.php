@if ($payload->upcomingThemeCount > 0 && $payload->newFicheCount > 0)
    <p style="margin:0">In de komende weken staan er <strong>{{ $payload->upcomingThemeCount }} thema's</strong> op de kalender, en de afgelopen maand deelden collega's <strong>{{ $payload->newFicheCount }} nieuwe fiches</strong>.</p>
@elseif ($payload->upcomingThemeCount > 0)
    <p style="margin:0">In de komende weken staan er <strong>{{ $payload->upcomingThemeCount }} thema's</strong> op de kalender om alvast in te plannen.</p>
@elseif ($payload->newFicheCount > 0)
    <p style="margin:0">De afgelopen maand deelden collega's <strong>{{ $payload->newFicheCount }} nieuwe fiches</strong> om uit te putten.</p>
@endif
