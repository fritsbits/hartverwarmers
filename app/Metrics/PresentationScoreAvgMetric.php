<?php

namespace App\Metrics;

use App\Models\Fiche;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use Carbon\CarbonImmutable;

class PresentationScoreAvgMetric implements Metric
{
    public function compute(string $range): MetricValue
    {
        $avg = Fiche::query()
            ->published()
            ->whereNotNull('presentation_score')
            ->avg('presentation_score');

        if ($avg === null) {
            return new MetricValue(current: null, lowData: true);
        }

        return new MetricValue(current: (int) round($avg), unit: '');
    }

    public function computeAsOf(CarbonImmutable $date): MetricValue
    {
        // presentation_score is mutable, so a baseline "as of $date" is measured with
        // today's scores filtered to fiches that existed then — not the scores as they
        // were on $date. created_at is the publish-date proxy (fiches have no published_at).
        $avg = Fiche::query()
            ->published()
            ->where('created_at', '<=', $date)
            ->whereNotNull('presentation_score')
            ->avg('presentation_score');

        if ($avg === null) {
            return new MetricValue(current: null, lowData: true);
        }

        return new MetricValue(current: (int) round($avg), unit: '');
    }
}
