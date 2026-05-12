<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use App\Services\JsonContent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->parseMonth($request->query('maand'));

        $themes = Cache::remember(
            'themes:index:'.$month->format('Y-m'),
            now()->addMinutes(15),
            fn () => Theme::query()
                ->forMonth($month->year, $month->month)
                ->with([
                    'occurrences' => fn ($q) => $q->where('year', $month->year),
                    'fiches' => fn ($q) => $q->published()->with('initiative', 'user')->withCount('comments'),
                ])
                ->get()
                ->sortBy(fn (Theme $t) => optional($t->occurrences->first())->start_date)
                ->values()
        );

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
            'monthIntro' => $this->loadMonthIntro($month->month),
            'seasonThemes' => $seasonThemes->values(),
            'dayThemes' => $dayThemes->values(),
            'themesByDate' => $themesByDate,
            'showPrev' => $showPrev,
        ]);
    }

    /**
     * @return array{title: string, intro: string}|null
     */
    private function loadMonthIntro(int $month): ?array
    {
        $all = Cache::rememberForever(
            'themes:monthly-intros',
            fn () => JsonContent::getContent('themes/monthly-intros') ?: []
        );
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
