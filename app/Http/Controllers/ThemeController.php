<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use App\Services\ThemeCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(Request $request, ThemeCalendar $calendar): View
    {
        $month = $this->parseMonth($request->query('maand'));

        $themes = $calendar->themesForMonth($month);

        [$seasonThemes, $dayThemes] = $themes->partition(fn (Theme $t) => $t->is_month);

        $themesByDate = $themes
            ->filter(fn (Theme $t) => $t->occurrences->first() !== null)
            ->groupBy(fn (Theme $t) => $t->occurrences->first()->start_date->format('Y-m-d'))
            ->map(fn ($group) => $group->map(fn (Theme $t) => [
                'slug' => $t->slug,
                'title' => $t->title,
                'fiche_count' => $t->fiches->count(),
            ])->values()->all())
            ->all();

        $today = CarbonImmutable::now('Europe/Brussels')->startOfDay();
        $prevMonth = $month->subMonth();
        $showPrev = $prevMonth->endOfMonth()->gte($today);

        return view('themes.index', [
            'month' => $month,
            'monthIntro' => $calendar->monthIntro($month->month),
            'seasonThemes' => $seasonThemes->values(),
            'dayThemes' => $dayThemes->values(),
            'themesByDate' => $themesByDate,
            'showPrev' => $showPrev,
        ]);
    }

    public function print(Request $request, ThemeCalendar $calendar): View
    {
        return view('themes.print', $calendar->printSheet($this->parseMonth($request->query('maand'))));
    }

    private function parseMonth(?string $input): CarbonImmutable
    {
        $default = CarbonImmutable::now('Europe/Brussels')->startOfMonth();

        if (! $input || ! preg_match('/^(\d{4})-(\d{2})$/', $input, $m)) {
            return $default;
        }

        $year = (int) $m[1];
        $month = (int) $m[2];
        if ($month < 1 || $month > 12) {
            return $default;
        }

        return CarbonImmutable::create($year, $month, 1, 0, 0, 0, 'Europe/Brussels');
    }
}
