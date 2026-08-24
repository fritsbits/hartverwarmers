<?php

namespace App\Metrics;

use App\Models\Fiche;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Share of the published library that reaches a strong DIAMANT score.
 *
 * A plain average moves glacially on a corpus this size — twenty excellent
 * fiches lift it by under two points — so the KR counts fiches over a
 * threshold instead: it moves when strong fiches are added *and* when weak
 * ones are reworked.
 */
class DiamantScoreShareMetric implements Metric
{
    /** "Goed" in the AI rubric, and the green band of the score-colour convention. */
    public const THRESHOLD = 70;

    /** Below this many scored fiches the share is too jumpy to read as progress. */
    private const LOW_DATA_BELOW = 10;

    public function compute(string $range): MetricValue
    {
        // Range-independent on purpose: this measures the library as it stands,
        // like presentation_score_avg. No previous value either — quality_score
        // is mutable, so a period-over-period delta would only reflect corpus
        // growth, never the score improvements the KR is actually about.
        return $this->share(Fiche::query()->published());
    }

    public function caption(string $range): string
    {
        return 'van de gepubliceerde fiches haalt '.self::THRESHOLD.'+ op de diamantscore';
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        // quality_score is mutable, so a baseline "as of $date" is measured with
        // today's scores filtered to fiches that existed then — not the scores as
        // they were on $date. created_at is the publish-date proxy (fiches have no
        // published_at).
        return $this->share(
            Fiche::query()->published()->where('created_at', '<=', $date)
        );
    }

    /** @param  Builder<Fiche>  $query */
    private function share(Builder $query): MetricValue
    {
        $scored = (clone $query)->whereNotNull('quality_score')->count();

        if ($scored === 0) {
            return new MetricValue(current: null, unit: '%', lowData: true);
        }

        $strong = (clone $query)->where('quality_score', '>=', self::THRESHOLD)->count();

        return new MetricValue(
            current: (int) round($strong / $scored * 100),
            unit: '%',
            lowData: $scored < self::LOW_DATA_BELOW,
        );
    }
}
