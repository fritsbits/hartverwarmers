<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->parseMonth($request->query('maand'));

        $themes = Theme::query()
            ->forMonth($month->year, $month->month)
            ->with([
                'occurrences' => fn ($q) => $q->where('year', $month->year),
                'fiches' => fn ($q) => $q->published()->with('initiative', 'user', 'tags', 'files')->withCount('comments')->take(6),
            ])
            ->get()
            ->sortBy(fn (Theme $t) => optional($t->occurrences->first())->start_date);

        [$seasonThemes, $dayThemes] = $themes->partition(fn (Theme $t) => $t->is_month);

        return view('themes.index', [
            'month' => $month,
            'seasonThemes' => $seasonThemes->values(),
            'dayThemes' => $dayThemes->values(),
        ]);
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
