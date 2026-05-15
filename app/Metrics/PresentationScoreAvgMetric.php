<?php

namespace App\Metrics;

use App\Models\Fiche;
use App\Services\Okr\Metric;
use App\Services\Okr\MetricValue;
use BadMethodCallException;
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
        throw new BadMethodCallException(static::class.'::computeAsOf not yet implemented.');
    }
}
