<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bronbestand van de themakalender
    |--------------------------------------------------------------------------
    |
    | themes.json is de enige bron voor thema's, gelegenheden en koppelingen.
    | themes:health-check leest het via deze sleutel, zodat een test een
    | fixture kan meegeven zonder aan het echte bestand te raken.
    |
    */

    'file' => database_path('seeders/data/themes.json'),

    /*
    |--------------------------------------------------------------------------
    | Drempels voor themes:health-check
    |--------------------------------------------------------------------------
    |
    | Een mail vertrekt alleen wanneer een drempel overschreden is. Daarna
    | zwijgt het commando per voorwaarde zolang de afkoelperiode loopt.
    |
    | min_horizon_days           alarm zodra de laatste gelegenheid minder dan
    |                            zoveel dagen vooruit ligt
    | upcoming_window_days       hoe ver vooruit "aankomende thema's" reiken
    | max_empty_upcoming         aankomende thema's met koppelingen in het
    |                            bestand maar nul gepubliceerde fiches
    | max_drift                  verschillen tussen de oplosbare slugs in het
    |                            bestand en de rijen in fiche_theme
    | max_fiches_after_watermark gepubliceerde fiches aangemaakt na de
    |                            fiche_match_watermark in het bestand
    |
    */

    'health' => [
        'min_horizon_days' => 60,
        'upcoming_window_days' => 60,
        'max_empty_upcoming' => 0,
        'max_drift' => 0,
        'max_fiches_after_watermark' => 25,
        'cooldown_days' => 14,
    ],

];
