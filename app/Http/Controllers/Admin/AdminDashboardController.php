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
        $weeklyTrend = $this->weeklyTrend();
        $trendDelta = $this->trendDelta($weeklyTrend);
        $lastFiches = $this->lastFiches();
        $lastFiveAvg = $lastFiches->isNotEmpty()
            ? (int) round($lastFiches->avg('presentation_score'))
            : null;
        $globalAvg = $this->globalAvg();

        return view('admin.dashboard', [
            'weeklyTrend' => $weeklyTrend,
            'trendDelta' => $trendDelta,
            'lastFiches' => $lastFiches,
            'lastFiveAvg' => $lastFiveAvg,
            'globalAvg' => $globalAvg,
        ]);
    }

    /** @return array<int, array{week_key: int, week_label: string, avg_score: int|null}> */
    private function weeklyTrend(): array
    {
        $fiches = Fiche::query()
            ->where('published', true)
            ->whereNotNull('presentation_score')
            ->whereNotNull('quality_assessed_at')
            ->where('quality_assessed_at', '>=', now()->subWeeks(8))
            ->get(['presentation_score', 'quality_assessed_at']);

        // Group in PHP using ISO year+week key (matches YEARWEEK mode 1)
        $grouped = [];
        foreach ($fiches as $fiche) {
            $date = $fiche->quality_assessed_at;
            $key = (int) $date->format('oW');
            $grouped[$key][] = $fiche->presentation_score;
        }

        // Build 8-slot array; missing weeks get null avg_score
        $result = [];
        for ($i = 7; $i >= 0; $i--) {
            $date = now()->subWeeks($i)->startOfWeek();
            $key = (int) $date->format('oW');
            $label = $date->format('d M');

            if (isset($grouped[$key])) {
                $scores = $grouped[$key];
                $avg = (int) round(array_sum($scores) / count($scores));
                $result[] = [
                    'week_key' => $key,
                    'week_label' => $label,
                    'avg_score' => $avg,
                ];
            } else {
                $result[] = [
                    'week_key' => $key,
                    'week_label' => $label,
                    'avg_score' => null,
                ];
            }
        }

        return $result;
    }

    private function lastFiches(): Collection
    {
        return Fiche::query()
            ->published()
            ->whereNotNull('presentation_score')
            ->whereNotNull('quality_assessed_at')
            ->orderBy('quality_assessed_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'presentation_score', 'quality_assessed_at']);
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
}
