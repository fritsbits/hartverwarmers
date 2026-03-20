<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fiche;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $weeklyTrend = $this->weeklyTrend();
        $trendDelta = $this->trendDelta($weeklyTrend);

        return view('admin.dashboard', [
            'weeklyTrend' => $weeklyTrend,
            'trendDelta' => $trendDelta,
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
            $date = now()->subWeeks($i);
            $key = (int) $date->format('oW');
            $label = $date->startOfWeek()->format('d M');

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
