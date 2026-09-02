<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * De cachesleutels die de themakalender warm houden.
 *
 * De kalenderpagina's cachen per maand met een TTL van een kwartier. Wie de
 * thema's in de databank verandert, ziet die verandering pas als die sleutels
 * weg zijn — vandaar dat zowel de import als de opruimstap hier langskomt.
 * Er bestaat geen tag om op te ruimen, dus de maandvarianten worden uitgeteld.
 */
class ThemeCache
{
    /** Het bereik van jaren waarvoor maandsleutels bestaan. */
    private const YEARS = [2024, 2030];

    public static function flush(): void
    {
        Cache::forget('home:upcoming-themes:'.today()->toDateString());
        Cache::forget('themes:monthly-intros');

        foreach (range(self::YEARS[0], self::YEARS[1]) as $year) {
            for ($month = 1; $month <= 12; $month++) {
                $suffix = sprintf('%d-%02d', $year, $month);
                Cache::forget('themes:index:'.$suffix);
                Cache::forget('home:themes-by-date:'.$suffix);
            }
        }
    }
}
