<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fiche;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $range = request()->get('range', 'week');
        $cutoff = $range === 'week' ? now()->subDays(7) : now()->subWeeks(4);

        $weeklyTrend = $this->trend($range);
        $trendDelta = $this->trendDelta($weeklyTrend);
        $lastFiches = $this->lastFiches();
        $scored = $lastFiches->filter(fn ($f) => $f->presentation_score !== null);
        $lastFiveAvg = $scored->isNotEmpty() ? (int) round($scored->avg('presentation_score')) : null;
        $globalAvg = $this->globalAvg();
        $fichesWithSuggestions = Fiche::query()
            ->published()
            ->whereNotNull('ai_suggestions')
            ->where('created_at', '>=', $cutoff)
            ->with('initiative:id,slug')
            ->get(['id', 'title', 'slug', 'initiative_id', 'ai_suggestions']);
        $adoption = $this->adoptionStats($fichesWithSuggestions);
        $fieldAdoption = $this->fieldAdoption($fichesWithSuggestions);

        return view('admin.dashboard', [
            'weeklyTrend' => $weeklyTrend,
            'trendDelta' => $trendDelta,
            'lastFiches' => $lastFiches,
            'lastFiveAvg' => $lastFiveAvg,
            'globalAvg' => $globalAvg,
            'range' => $range,
            ...$adoption,
            'fieldAdoption' => $fieldAdoption,
            'ficheAdoptionDetails' => $this->ficheAdoptionDetails($fichesWithSuggestions),
        ]);
    }

    /** @return array<int, array{week_key: string|int, week_label: string, avg_score: int|null}> */
    private function trend(string $range): array
    {
        return $range === 'week' ? $this->dailyTrend() : $this->monthlyTrend();
    }

    /** @return array<int, array{week_key: string, week_label: string, avg_score: int|null}> */
    private function dailyTrend(): array
    {
        $fiches = Fiche::query()
            ->where('published', true)
            ->whereNotNull('presentation_score')
            ->whereNotNull('quality_assessed_at')
            ->where('quality_assessed_at', '>=', now()->subDays(7))
            ->get(['presentation_score', 'quality_assessed_at']);

        $grouped = [];
        foreach ($fiches as $fiche) {
            $key = $fiche->quality_assessed_at->format('Y-m-d');
            $grouped[$key][] = $fiche->presentation_score;
        }

        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $label = $date->format('d M');

            if (isset($grouped[$key])) {
                $scores = $grouped[$key];
                $avg = (int) round(array_sum($scores) / count($scores));
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => $avg];
            } else {
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => null];
            }
        }

        return $result;
    }

    /** @return array<int, array{week_key: int, week_label: string, avg_score: int|null}> */
    private function monthlyTrend(): array
    {
        $fiches = Fiche::query()
            ->where('published', true)
            ->whereNotNull('presentation_score')
            ->whereNotNull('quality_assessed_at')
            ->where('quality_assessed_at', '>=', now()->subWeeks(4))
            ->get(['presentation_score', 'quality_assessed_at']);

        $grouped = [];
        foreach ($fiches as $fiche) {
            $date = $fiche->quality_assessed_at;
            $key = (int) $date->format('oW');
            $grouped[$key][] = $fiche->presentation_score;
        }

        $result = [];
        for ($i = 3; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $key = (int) $date->format('oW');
            $label = $date->format('d M');

            if (isset($grouped[$key])) {
                $scores = $grouped[$key];
                $avg = (int) round(array_sum($scores) / count($scores));
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => $avg];
            } else {
                $result[] = ['week_key' => $key, 'week_label' => $label, 'avg_score' => null];
            }
        }

        return $result;
    }

    private function lastFiches(): Collection
    {
        return Fiche::query()
            ->published()
            ->with('initiative:id,slug')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'presentation_score', 'created_at', 'initiative_id']);
    }

    private function globalAvg(): ?int
    {
        $avg = Fiche::query()
            ->published()
            ->whereNotNull('presentation_score')
            ->avg('presentation_score');

        return $avg !== null ? (int) round($avg) : null;
    }

    /** @param array<int, array{avg_score: int|null}> $trend */
    private function trendDelta(array $trend): ?int
    {
        $scored = array_values(array_filter($trend, fn ($w) => $w['avg_score'] !== null));
        if (count($scored) < 2) {
            return null;
        }

        return $scored[array_key_last($scored)]['avg_score'] - $scored[0]['avg_score'];
    }

    /** @return array{withSuggestions: int, withAnyApplied: int, adoptionRate: int} */
    private function adoptionStats(Collection $fiches): array
    {
        $fields = ['title', 'description', 'preparation', 'inventory', 'process'];

        $withSuggestions = 0;
        $withAnyApplied = 0;

        foreach ($fiches as $fiche) {
            $suggestions = $fiche->ai_suggestions;
            $hasNonEmpty = collect($fields)->contains(
                fn ($field) => isset($suggestions[$field]) && $suggestions[$field] !== ''
            );

            if (! $hasNonEmpty) {
                continue;
            }

            $withSuggestions++;

            if (! empty($suggestions['applied'])) {
                $withAnyApplied++;
            }
        }

        $adoptionRate = $withSuggestions > 0
            ? (int) round($withAnyApplied / $withSuggestions * 100)
            : 0;

        return compact('withSuggestions', 'withAnyApplied', 'adoptionRate');
    }

    /**
     * @param  Collection<int, Fiche>  $fiches
     * @return array<int, array{title: string, url: string, fields: array<string, array{suggested: bool, applied: bool, label: string, shortLabel: string}>, adoptedCount: int, suggestedCount: int}>
     */
    private function ficheAdoptionDetails(Collection $fiches): array
    {
        $fieldMeta = [
            'title' => ['label' => 'Titel', 'shortLabel' => 'Titel'],
            'description' => ['label' => 'Omschrijving', 'shortLabel' => 'Omschr.'],
            'preparation' => ['label' => 'Voorbereiding', 'shortLabel' => 'Voorb.'],
            'inventory' => ['label' => 'Benodigdheden', 'shortLabel' => 'Ben.'],
            'process' => ['label' => 'Werkwijze', 'shortLabel' => 'Werkw.'],
        ];

        $result = [];

        foreach ($fiches as $fiche) {
            $suggestions = $fiche->ai_suggestions;
            $applied = $suggestions['applied'] ?? [];

            $fields = [];
            $suggestedCount = 0;
            $adoptedCount = 0;

            foreach ($fieldMeta as $key => $meta) {
                $suggested = isset($suggestions[$key]) && $suggestions[$key] !== '';
                $isApplied = in_array($key, $applied, true);

                if ($suggested) {
                    $suggestedCount++;
                }

                if ($suggested && $isApplied) {
                    $adoptedCount++;
                }

                $fields[$key] = [
                    'suggested' => $suggested,
                    'applied' => $isApplied,
                    'label' => $meta['label'],
                    'shortLabel' => $meta['shortLabel'],
                ];
            }

            if ($suggestedCount === 0) {
                continue;
            }

            $result[] = [
                'title' => $fiche->title,
                'url' => route('fiches.show', [$fiche->initiative, $fiche]),
                'fields' => $fields,
                'adoptedCount' => $adoptedCount,
                'suggestedCount' => $suggestedCount,
            ];
        }

        return $result;
    }

    /** @return array<string, array{suggested: int, applied: int, rate: int, label: string}> */
    private function fieldAdoption(Collection $fiches): array
    {
        $fields = [
            'title' => 'Titel',
            'description' => 'Omschrijving',
            'preparation' => 'Voorbereiding',
            'inventory' => 'Benodigdheden',
            'process' => 'Werkwijze',
        ];

        $result = [];
        foreach ($fields as $field => $label) {
            $suggested = 0;
            $applied = 0;

            foreach ($fiches as $fiche) {
                $suggestions = $fiche->ai_suggestions;

                if (isset($suggestions[$field]) && $suggestions[$field] !== '') {
                    $suggested++;
                }

                if (in_array($field, $suggestions['applied'] ?? [], true)) {
                    $applied++;
                }
            }

            $rate = $suggested > 0 ? (int) round($applied / $suggested * 100) : 0;

            $result[$field] = compact('suggested', 'applied', 'rate') + ['label' => $label];
        }

        return $result;
    }
}
