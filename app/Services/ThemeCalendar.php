<?php

namespace App\Services;

use App\Models\Theme;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ThemeCalendar
{
    /**
     * Themes that touch the given month, with that year's occurrence and the
     * published fiches eager-loaded, ordered by start date.
     *
     * @return Collection<int, Theme>
     */
    public function themesForMonth(CarbonImmutable $month): Collection
    {
        return Cache::remember(
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
    }

    /**
     * @return array{title: string, intro: string}|null
     */
    public function monthIntro(int $month): ?array
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

    /**
     * Everything one printed A3 month sheet needs.
     *
     * @return array{month: CarbonImmutable, monthIntro: array{title: string, intro: string}|null, seasonThemes: Collection<int, Theme>, dayThemes: Collection<int, Theme>}
     */
    public function printSheet(CarbonImmutable $month): array
    {
        [$seasonThemes, $dayThemes] = $this->themesForMonth($month)->partition(fn (Theme $t) => $t->is_month);

        return [
            'month' => $month,
            'monthIntro' => $this->monthIntro($month->month),
            'seasonThemes' => $seasonThemes->values(),
            'dayThemes' => $dayThemes->values(),
        ];
    }
}
