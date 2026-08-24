<?php

namespace App\Services\Okr;

use App\Metrics\DiamantScoreShareMetric;
use App\Models\Fiche;
use Illuminate\Support\Collection;

/**
 * The two views that make the diamant-score KR readable.
 *
 * The KR itself is a stock: what share of the whole library is strong. That
 * number barely moves, which says nothing about whether the work is getting
 * better. These add the two missing questions: are newer fiches better than
 * older ones (cohorts), and how far is the library from the threshold
 * (distribution).
 */
final class DiamantQualityInsights
{
    /** Under this many fiches a cohort average is an anecdote, not a signal. */
    private const THIN_COHORT = 3;

    /**
     * Average diamant score per month of fiches *created* that month.
     *
     * Buckets on created_at, never on quality_assessed_at or updated_at: the
     * assessment job writes the score with updateQuietly(), which bumps
     * updated_at, so both of those timestamps say when the AI last ran rather
     * than when a person made the fiche.
     *
     * @return array<int, array{label: string, fiches: int, avg: int|null, thin: bool}>
     */
    public function cohorts(string $range): array
    {
        $months = match ($range) {
            'week' => 3,
            'month' => 6,
            'quarter' => 12,
            default => 36,
        };

        $scores = Fiche::query()
            ->published()
            ->whereNotNull('quality_score')
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->get(['created_at', 'quality_score'])
            ->groupBy(fn (Fiche $fiche) => $fiche->created_at->format('Y-m'))
            ->map(fn (Collection $group) => $group->pluck('quality_score'));

        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $cohort = $scores->get($month->format('Y-m'));
            $count = $cohort?->count() ?? 0;

            $result[] = [
                'label' => $month->isoFormat('MMM YY'),
                'fiches' => $count,
                'avg' => $count > 0 ? (int) round($cohort->avg()) : null,
                'thin' => $count > 0 && $count < self::THIN_COHORT,
            ];
        }

        return $result;
    }

    /**
     * How the library's scores are spread, in bands of ten, plus what it would
     * take to reach the threshold.
     *
     * @return array{bands: array<int, array{label: string, count: int, strong: bool}>, scored: int, strong: int, threshold: int, oneStepBelow: int, projectedShare: int|null}
     */
    public function distribution(): array
    {
        $threshold = DiamantScoreShareMetric::THRESHOLD;

        $scores = Fiche::query()
            ->published()
            ->whereNotNull('quality_score')
            ->pluck('quality_score');

        $bands = [];

        for ($start = 0; $start <= 90; $start += 10) {
            $end = $start + ($start === 90 ? 10 : 9);

            $bands[] = [
                'label' => $start.'–'.$end,
                'count' => $scores->filter(fn (int $score) => $score >= $start && $score <= $end)->count(),
                'strong' => $start >= $threshold,
            ];
        }

        $scored = $scores->count();
        $strong = $scores->filter(fn (int $score) => $score >= $threshold)->count();

        // The band directly under the threshold: the cheapest way to move the KR,
        // and the group worth putting a rework effort behind.
        $oneStepBelow = $scores
            ->filter(fn (int $score) => $score >= $threshold - 10 && $score < $threshold)
            ->count();

        return [
            'bands' => $bands,
            'scored' => $scored,
            'strong' => $strong,
            'threshold' => $threshold,
            'oneStepBelow' => $oneStepBelow,
            'projectedShare' => $scored > 0
                ? (int) round(($strong + $oneStepBelow) / $scored * 100)
                : null,
        ];
    }
}
