<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use App\Services\JsonContent;
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

        $datesWithThemes = $themes
            ->flatMap(fn (Theme $t) => $t->occurrences->pluck('start_date'))
            ->filter()
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        return view('themes.index', [
            'month' => $month,
            'monthIntro' => $this->loadMonthIntro($month->month),
            'seasonThemes' => $seasonThemes->values(),
            'dayThemes' => $dayThemes->values(),
            'datesWithThemes' => $datesWithThemes,
        ]);
    }

    /**
     * @return array{title: string, intro: string}|null
     */
    private function loadMonthIntro(int $month): ?array
    {
        $all = JsonContent::getContent('themes/monthly-intros');
        if (! is_array($all) || ! isset($all[(string) $month])) {
            return null;
        }

        $entry = $all[(string) $month];
        if (! is_array($entry) || empty($entry['title']) || empty($entry['intro'])) {
            return null;
        }

        return ['title' => $entry['title'], 'intro' => $entry['intro']];
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
